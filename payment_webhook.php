<?php
require_once 'include/db.php';

$billCode = $_POST['billcode'] ?? '';
$status = $_POST['status_id'] ?? '';
$transaction_id = $_POST['transaction_id'] ?? '';

if (!empty($billCode) && $status == '1') {

    $stmt = $pdo->prepare("
        UPDATE bookings
        SET payment_status = 'paid',
            payment_date = NOW(),
            transaction_id = ?
        WHERE bill_code = ?
    ");

    $stmt->execute([
        $transaction_id,
        $billCode
    ]);
}

http_response_code(200);
echo "OK";
exit();
?>