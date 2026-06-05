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
    SELECT *
    FROM bookings
    WHERE id = ? 
    AND member_id = ?
    AND booking_type = 'trainer'
    AND payment_status = 'paid'
");
$stmt->execute([$booking_id, $member_id]);
$booking = $stmt->fetch();

if (!$booking) {
    header("Location: my_bookings.php?error=Invalid refund request");
    exit();
}

if ($booking['refund_status'] == 'requested') {
    header("Location: my_bookings.php?error=Refund already requested");
    exit();
}

if ($booking['refund_status'] == 'not_allowed') {
    header("Location: my_bookings.php?error=Refund is not allowed for this booking");
    exit();
}

$stmt = $pdo->prepare("
    UPDATE bookings
    SET refund_status = 'requested',
        member_action = 'refund_requested',
        refund_reason = 'Member requested refund'
    WHERE id = ?
");
$stmt->execute([$booking_id]);

header("Location: my_bookings.php?success=Refund request submitted to admin");
exit();
?>