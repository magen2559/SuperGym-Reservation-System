<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'staff') {
    header("Location: dashboard.php");
    exit();
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['approve_refund'])) {
    $booking_id = $_POST['booking_id'];
    
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            SELECT b.*, ts.id as slot_id 
            FROM bookings b
            JOIN trainer_slots ts ON b.trainer_slot_id = ts.id
            WHERE b.id = ? AND b.refund_status = 'requested'
        ");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch();
        
        if ($booking) {
            $stmt = $pdo->prepare("
                UPDATE bookings 
                SET refund_status = 'approved', 
                    payment_status = 'refunded',
                    refund_processed_date = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$booking_id]);
            
            $stmt = $pdo->prepare("
                UPDATE trainer_slots 
                SET is_available = 1 
                WHERE id = ?
            ");
            $stmt->execute([$booking['slot_id']]);
            
            $pdo->commit();
            $success_message = "Refund approved successfully!";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Error: " . $e->getMessage();
    }
}

$stmt = $pdo->prepare("
    SELECT b.*, 
           u.name AS member_name, 
           u.email AS member_email,
           ts.slot_date, 
           ts.start_time, 
           ts.end_time,
           trainer_user.name AS trainer_name
    FROM bookings b
    JOIN users u ON b.member_id = u.id
    JOIN trainer_slots ts ON b.trainer_slot_id = ts.id
    JOIN trainers t ON ts.trainer_id = t.trainer_id
    JOIN users trainer_user ON t.user_id = trainer_user.id
    WHERE b.refund_status = 'requested'
    ORDER BY b.refund_request_date DESC
");
$stmt->execute();
$refunds = $stmt->fetchAll();

$pending_count = count($refunds);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperGym - Manage Refunds</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #111;
            color: #fff;
        }
        .navbar {
            background-color: #1a1a1a;
            border-bottom: 1px solid #333;
            padding: 6px;
        }
        .navbar .container {
            max-width: 100%;
            width: 100%;
            padding-left: 0;
            padding-right: 0;
            margin: 0;
        }
        .navbar-brand,
        .navbar-brand:hover,
        .navbar-brand:focus,
        .navbar-brand:active {
            font-weight: bold;
            font-size: 30px;
            color: #d6ff00 !important;
            text-decoration: none;
            padding-left: 15px;
        }
        .nav-link {
            color: #fff !important;
            font-weight: bold;
            text-transform: uppercase;
        }
        .nav-link:hover {
            color: #d6ff00 !important;
        }
        .btn-outline-custom {
            border: 2px solid #d6ff00;
            color: #d6ff00;
            font-weight: bold;
            padding: 8px 20px;
            border-radius: 10px;
            text-decoration: none;
            background-color: transparent;
        }
        .btn-outline-custom:hover {
            background-color: #d6ff00;
            color: #000;
        }
        .btn-approve {
            background-color: #22c55e;
            color: #fff;
            font-weight: bold;
            border: none;
            padding: 5px 12px;
            border-radius: 5px;
            font-size: 12px;
        }
        .btn-approve:hover {
            background-color: #16a34a;
            color: #fff;
        }
        .welcome-text {
            color: #ddd;
            font-size: 14px;
            margin-left: 20px;
            padding-left: 20px;
            border-left: 1px solid #555;
        }
        .stat-card {
            background-color: #1a1a1a;
            border: 1px solid #333;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #d6ff00;
        }
        .stat-label {
            color: #aaa;
            font-size: 14px;
        }
        .table-dark {
            background-color: #1a1a1a;
        }
        .table-dark td, .table-dark th {
            border-color: #333;
            color: #ddd;
            text-align: center;
            vertical-align: middle;
        }
        .table-dark th {
            color: #d6ff00;
        }
        footer {
            background-color: #0a0a0a;
            padding: 40px;
            text-align: center;
            border-top: 1px solid #222;
            margin-top: 50px;
        }
        h1 {
            color: #fff;
        }
        .text-muted {
            color: #aaa !important;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <div class="d-flex align-items-center">
            <a class="navbar-brand" href="index.php">SUPERGYM</a>
            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        </div>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon bg-white"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="staff_dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_users.php">Users</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_trainers.php">Trainers</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_bookings.php">Bookings</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_refunds.php" style="color: #d6ff00 !important;">Refunds</a></li>
                <li class="nav-item"><a class="nav-link" href="equipment.php">Equipment</a></li>
                <li class="nav-item"><a class="nav-link" href="gym_capacity.php">Gym Capacity</a></li>
                <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.php">My Account</a></li>
            </ul>
            <div class="ms-4">
                <a href="logout.php" class="btn btn-outline-custom">Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col">
            <h1>Manage Refunds</h1>
            <p class="text-muted">Review and process member refund requests</p>
        </div>
    </div>
    
    <div class="row mb-4 justify-content-center">
        <div class="col-md-4 mb-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo $pending_count; ?></div>
                <div class="stat-label">Pending Refund Requests</div>
            </div>
        </div>
    </div>

    <?php if($success_message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <?php if($error_message): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <?php if(count($refunds) > 0): ?>
            <table class="table table-dark">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Email</th>
                        <th>Trainer</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Amount</th>
                        <th>Request Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($refunds as $refund): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($refund['member_name']); ?></td>
                            <td><?php echo htmlspecialchars($refund['member_email']); ?></td>
                            <td><?php echo htmlspecialchars($refund['trainer_name'] ?? 'N/A'); ?></td>
                            <td><?php echo date('D, M j', strtotime($refund['slot_date'])); ?></td>
                            <td><?php echo date('g:i A', strtotime($refund['start_time'])); ?> - <?php echo date('g:i A', strtotime($refund['end_time'])); ?></td>
                            <td>RM<?php echo number_format($refund['payment_amount'], 2); ?></td>
                            <td><?php echo date('d M Y, h:i A', strtotime($refund['refund_request_date'])); ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Approve this refund? The trainer slot will become available again.')" style="display:inline;">
                                    <input type="hidden" name="booking_id" value="<?php echo $refund['id']; ?>">
                                    <button type="submit" name="approve_refund" class="btn-approve">✓ Approve</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info text-center">No pending refund requests at this time.</div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>