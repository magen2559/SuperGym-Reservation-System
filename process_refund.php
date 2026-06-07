<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'staff') {
    header("Location: dashboard.php");
    exit();
}

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($booking_id <= 0 || !in_array($action, ['approve', 'reject'])) {
    header("Location: manage_refunds.php?error=Invalid refund action");
    exit();
}

$stmt = $pdo->prepare("
    SELECT *
    FROM bookings
    WHERE id = ?
    AND booking_type = 'trainer'
    AND refund_status = 'requested'
");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch();

if (!$booking) {
    header("Location: manage_refunds.php?error=Refund request not found");
    exit();
}

if ($action == 'approve') {
    $stmt = $pdo->prepare("
        UPDATE bookings
        SET refund_status = 'refunded',
            payment_status = 'refunded',
            refund_reason = 'Refund approved by staff'
        WHERE id = ?
    ");
    $stmt->execute([$booking_id]);

    header("Location: manage_refunds.php?success=Refund approved successfully");
    exit();
}

if ($action == 'reject') {
    $stmt = $pdo->prepare("
        UPDATE bookings
        SET refund_status = 'rejected',
            refund_reason = 'Refund rejected by staff'
        WHERE id = ?
    ");
    $stmt->execute([$booking_id]);

    header("Location: manage_refunds.php?success=Refund rejected successfully");
    exit();
}
?>