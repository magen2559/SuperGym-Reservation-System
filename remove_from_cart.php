<?php
session_start();
require_once 'include/session_check.php';

if ($_SESSION['user_role'] != 'member') {
    header("Location: dashboard.php");
    exit();
}

$index = isset($_GET['index']) ? (int)$_GET['index'] : -1;

if (isset($_SESSION['cart']['bookings'][$index])) {
    array_splice($_SESSION['cart']['bookings'], $index, 1);
    
    $total = 0;
    foreach ($_SESSION['cart']['bookings'] as $booking) {
        $total += $booking['amount'];
    }
    $_SESSION['cart']['total_amount'] = $total;
    
    if (empty($_SESSION['cart']['bookings'])) {
        unset($_SESSION['cart']);
    }
}

header("Location: cart.php");
exit();
?>