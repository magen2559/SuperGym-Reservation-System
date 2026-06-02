<?php
session_start();
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'member') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$booking_ids = isset($input['booking_ids']) ? json_decode($input['booking_ids'], true) : [];

if (empty($booking_ids)) {
    echo json_encode(['success' => false, 'message' => 'No bookings selected']);
    exit();
}

$member_id = $_SESSION['user_id'];

$valid_bookings = [];
$total_amount = 0;

foreach ($booking_ids as $booking_id) {
    $stmt = $pdo->prepare("
        SELECT b.*, u.name as trainer_name, ts.slot_date, ts.start_time, ts.end_time
        FROM bookings b
        JOIN trainer_slots ts ON b.trainer_slot_id = ts.id
        JOIN trainers t ON ts.trainer_id = t.id
        JOIN users u ON t.user_id = u.id
        WHERE b.id = ? AND b.member_id = ? AND b.booking_type = 'trainer' 
          AND b.payment_status != 'paid' AND b.status = 'approved'
    ");
    $stmt->execute([$booking_id, $member_id]);
    $booking = $stmt->fetch();
    
    if ($booking) {
        $valid_bookings[] = [
            'id' => $booking['id'],
            'trainer_name' => $booking['trainer_name'],
            'slot_date' => $booking['slot_date'],
            'start_time' => $booking['start_time'],
            'end_time' => $booking['end_time'],
            'amount' => 50.00
        ];
        $total_amount += 50.00;
    }
}

if (empty($valid_bookings)) {
    echo json_encode(['success' => false, 'message' => 'No valid bookings selected']);
    exit();
}

$_SESSION['cart'] = [
    'bookings' => $valid_bookings,
    'total_amount' => $total_amount,
    'created_at' => time()
];

echo json_encode(['success' => true, 'message' => 'Added to cart', 'count' => count($valid_bookings)]);
?>