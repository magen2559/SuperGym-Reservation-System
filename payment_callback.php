<?php
session_start();
require_once 'include/db.php';

$status = isset($_GET['status']) ? $_GET['status'] : '';
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
$transaction_id = isset($_GET['transaction_id']) ? $_GET['transaction_id'] : '';
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';

if ($status == 'success' && $booking_id > 0) {
    
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();
    
    if ($booking && $booking['bill_code']) {
        
        $userSecretKey = "rxgtzxfu-4awp-v5jj-0jcz-fc8t5uxtsk9h";
        
        $statusData = array(
            'userSecretKey' => $userSecretKey,
            'billCode' => $booking['bill_code']
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
            $stmt = $pdo->prepare("
                UPDATE bookings 
                SET payment_status = 'paid', 
                    payment_date = NOW(),
                    transaction_id = ?
                WHERE id = ?
            ");
            $stmt->execute([$transactionId, $booking_id]);
            
            echo "<script>
                alert('Payment successful! Your booking is now confirmed.');
                window.location.href = 'my_bookings.php';
            </script>";
            exit();
        } else {
            echo "<script>
                alert('Payment verification failed. Please contact support.');
                window.location.href = 'my_bookings.php';
            </script>";
            exit();
        }
    }
} else {
    echo "<script>
        alert('Payment cancelled or failed. Please try again.');
        window.location.href = 'my_bookings.php';
    </script>";
    exit();
}
?>