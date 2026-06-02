<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'trainer') {
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id FROM trainers WHERE user_id = ?");
$stmt->execute([$user_id]);
$trainer = $stmt->fetch();

if (!$trainer) {
    header("Location: trainer_dashboard.php?error=Trainer profile not found");
    exit();
}

$trainer_id = $trainer['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['booking_id']) && isset($_POST['action'])) {
    
    $booking_id = $_POST['booking_id'];
    $action = $_POST['action'];
    
    $stmt = $pdo->prepare("
        SELECT b.id, b.status, ts.trainer_id 
        FROM bookings b
        JOIN trainer_slots ts ON b.trainer_slot_id = ts.id
        WHERE b.id = ? AND ts.trainer_id = ?
    ");
    $stmt->execute([$booking_id, $trainer_id]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        header("Location: trainer_dashboard.php?error=Booking not found");
        exit();
    }
    
    if ($booking['status'] != 'pending') {
        header("Location: trainer_dashboard.php?error=This booking has already been processed");
        exit();
    }
    
    $pdo->beginTransaction();
    
    try {
        if ($action == 'approve') {
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'approved' WHERE id = ?");
            $stmt->execute([$booking_id]);
            $message = "Booking approved successfully! Member can now make payment.";
        } elseif ($action == 'reject') {
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$booking_id]);
            
            $stmt = $pdo->prepare("
                UPDATE trainer_slots ts
                JOIN bookings b ON ts.id = b.trainer_slot_id
                SET ts.is_available = 1
                WHERE b.id = ?
            ");
            $stmt->execute([$booking_id]);
            $message = "Booking rejected. Time slot is now available again.";
        } else {
            throw new Exception("Invalid action");
        }
        
        $pdo->commit();
        header("Location: trainer_dashboard.php?success=" . urlencode($message));
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: trainer_dashboard.php?error=" . urlencode($e->getMessage()));
        exit();
    }
    
} else {
    header("Location: trainer_dashboard.php");
    exit();
}
?>