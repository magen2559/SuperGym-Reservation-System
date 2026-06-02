<?php
session_start();
require_once 'include/db.php';

$trainer_id = isset($_GET['trainer_id']) ? (int)$_GET['trainer_id'] : 0;

$response = ['slots' => [], 'is_logged_in' => isset($_SESSION['user_id'])];

if ($trainer_id > 0) {
    $stmt = $pdo->prepare("
        SELECT ts.id, ts.slot_date, ts.start_time, ts.end_time
        FROM trainer_slots ts
        WHERE ts.trainer_id = ? 
          AND ts.slot_date >= CURDATE() 
          AND ts.is_available = 1
        ORDER BY ts.slot_date, ts.start_time
        LIMIT 10
    ");
    $stmt->execute([$trainer_id]);
    $slots = $stmt->fetchAll();
    
    foreach ($slots as $slot) {
        $response['slots'][] = [
            'id' => $slot['id'],
            'date' => date('l, M j', strtotime($slot['slot_date'])),
            'start_time' => date('g:i A', strtotime($slot['start_time'])),
            'end_time' => date('g:i A', strtotime($slot['end_time']))
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>