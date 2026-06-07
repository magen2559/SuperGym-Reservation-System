<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'member') {
    header("Location: dashboard.php");
    exit();
}

$member_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT b.*, 
           CASE 
               WHEN b.booking_type = 'gym' THEN gs.session_date
               WHEN b.booking_type = 'trainer' THEN ts.slot_date
           END as booking_date,
           CASE 
               WHEN b.booking_type = 'gym' THEN CONCAT(gs.start_time, ' - ', gs.end_time)
               WHEN b.booking_type = 'trainer' THEN CONCAT(ts.start_time, ' - ', ts.end_time)
           END as booking_time,
           CASE 
               WHEN b.booking_type = 'trainer' THEN u.name
               ELSE NULL
           END as trainer_name,
           CASE 
               WHEN b.booking_type = 'gym' THEN gs.id
               WHEN b.booking_type = 'trainer' THEN ts.id
           END as session_or_slot_id
    FROM bookings b
    LEFT JOIN gym_sessions gs ON b.gym_session_id = gs.id
    LEFT JOIN trainer_slots ts ON b.trainer_slot_id = ts.id
    LEFT JOIN trainers t ON ts.trainer_id = t.trainer_id
    LEFT JOIN users u ON t.user_id = u.id
   WHERE b.member_id = ?
AND (
    (
        CASE 
            WHEN b.booking_type = 'gym' THEN CONCAT(gs.session_date, ' ', gs.end_time)
            WHEN b.booking_type = 'trainer' THEN CONCAT(ts.slot_date, ' ', ts.end_time)
        END
    ) >= NOW()
    OR b.refund_status IN ('requested', 'approved', 'rejected')
)
AND b.status != 'completed'
ORDER BY booking_date DESC, booking_time ASC

");
$stmt->execute([$member_id]);
$bookings = $stmt->fetchAll();

$upcoming_bookings = [];

foreach ($bookings as $booking) {

    $bookingDate = $booking['booking_date'];
    $bookingTime = $booking['booking_time'];

    if (!$bookingDate || !$bookingTime) {
        continue;
    }

    $timeParts = explode(' - ', $bookingTime);
    $endTime = trim($timeParts[1] ?? $timeParts[0]);

    $bookingEndTimestamp = strtotime($bookingDate . ' ' . $endTime);
    $nowTimestamp = time();

  $showCancelledForRefund = (
    $booking['booking_type'] == 'trainer'
    &&
    $booking['status'] == 'cancelled'
    &&
    (
        ($booking['payment_status'] ?? '') == 'paid'
        ||
        ($booking['payment_status'] ?? '') == 'refunded'
    )
    &&
    in_array(($booking['refund_status'] ?? ''), [
        'not_requested',
        'requested',
        'refunded',
        'rejected',
        'not_allowed'
    ])
);

if (
    $bookingEndTimestamp >= $nowTimestamp
    &&
    (
        !in_array($booking['status'], ['cancelled', 'completed'])
        ||
        $showCancelledForRefund
    )
) {
    $upcoming_bookings[] = $booking;
}

if (
    $bookingEndTimestamp >= $nowTimestamp
    &&
    (
        !in_array($booking['status'], ['cancelled', 'completed'])
        ||
        $showCancelledForRefund
    )
) {
    $upcoming_bookings[] = $booking;
}
}

function canRefundByTime($bookingDate, $bookingTime) {
    if (!$bookingDate || !$bookingTime) {
        return false;
    }

    $parts = explode(' - ', $bookingTime);
    $startTime = trim($parts[0]);

    $sessionTimestamp = strtotime($bookingDate . ' ' . $startTime);
    $now = time();

    $hoursBefore = ($sessionTimestamp - $now) / 3600;

    return $hoursBefore >= 24;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperGym - My Bookings</title>
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
        .navbar-brand:hover {
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

        .welcome-text {
            color: #ddd;
            font-size: 14px;
            margin-left: 20px;
            padding-left: 20px;
            border-left: 1px solid #555;
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

        .main-container {
            max-width: 1280px;
            margin: auto;
        }

        .content-card {
            background-color: #eef527;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .content-card h3 {
            color: #000;
            font-weight: bold;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(0,0,0,0.3);
        }

        .table-dark {
            background-color: #1f2529;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 0;
        }

        .table-dark th {
            color: #d6ff00;
            text-align: center;
            vertical-align: middle;
            border-color: #333;
            font-size: 14px;
        }

        .table-dark td {
            color: #fff;
            text-align: center;
            vertical-align: middle;
            border-color: #333;
            font-size: 14px;
        }

        .status-badge,
        .paid-badge,
        .unpaid-badge,
        .refund-badge {
            display: inline-block;
            padding: 6px 13px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin: 2px;
        }

        .status-pending { background-color: #f59e0b; color: #000; }
        .status-approved { background-color: #22c55e; color: #fff; }
        .status-rejected { background-color: #ef4444; color: #fff; }
        .status-cancelled { background-color: #6b7280; color: #fff; }
        .status-completed { background-color: #3b82f6; color: #fff; }

        .paid-badge { background-color: #22c55e; color: #fff; }
        .unpaid-badge { background-color: #6b7280; color: #fff; }
        .refund-badge { background-color: #6b7280; color: #fff; }

        .btn-cancel,
        .btn-receipt,
        .btn-refund,
        .btn-change {
            border: none;
            padding: 6px 14px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            margin: 2px;
            cursor: pointer;
        }

        .btn-cancel,
        .btn-refund {
            background-color: #ef4444;
            color: #fff;
        }

        .btn-change {
            background-color: #d6ff00;
            color: #000;
        }

        .btn-receipt {
            background-color: #3b82f6;
            color: #fff;
        }

        h1 {
            color: #fff;
            font-weight: bold;
        }

        .text-muted {
            color: #aaa !important;
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

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon bg-white"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="member_dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="book_gym.php">Book Gym</a></li>
                <li class="nav-item"><a class="nav-link" href="book_trainer.php">Book Trainer</a></li>
                <li class="nav-item"><a class="nav-link" href="cart.php">Cart</a></li>
                <li class="nav-item"><a class="nav-link" href="my_bookings.php" style="color: #d6ff00 !important;">My Bookings</a></li>
                <li class="nav-item"><a class="nav-link" href="booking_history.php">Booking History</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.php">My Account</a></li>
            </ul>

            <div class="ms-4">
                <a href="logout.php" class="btn btn-outline-custom">Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="container main-container my-5">

    <div class="row mb-4">
        <div class="col">
            <h1>My Bookings</h1>
            <p class="text-muted">View and manage your upcoming gym session and trainer bookings</p>
        </div>
    </div>

    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>

    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div class="content-card">
        <h3>📅 Upcoming Bookings</h3>

        <?php if(count($upcoming_bookings) > 0): ?>
            <div class="table-responsive">
                <table class="table table-dark">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Trainer</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach($upcoming_bookings as $booking): ?>
                            <?php
                                $paymentStatus = $booking['payment_status'] ?? 'unpaid';
                                $refundStatus = $booking['refund_status'] ?? 'not_requested';
                                $memberAction = $booking['member_action'] ?? '';
                                $refundAllowedByTime = canRefundByTime($booking['booking_date'], $booking['booking_time']);
                            ?>

                            <tr>
                                <td>
                                    <?php echo $booking['booking_type'] == 'gym' ? '🏋️ Gym Session' : '👨‍🏫 Personal Trainer'; ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($booking['booking_date'] ?? 'N/A'); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($booking['booking_time'] ?? 'N/A'); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($booking['trainer_name'] ?? '-'); ?>
                                </td>

                                <td>
                                    <span class="status-badge status-<?php echo htmlspecialchars($booking['status']); ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if($booking['booking_type'] == 'trainer'): ?>

                                        <?php if($paymentStatus == 'paid'): ?>
                                            <span class="paid-badge">✓ Paid (RM<?php echo htmlspecialchars($booking['payment_amount']); ?>)</span>
                                            <br>
                                            <a href="receipt.php?booking_id=<?php echo $booking['id']; ?>" class="btn-receipt">🧾 Receipt</a>

                                        <?php elseif($paymentStatus == 'refunded'): ?>
                                            <span class="refund-badge">Refunded</span>

                                        <?php else: ?>
                                            <span class="unpaid-badge">Unpaid (RM<?php echo htmlspecialchars($booking['payment_amount']); ?>)</span>
                                        <?php endif; ?>

                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if($booking['booking_type'] == 'trainer' && $booking['status'] == 'rejected' && $paymentStatus == 'paid'): ?>

                                        <a href="change_trainer.php?booking_id=<?php echo $booking['id']; ?>" class="btn-change">Change Trainer</a>

                                        <a href="request_refund.php?booking_id=<?php echo $booking['id']; ?>" class="btn-refund" onclick="return confirm('Request refund for this booking?');">
                                            Request Refund
                                        </a>

                                    <?php elseif($booking['booking_type'] == 'trainer' && $booking['status'] == 'cancelled'): ?>

    <?php if($refundStatus == 'requested'): ?>
        <span class="unpaid-badge">Refund Requested</span>

    <?php elseif($refundStatus == 'refunded'): ?>
        <span class="paid-badge">Refund Approved</span>

    <?php elseif($refundStatus == 'rejected'): ?>
        <span class="unpaid-badge">Refund Rejected</span>

    <?php elseif($refundStatus == 'not_allowed' || $memberAction == 'refund_not_allowed' || !$refundAllowedByTime): ?>
        <span class="unpaid-badge">Refund Not Allowed</span>

    <?php else: ?>
        <a href="request_refund.php?booking_id=<?php echo $booking['id']; ?>" class="btn-refund" onclick="return confirm('Request refund for this cancelled booking?');">
            Request Refund
        </a>
    <?php endif; ?>

                                    <?php elseif($booking['status'] == 'pending' || $booking['status'] == 'approved'): ?>

                                        <form action="cancel_booking.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to cancel this booking?')">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <input type="hidden" name="booking_type" value="<?php echo $booking['booking_type']; ?>">
                                            <input type="hidden" name="session_or_slot_id" value="<?php echo $booking['session_or_slot_id']; ?>">
                                            <button type="submit" name="cancel_booking" class="btn-cancel">Cancel</button>
                                        </form>

                                    <?php else: ?>

                                        <span class="text-muted">-</span>

                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="color:#000; font-weight:bold;">No upcoming bookings.</p>
        <?php endif; ?>
    </div>

</div>

<div class="simple-footer">
    <div class="logo">SUPERGYM</div>
    <p>© SuperGym Booking System. All Rights Reserved.</p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>