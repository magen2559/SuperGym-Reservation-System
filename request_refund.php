<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'member') {
    header("Location: dashboard.php");
    exit();
}

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
$member_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT b.*, 
           b.payment_date, 
           b.payment_status, 
           b.refund_status,
           b.payment_amount,
           CASE 
               WHEN b.booking_type = 'gym' THEN gs.session_date
               WHEN b.booking_type = 'trainer' THEN ts.slot_date
           END as booking_date,
           CASE 
               WHEN b.booking_type = 'gym' THEN gs.start_time
               WHEN b.booking_type = 'trainer' THEN ts.start_time
           END as booking_start_time
    FROM bookings b
    LEFT JOIN gym_sessions gs ON b.gym_session_id = gs.id
    LEFT JOIN trainer_slots ts ON b.trainer_slot_id = ts.id
    WHERE b.id = ? AND b.member_id = ?
");
$stmt->execute([$booking_id, $member_id]);
$booking = $stmt->fetch();

if (!$booking) {
    header("Location: my_bookings.php?error=Booking not found");
    exit();
}

if ($booking['payment_status'] != 'paid') {
    header("Location: my_bookings.php?error=This booking has not been paid");
    exit();
}

if ($booking['refund_status'] != 'none') {
    header("Location: my_bookings.php?error=Refund already requested or processed");
    exit();
}

$booking_datetime = strtotime($booking['booking_date'] . ' ' . $booking['booking_start_time']);
$now = time();
$hours_until_booking = ($booking_datetime - $now) / 3600;

if ($hours_until_booking <= 24) {
    header("Location: my_bookings.php?error=Refund can only be requested more than 24 hours before the session starts");
    exit();
}

if ($hours_until_booking <= 0) {
    header("Location: my_bookings.php?error=The session has already started or passed. Refund is no longer available");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_refund'])) {
    
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET refund_status = 'requested', 
            refund_request_date = NOW(),
            refund_amount = payment_amount
        WHERE id = ?
    ");
    $stmt->execute([$booking_id]);
    
    header("Location: my_bookings.php?success=Refund request submitted. Please wait for admin approval.");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Refund - SuperGym</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #111; color: #fff; }
        .refund-card { background-color: #1a1a1a; border-radius: 15px; padding: 30px; max-width: 500px; margin: 50px auto; }
        .btn-refund { background-color: #ef4444; color: #fff; font-weight: bold; }
        .btn-refund:hover { background-color: #dc2626; }
        .btn-cancel { background-color: #6b7280; color: #fff; }
        .info-box {
            background-color: #2a2a2a;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .info-box p {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="refund-card">
        <h2 class="mb-4">Request Refund</h2>
        
        <div class="info-box">
            <p><strong>Booking ID:</strong> #<?php echo $booking['id']; ?></p>
            <p><strong>Amount:</strong> RM<?php echo number_format($booking['payment_amount'], 2); ?></p>
            <p><strong>Session Date:</strong> <?php echo date('d M Y', strtotime($booking['booking_date'])); ?></p>
            <p><strong>Session Time:</strong> <?php echo date('g:i A', strtotime($booking['booking_start_time'])); ?></p>
            <p><strong>Paid On:</strong> <?php echo date('d M Y, h:i A', strtotime($booking['payment_date'])); ?></p>
        </div>
        
        <p class="text-warning">⚠️ Refund can only be requested <strong>more than 24 hours before</strong> the session starts.</p>
        
        <?php if ($hours_until_booking > 24): ?>
            <p class="text-success">✓ You are eligible for refund. <?php echo round($hours_until_booking); ?> hours remaining until session.</p>
        <?php endif; ?>
        
        <form method="POST">
            <div class="d-flex gap-3">
                <button type="submit" name="confirm_refund" class="btn btn-refund px-4 py-2">Submit Refund Request</button>
                <a href="my_bookings.php" class="btn btn-cancel px-4 py-2 text-decoration-none">Cancel</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>