<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'staff') {
    header("Location: dashboard.php");
    exit();
}

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

if ($booking_id <= 0) {
    header("Location: manage_refunds.php");
    exit();
}

$stmt = $pdo->prepare("
    UPDATE bookings
    SET refund_status = 'refunded',
        member_action = 'refund_completed',
        refund_reason = 'Refund processed by staff'
    WHERE id = ?
");
$stmt->execute([$booking_id]);

header("Location: manage_refunds.php");
exit();
?>