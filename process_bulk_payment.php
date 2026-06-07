<?php
session_start();
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'member') {
    header("Location: dashboard.php");
    exit();
}

if (!isset($_SESSION['cart']) || empty($_SESSION['cart']['bookings'])) {
    header("Location: cart.php?error=Cart is empty");
    exit();
}

$cart = $_SESSION['cart'];
$bookings = $cart['bookings'];
$total_amount = $cart['total_amount'];
$amount_in_cents = $total_amount * 100;

$member_id = $_SESSION['user_id'];
$member_name = $_SESSION['user_name'];
$member_email = $_SESSION['user_email'] ?? 'customer@supergym.com';

$external_order_no = 'BULK_' . time() . '_' . $member_id;

// 开发环境配置（上线前改为生产环境）
$is_production = false;  // 本地测试 false，上线改 true

if ($is_production) {
    $userSecretKey = "uj2klw22-20bs-jb06-y8do-nks642kkzt25";
    $categoryCode = "q1t4cpsy";
    $api_url = 'https://toyyibpay.com/index.php/api/createBill';
    $payment_url_base = 'https://toyyibpay.com/';
    $baseUrl = "https://yourdomain.com";
} else {
    $userSecretKey = "rxgtzxfu-4awp-v5jj-0jcz-fc8t5uxtsk9h";
    $categoryCode = "hnnab3c8";
    $api_url = 'https://dev.toyyibpay.com/index.php/api/createBill';
    $payment_url_base = 'https://dev.toyyibpay.com/';
    $baseUrl = "http://localhost/gymsystem";
}

$description = "Trainer Bookings: ";
foreach ($bookings as $booking) {
    $description .= $booking['trainer_name'] . ' (' . date('d M', strtotime($booking['slot_date'])) . ') ';
}

$billData = array(
    'userSecretKey' => $userSecretKey,
    'categoryCode' => $categoryCode,
    'billName' => 'SuperGym - Bulk Payment',
    'billDescription' => substr($description, 0, 100),
    'billPriceSetting' => 1,
    'billPayorInfo' => 1,
    'billAmount' => $amount_in_cents,
    'billReturnUrl' => $baseUrl . '/payment_callback_bulk.php?status=success',
    'billCallbackUrl' => $baseUrl . '/payment_webhook.php',
    'billExternalReferenceNo' => $external_order_no,
    'billTo' => $member_name,
    'billEmail' => $member_email,
    'billPhone' => '0123456789',
    'billPaymentChannel' => '0',
    'billExpiryDays' => 3
);

$curl = curl_init();
curl_setopt($curl, CURLOPT_POST, 1);
curl_setopt($curl, CURLOPT_URL, $api_url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($billData));

$result = curl_exec($curl);
$curl_error = curl_error($curl);
curl_close($curl);

if ($curl_error) {
    echo "<h3>cURL Error</h3>";
    echo "<pre>" . htmlspecialchars($curl_error) . "</pre>";
    echo "<a href='cart.php'>Go back to Cart</a>";
    exit();
}

$response = json_decode($result, true);

if (isset($response[0]['BillCode'])) {
    $billCode = $response[0]['BillCode'];
    
    foreach ($bookings as $booking) {
        $stmt = $pdo->prepare("UPDATE bookings SET bill_code = ?, payment_amount = ? WHERE id = ?");
        $stmt->execute([$billCode, $booking['amount'], $booking['id']]);
    }
    
    $_SESSION['bulk_payment'] = [
        'bill_code' => $billCode,
        'booking_ids' => array_column($bookings, 'id'),
        'total_amount' => $total_amount
    ];
    
    $paymentUrl = $payment_url_base . $billCode;
    header("Location: " . $paymentUrl);
    exit();
} else {
    echo "<h3>Error creating bill</h3>";
    echo "<pre>";
    print_r($response);
    echo "</pre>";
    echo "<a href='cart.php'>Go back to Cart</a>";
    exit();
}
?>