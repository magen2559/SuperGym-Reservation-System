<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'member') {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_booking'])) {
    
    $booking_id = $_POST['booking_id'];
    $booking_type = $_POST['booking_type'];
    $session_or_slot_id = $_POST['session_or_slot_id'];
    $member_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("SELECT id, status FROM bookings WHERE id = ? AND member_id = ?");
    $stmt->execute([$booking_id, $member_id]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        header("Location: my_bookings.php?error=Booking not found");
        exit();
    }
    
    if ($booking['status'] != 'pending' && $booking['status'] != 'approved') {
        header("Location: my_bookings.php?error=Cannot cancel this booking (Status: " . $booking['status'] . ")");
        exit();
    }
    
    $pdo->beginTransaction();
    
    try {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$booking_id]);
        
        if ($booking_type == 'gym') {
            $stmt = $pdo->prepare("UPDATE gym_sessions SET current_bookings = current_bookings - 1 WHERE id = ?");
            $stmt->execute([$session_or_slot_id]);
        }
        
        if ($booking_type == 'trainer') {
            $stmt = $pdo->prepare("UPDATE trainer_slots SET is_available = TRUE WHERE id = ?");
            $stmt->execute([$session_or_slot_id]);
        }
        
        $pdo->commit();
        
        header("Location: my_bookings.php?success=Booking cancelled successfully");
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: my_bookings.php?error=Cancellation failed: " . $e->getMessage());
        exit();
    }
    
} else {
    header("Location: my_bookings.php");
    exit();
}
?>