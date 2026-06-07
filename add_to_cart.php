<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

header('Content-Type: application/json');

if ($_SESSION['user_role'] != 'member') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$slot_id = isset($input['slot_id']) ? (int)$input['slot_id'] : 0;

if ($slot_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid slot selected']);
    exit();
}

$member_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT ts.*, u.name AS trainer_name
    FROM trainer_slots ts
    JOIN trainers t ON ts.trainer_id = t.trainer_id
    JOIN users u ON t.user_id = u.id
    WHERE ts.id = ?
    LIMIT 1
");
$stmt->execute([$slot_id]);
$slot = $stmt->fetch();

if (!$slot) {
    echo json_encode(['success' => false, 'message' => 'Slot not found']);
    exit();
}

foreach ($_SESSION['cart']['bookings'] ?? [] as $item) {
    if ($item['slot_id'] == $slot_id) {
        echo json_encode(['success' => false, 'message' => 'This slot is already in cart']);
        exit();
    }
}

$amount = 2.00;

$_SESSION['cart']['bookings'][] = [
    'id' => null,
    'slot_id' => $slot_id,
    'trainer_name' => $slot['trainer_name'],
    'slot_date' => $slot['slot_date'],
    'start_time' => $slot['start_time'],
    'end_time' => $slot['end_time'],
    'amount' => $amount
];

$_SESSION['cart']['total_amount'] = 0;
foreach ($_SESSION['cart']['bookings'] as $item) {
    $_SESSION['cart']['total_amount'] += $item['amount'];
}

echo json_encode([
    'success' => true,
    'message' => 'Slot added to cart'
]);
?>