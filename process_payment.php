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
    SELECT b.*, u.name as trainer_name, ts.slot_date, ts.start_time, ts.end_time
    FROM bookings b
    JOIN trainer_slots ts ON b.trainer_slot_id = ts.id
    JOIN trainers t ON ts.trainer_id = t.id
    JOIN users u ON t.user_id = u.id
    WHERE b.id = ? AND b.member_id = ? AND b.booking_type = 'trainer'
");
$stmt->execute([$booking_id, $member_id]);
$booking = $stmt->fetch();

if (!$booking) {
    die("Invalid booking.");
}

if ($booking['payment_status'] == 'paid') {
    echo "<script>alert('This booking has already been paid.'); window.location.href='my_bookings.php';</script>";
    exit();
}

$userSecretKey = "rxgtzxfu-4awp-v5jj-0jcz-fc8t5uxtsk9h";
$categoryCode = "hnnab3c8";
$amount = 50.00;
$amount_in_cents = $amount * 100;

$billData = array(
    'userSecretKey' => $userSecretKey,
    'categoryCode' => $categoryCode,
    'billName' => 'SuperGym Trainer Booking',
    'billDescription' => 'Trainer: ' . $booking['trainer_name'] . ' on ' . date('d M Y', strtotime($booking['slot_date'])),
    'billPriceSetting' => 1,
    'billPayorInfo' => 1,
    'billAmount' => $amount_in_cents,
    'billReturnUrl' => 'http://localhost/gymsystem/payment_callback.php?status=success&booking_id=' . $booking_id,
    'billCallbackUrl' => 'http://localhost/gymsystem/payment_webhook.php',
    'billExternalReferenceNo' => 'BOOKING_' . $booking_id . '_' . time(),
    'billTo' => $_SESSION['user_name'],
    'billEmail' => $_SESSION['user_email'],
    'billPhone' => '0123456789',
    'billPaymentChannel' => '0',
    'billExpiryDays' => 3
);

$curl = curl_init();
curl_setopt($curl, CURLOPT_POST, 1);
curl_setopt($curl, CURLOPT_URL, 'https://dev.toyyibpay.com/index.php/api/createBill');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($billData));

$result = curl_exec($curl);
curl_close($curl);

$response = json_decode($result, true);

if (isset($response[0]['BillCode'])) {
    $billCode = $response[0]['BillCode'];
    
    $stmt = $pdo->prepare("UPDATE bookings SET bill_code = ?, payment_amount = ? WHERE id = ?");
    $stmt->execute([$billCode, $amount, $booking_id]);
    
    $paymentUrl = 'https://dev.toyyibpay.com/' . $billCode;
    header("Location: " . $paymentUrl);
    exit();
} else {
    echo "<h3>Error creating bill</h3>";
    echo "<pre>";
    print_r($response);
    echo "</pre>";
    echo "<a href='my_bookings.php'>Go back to My Bookings</a>";
    exit();
}
?>