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
           END as booking_start_time,
           CASE 
               WHEN b.booking_type = 'gym' THEN gs.end_time
               WHEN b.booking_type = 'trainer' THEN ts.end_time
           END as booking_end_time,
           CASE 
               WHEN b.booking_type = 'trainer' THEN u.name
               ELSE NULL
           END as trainer_name,
           CASE 
               WHEN b.booking_type = 'gym' THEN 'Gym Session'
               WHEN b.booking_type = 'trainer' THEN 'Personal Trainer'
           END as booking_type_name
    FROM bookings b
    LEFT JOIN gym_sessions gs ON b.gym_session_id = gs.id
    LEFT JOIN trainer_slots ts ON b.trainer_slot_id = ts.id
    LEFT JOIN trainers t ON ts.trainer_id = t.trainer_id
    LEFT JOIN users u ON t.user_id = u.id
    WHERE b.id = ? AND b.member_id = ?
");
$stmt->execute([$booking_id, $member_id]);
$booking = $stmt->fetch();

if (!$booking) {
    header("Location: my_bookings.php?error=Booking not found");
    exit();
}

// 检查支付状态
if ($booking['payment_status'] != 'paid') {
    header("Location: my_bookings.php?error=This booking has not been paid");
    exit();
}

// 检查退款状态
if ($booking['refund_status'] == 'requested') {
    header("Location: my_bookings.php?error=Refund already requested");
    exit();
}

if ($booking['refund_status'] == 'approved') {
    header("Location: my_bookings.php?error=Refund already approved");
    exit();
}

if ($booking['refund_status'] == 'rejected') {
    header("Location: my_bookings.php?error=Refund request was rejected. Please contact support.");
    exit();
}

if (!$booking['booking_date'] || !$booking['booking_start_time']) {
    header("Location: my_bookings.php?error=Cannot request refund for this booking");
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
        WHERE id = ? AND member_id = ?
    ");
    $stmt->execute([$booking_id, $member_id]);
    
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
        .navbar-brand {
            font-weight: bold;
            font-size: 30px;
            color: #d6ff00 !important;
            text-decoration: none;
            padding-left: 15px;
        }
        .refund-card { 
            background-color: #EEF527;
            border-radius: 15px; 
            padding: 30px; 
            max-width: 550px; 
            margin: 50px auto;
            color: #000;
        }
        .refund-card h2 {
            color: #000;
            border-bottom: 1px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .btn-refund { 
            background-color: #000; 
            color: #fff; 
            font-weight: bold;
            border: none;
            padding: 10px 25px;
            border-radius: 10px;
        }
        .btn-refund:hover { 
            background-color: #333; 
            color: #fff; 
        }
        .btn-cancel { 
            background-color: #6b7280; 
            color: #fff;
            font-weight: bold;
            border: none;
            padding: 10px 25px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-cancel:hover { 
            background-color: #555; 
            color: #fff;
        }
        .info-box {
            background-color: rgba(0,0,0,0.05);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .info-box p {
            margin-bottom: 8px;
            color: #000;
        }
        .info-box strong {
            color: #000;
        }
        .text-warning {
            color: #dc2626 !important;
            font-weight: bold;
        }
        .text-success {
            color: #16a34a !important;
            font-weight: bold;
        }
        .badge-trainer {
            background-color: #f59e0b;
            color: #000;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
        }
        .badge-gym {
            background-color: #22c55e;
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <div class="d-flex align-items-center">
            <a class="navbar-brand" href="index.php">SUPERGYM</a>
            <span class="welcome-text" style="color: #ddd; font-size: 14px; margin-left: 20px; padding-left: 20px; border-left: 1px solid #555;">
                Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
            </span>
        </div>
        <div class="ms-4">
            <a href="my_bookings.php" class="btn btn-outline-custom" style="border: 2px solid #d6ff00; color: #d6ff00; padding: 8px 20px; border-radius: 10px; text-decoration: none;">← Back</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="refund-card">
        <h2>💰 Request Refund</h2>
        
        <div class="info-box">
            <p>
                <strong>Booking Type:</strong> 
                <span class="<?php echo $booking['booking_type'] == 'trainer' ? 'badge-trainer' : 'badge-gym'; ?>">
                    <?php echo $booking['booking_type_name']; ?>
                </span>
            </p>
            <p><strong>Booking ID:</strong> #<?php echo $booking['id']; ?></p>
            <p><strong>Amount:</strong> <span style="color: #dc2626; font-weight: bold;">RM<?php echo number_format($booking['payment_amount'], 2); ?></span></p>
            <p><strong>Session Date:</strong> <?php echo date('D, M j, Y', strtotime($booking['booking_date'])); ?></p>
            <p><strong>Session Time:</strong> <?php echo date('g:i A', strtotime($booking['booking_start_time'])); ?></p>
            <?php if($booking['booking_type'] == 'trainer' && $booking['trainer_name']): ?>
                <p><strong>Trainer:</strong> <?php echo htmlspecialchars($booking['trainer_name']); ?></p>
            <?php endif; ?>
            <p><strong>Paid On:</strong> <?php echo date('d M Y, h:i A', strtotime($booking['payment_date'])); ?></p>
        </div>
        
        <div class="alert alert-warning" style="background-color: #fef3c7; color: #92400e; border: none;">
            ⚠️ <strong>Refund Policy:</strong> Refund can only be requested <strong>more than 24 hours before</strong> the session starts.
        </div>
        
        <?php if ($hours_until_booking > 24): ?>
            <div class="alert alert-success" style="background-color: #d1fae5; color: #065f46; border: none;">
                ✓ <strong>You are eligible for refund!</strong> <?php echo round($hours_until_booking); ?> hours remaining until session starts.
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="d-flex gap-3 justify-content-between">
                <button type="submit" name="confirm_refund" class="btn-refund" onclick="return confirm('Are you sure you want to request a refund? This action cannot be undone.')">
                    ✓ Submit Refund Request
                </button>
                <a href="my_bookings.php" class="btn-cancel">✗ Cancel</a>
            </div>
        </form>
        
        <p class="text-muted mt-3 mb-0" style="color: #555 !important; font-size: 12px;">
            * Refund requests are reviewed by staff within 3-5 business days.
        </p>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>