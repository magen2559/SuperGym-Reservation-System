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
    
    $stmt = $pdo->prepare("
        SELECT b.*, ts.slot_date, ts.start_time
        FROM bookings b
        LEFT JOIN trainer_slots ts ON b.trainer_slot_id = ts.id
        WHERE b.id = ? AND b.member_id = ?
    ");
    $stmt->execute([$booking_id, $member_id]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        header("Location: my_bookings.php?error=Booking not found");
        exit();
    }
    
    if ($booking['status'] != 'pending' && $booking['status'] != 'approved') {
        header("Location: my_bookings.php?error=Cannot cancel this booking");
        exit();
    }
    
    $pdo->beginTransaction();
    
    try {
        if ($booking_type == 'trainer' && $booking['payment_status'] == 'paid') {
            
            $session_time = strtotime($booking['slot_date'] . ' ' . $booking['start_time']);
            $now = time();
            $hours_before = ($session_time - $now) / 3600;

            if ($hours_before >= 24) {
                $stmt = $pdo->prepare("
                    UPDATE bookings 
                    SET status = 'cancelled',
                        member_action = 'refund_available',
                        refund_status = 'not_requested',
                        refund_reason = 'Member cancelled more than 24 hours before session'
                    WHERE id = ?
                ");
                $stmt->execute([$booking_id]);

                $success_msg = "Booking cancelled successfully. You can request refund.";
            } else {
                $stmt = $pdo->prepare("
                    UPDATE bookings 
                    SET status = 'cancelled',
                        member_action = 'refund_not_allowed',
                        refund_status = 'not_allowed',
                        refund_reason = 'Member cancelled less than 24 hours before session'
                    WHERE id = ?
                ");
                $stmt->execute([$booking_id]);

                $success_msg = "Booking cancelled successfully, but refund is not allowed because cancellation is less than 24 hours before session.";
            }

        } else {
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$booking_id]);

            $success_msg = "Booking cancelled successfully";
        }
        
        if ($booking_type == 'gym') {
            $stmt = $pdo->prepare("
                UPDATE gym_sessions 
                SET current_bookings = current_bookings - 1 
                WHERE id = ? AND current_bookings > 0
            ");
            $stmt->execute([$session_or_slot_id]);
        }
        
        if ($booking_type == 'trainer') {
            $stmt = $pdo->prepare("
                UPDATE trainer_slots 
                SET is_available = TRUE 
                WHERE id = ?
            ");
            $stmt->execute([$session_or_slot_id]);
        }
        
        $pdo->commit();
        
        header("Location: my_bookings.php?success=" . urlencode($success_msg));
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: my_bookings.php?error=Cancellation failed: " . urlencode($e->getMessage()));
        exit();
    }
    
} else {
    header("Location: my_bookings.php");
    exit();
}
?>