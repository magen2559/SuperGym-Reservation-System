<?php
require_once 'include/db.php';

$input = file_get_contents('php://input');
parse_str($input, $data);

$billCode = $data['billcode'] ?? '';
$status = $data['status_id'] ?? '';
$transaction_id = $data['transaction_id'] ?? '';
$msg = $data['msg'] ?? '';

if ($billCode && $status == '1') {
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET payment_status = 'paid', 
            payment_date = NOW(),
            transaction_id = ?
        WHERE bill_code = ?
    ");
    $stmt->execute([$transaction_id, $billCode]);
}

echo "OK";
?>