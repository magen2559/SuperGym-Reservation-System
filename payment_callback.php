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
            
            $stmt = $pdo->prepare("
                SELECT b.*, 
                       u.name as member_name, 
                       u.email as member_email,
                       CASE 
                           WHEN b.booking_type = 'trainer' THEN trainer.name
                           ELSE NULL
                       END as trainer_name,
                       CASE 
                           WHEN b.booking_type = 'gym' THEN gs.session_date
                           WHEN b.booking_type = 'trainer' THEN ts.slot_date
                       END as session_date,
                       CASE 
                           WHEN b.booking_type = 'gym' THEN CONCAT(gs.start_time, ' - ', gs.end_time)
                           WHEN b.booking_type = 'trainer' THEN CONCAT(ts.start_time, ' - ', ts.end_time)
                       END as session_time
                FROM bookings b
                JOIN users u ON b.member_id = u.id
                LEFT JOIN gym_sessions gs ON b.gym_session_id = gs.id
                LEFT JOIN trainer_slots ts ON b.trainer_slot_id = ts.id
                LEFT JOIN trainers t ON ts.trainer_id = t.id
                LEFT JOIN users trainer ON t.user_id = trainer.id
                WHERE b.id = ?
            ");
            $stmt->execute([$booking_id]);
            $receipt_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $_SESSION['receipt_data'] = $receipt_data;
            $_SESSION['payment_success'] = true;
            
            header("Location: receipt.php?booking_id=" . $booking_id);
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