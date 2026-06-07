<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'member') {
    header("Location: dashboard.php");
    exit();
}

$member_id = $_SESSION['user_id'];
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

$stmt = $pdo->prepare("
    SELECT *
    FROM bookings
    WHERE id = ?
    AND member_id = ?
    AND booking_type = 'trainer'
    AND status = 'rejected'
    AND payment_status = 'paid'
");
$stmt->execute([$booking_id, $member_id]);
$booking = $stmt->fetch();

if (!$booking) {
    header("Location: my_bookings.php?error=Invalid change trainer request");
    exit();
}

$_SESSION['change_booking_id'] = $booking_id;
$_SESSION['change_payment_amount'] = $booking['payment_amount'];
$_SESSION['change_bill_code'] = $booking['bill_code'];
$_SESSION['change_transaction_id'] = $booking['transaction_id'];

header("Location: book_trainer.php?change=1");
exit();
?>