<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'staff') {
    header("Location: dashboard.php");
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['refund_action'])) {
    $booking_id = (int)$_POST['booking_id'];
    $action = $_POST['refund_action'];

    if ($action == 'approve') {

        $stmt = $pdo->prepare("
            UPDATE bookings
            SET refund_status = 'refunded',
                payment_status = 'refunded',
                refund_completed_date = NOW(),
                status = 'cancelled'
            WHERE id = ?
            AND refund_status = 'requested'
        ");
        $stmt->execute([$booking_id]);

        $success = "Refund approved successfully.";
    }

    if ($action == 'reject') {

        $stmt = $pdo->prepare("
            UPDATE bookings
            SET refund_status = 'rejected',
                status = 'cancelled'
            WHERE id = ?
            AND refund_status = 'requested'
        ");
        $stmt->execute([$booking_id]);

        $success = "Refund rejected successfully.";
    }
}

$stmt = $pdo->prepare("
    SELECT b.*,
           u.name AS member_name,
           u.email AS member_email,
           trainer_user.name AS trainer_name,
           ts.slot_date,
           ts.start_time,
           ts.end_time
    FROM bookings b
    JOIN users u ON b.member_id = u.id
    LEFT JOIN trainer_slots ts ON b.trainer_slot_id = ts.id
    LEFT JOIN trainers t ON ts.trainer_id = t.trainer_id
    LEFT JOIN users trainer_user ON t.user_id = trainer_user.id
    WHERE b.refund_status = 'requested'
    ORDER BY b.refund_request_date DESC
");
$stmt->execute();
$refunds = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Refunds - SuperGym</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body { background-color: #111; color: #fff; }

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

        .navbar-brand {
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

        .welcome-text {
            color: #ddd;
            font-size: 14px;
            margin-left: 20px;
            padding-left: 20px;
            border-left: 1px solid #555;
        }

        .content-card {
            background-color: #EEF527;
            border-radius: 15px;
            padding: 25px;
            margin-top: 30px;
        }

        .content-card h3 {
            color: #000;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .table-dark th {
            color: #d6ff00;
            text-align: center;
            vertical-align: middle;
        }

        .table-dark td {
            text-align: center;
            vertical-align: middle;
        }

        .btn-approve {
            background-color: #22c55e;
            color: #fff;
            border: none;
            padding: 6px 14px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 12px;
        }

        .btn-reject {
            background-color: #ef4444;
            color: #fff;
            border: none;
            padding: 6px 14px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 12px;
        }

        .simple-footer {
            background-color: #0a0a0a;
            padding: 40px;
            text-align: center;
            border-top: 1px solid #222;
            margin-top: 50px;
        }

        .simple-footer .logo {
            font-size: 1.8rem;
            font-weight: bold;
            font-style: italic;
            color: #d6ff00;
            margin-bottom: 15px;
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

        <div class="collapse navbar-collapse show">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="staff_dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_bookings.php">Manage Bookings</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_users.php">Manage Users</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_refunds.php" style="color:#d6ff00 !important;">Refunds</a></li>
            </ul>

            <div class="ms-4">
                <a href="logout.php" class="btn btn-outline-custom">Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="container my-5">
    <h1>Manage Refunds</h1>
    <p class="text-muted">Approve or reject member refund requests</p>

    <?php if($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="content-card">
        <h3>💰 Refund Requests</h3>

        <?php if(count($refunds) > 0): ?>
            <div class="table-responsive">
                <table class="table table-dark">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Member</th>
                            <th>Trainer</th>
                            <th>Session</th>
                            <th>Amount</th>
                            <th>Request Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach($refunds as $refund): ?>
                            <tr>
                                <td>#<?php echo $refund['id']; ?></td>

                                <td>
                                    <?php echo htmlspecialchars($refund['member_name']); ?><br>
                                    <small><?php echo htmlspecialchars($refund['member_email']); ?></small>
                                </td>

                                <td><?php echo htmlspecialchars($refund['trainer_name'] ?? '-'); ?></td>

                                <td>
                                    <?php echo date('d M Y', strtotime($refund['slot_date'])); ?><br>
                                    <?php echo date('g:i A', strtotime($refund['start_time'])); ?> -
                                    <?php echo date('g:i A', strtotime($refund['end_time'])); ?>
                                </td>

                                <td>RM <?php echo number_format($refund['payment_amount'], 2); ?></td>

                                <td>
                                    <?php
                                    if (!empty($refund['refund_request_date'])) {
                                        echo date('d M Y, h:i A', strtotime($refund['refund_request_date']));
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>

                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="booking_id" value="<?php echo $refund['id']; ?>">
                                        <button type="submit" name="refund_action" value="approve" class="btn-approve"
                                            onclick="return confirm('Approve this refund?')">
                                            Approve
                                        </button>
                                    </form>

                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="booking_id" value="<?php echo $refund['id']; ?>">
                                        <button type="submit" name="refund_action" value="reject" class="btn-reject"
                                            onclick="return confirm('Reject this refund?')">
                                            Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="color:#333;">No refund requests at the moment.</p>
        <?php endif; ?>
    </div>
</div>

<div class="simple-footer">
    <div class="logo">SUPERGYM</div>
    <p>© SuperGym Booking System. All Rights Reserved.</p>
</div>

</body>
</html>