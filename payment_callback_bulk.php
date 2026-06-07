<?php
session_start();
require_once 'include/db.php';

$status = isset($_GET['status']) ? $_GET['status'] : '';

if ($status == 'success' && isset($_SESSION['bulk_payment'])) {
    
    $bulk_payment = $_SESSION['bulk_payment'];
    $bill_code = $bulk_payment['bill_code'];
    $booking_ids = $bulk_payment['booking_ids'];
    $total_amount = $bulk_payment['total_amount'];
    
    $userSecretKey = "rxgtzxfu-4awp-v5jj-0jcz-fc8t5uxtsk9h";
    
    $statusData = array(
        'userSecretKey' => $userSecretKey,
        'billCode' => $bill_code
    );
    
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_URL, 'https://dev.toyyibpay.com/index.php/api/getBillTransactions');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($statusData));
    
    $result = curl_exec($curl);
    curl_close($curl);
    
    $transactions = json_decode($result, true);
    
    $isPaid = false;
    $transactionId = '';
    
    if (is_array($transactions)) {
        foreach ($transactions as $transaction) {
            if ($transaction['billpaymentStatus'] == '1') {
                $isPaid = true;
                $transactionId = $transaction['billpaymentInvoiceNo'] ?? '';
                break;
            }
        }
    }
    
    if ($isPaid) {
        $placeholders = implode(',', array_fill(0, count($booking_ids), '?'));
        $stmt = $pdo->prepare("
            UPDATE bookings 
            SET payment_status = 'paid', 
                payment_date = NOW(),
                transaction_id = ?
            WHERE id IN ($placeholders)
        ");
        $stmt->execute([$transactionId, ...$booking_ids]);
        
        unset($_SESSION['cart']);
        unset($_SESSION['bulk_payment']);
        
        $_SESSION['payment_success'] = "Bulk payment successful! " . count($booking_ids) . " bookings confirmed.";
        header("Location: my_bookings.php");
        exit();
    } else {
        $_SESSION['payment_error'] = "Payment verification failed.";
        header("Location: cart.php");
        exit();
    }
} else {
    $_SESSION['payment_error'] = "Payment cancelled or failed.";
    header("Location: cart.php");
    exit();
}
?>