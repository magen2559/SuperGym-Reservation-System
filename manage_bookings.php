<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'staff') {
    header("Location: dashboard.php");
    exit();
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $booking_id = $_GET['id'];
    $action = $_GET['action'];

    if ($action == 'approve') {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'approved' WHERE id = ?");
        $stmt->execute([$booking_id]);
        $success = "Booking approved successfully!";
    }

    if ($action == 'reject') {
        $stmt = $pdo->prepare("SELECT gym_session_id FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch();

        if ($booking && $booking['gym_session_id']) {
            $stmt = $pdo->prepare("UPDATE gym_sessions SET current_bookings = current_bookings - 1 WHERE id = ? AND current_bookings > 0");
            $stmt->execute([$booking['gym_session_id']]);
        }

        $stmt = $pdo->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$booking_id]);
        $success = "Booking rejected successfully!";
    }

    header("Location: manage_bookings.php?success=" . urlencode($success));
    exit();
}

$stmt = $pdo->prepare("
    SELECT 
        b.id,
        b.booking_type,
        b.status,
        b.booking_date,
        u.name AS member_name,
        u.email AS member_email,
        gs.session_date,
        gs.start_time,
        gs.end_time,
        gs.max_capacity,
        gs.current_bookings
    FROM bookings b
    JOIN users u ON b.member_id = u.id
    JOIN gym_sessions gs ON b.gym_session_id = gs.id
    WHERE b.booking_type = 'gym' AND b.status = 'pending'
    ORDER BY b.booking_date ASC
");
$stmt->execute();
$bookings = $stmt->fetchAll();

$stmt = $pdo->query("SELECT COUNT(*) as count FROM bookings WHERE booking_type = 'gym' AND status = 'pending'");
$pending_count = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperGym - Manage Gym Bookings</title>
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
        .btn-primary-custom {
            background-color: #d6ff00;
            color: #000;
            font-weight: bold;
            border: none;
            padding: 8px 20px;
            border-radius: 10px;
        }
        .btn-primary-custom:hover {
            background-color: #c0e800;
            color: #000;
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
        .btn-success-custom {
            background-color: #22c55e;
            color: #fff;
            border: none;
            padding: 5px 12px;
            border-radius: 5px;
            font-size: 12px;
            margin-right: 5px;
        }
        .btn-danger-custom {
            background-color: #ef4444;
            color: #fff;
            border: none;
            padding: 5px 12px;
            border-radius: 5px;
            font-size: 12px;
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
                <li class="nav-item"><a class="nav-link" href="manage_bookings.php" style="color: #d6ff00 !important;">Gym Bookings</a></li>
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
            <h1>Manage Gym Bookings</h1>
            <p class="text-muted">Approve or reject pending gym session requests</p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6 mx-auto">
            <div class="stat-card">
                <div class="stat-number"><?php echo $pending_count; ?></div>
                <div class="stat-label">Pending Gym Bookings</div>
            </div>
        </div>
    </div>

    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>

    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <?php if(count($bookings) > 0): ?>
            <table class="table table-dark">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Email</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Capacity</th>
                        <th>Requested At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($bookings as $booking): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($booking['member_name']); ?></td>
                            <td><?php echo htmlspecialchars($booking['member_email']); ?></td>
                            <td><?php echo date('D, M j', strtotime($booking['session_date'])); ?></td>
                            <td><?php echo date('g:i A', strtotime($booking['start_time'])); ?> - <?php echo date('g:i A', strtotime($booking['end_time'])); ?></td>
                            <td><?php echo $booking['current_bookings']; ?>/<?php echo $booking['max_capacity']; ?></td>
                            <td><?php echo date('d M Y, h:i A', strtotime($booking['booking_date'])); ?></td>
                            <td>
                                <a href="?action=approve&id=<?php echo $booking['id']; ?>" class="btn btn-success-custom" onclick="return confirm('Approve this booking?')">✓ Approve</a>
                                <a href="?action=reject&id=<?php echo $booking['id']; ?>" class="btn btn-danger-custom" onclick="return confirm('Reject this booking? This will free up the capacity.')">✗ Reject</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info text-center">No pending gym booking requests.</div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>