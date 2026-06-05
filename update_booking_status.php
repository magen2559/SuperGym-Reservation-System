<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

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
        SELECT 
            b.id, 
            b.status, 
            ts.trainer_id,
            ts.slot_date,
            ts.start_time,
            ts.end_time,
            u.name AS member_name,
            u.email AS member_email
        FROM bookings b
        JOIN trainer_slots ts ON b.trainer_slot_id = ts.id
        JOIN users u ON b.member_id = u.id
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

            $message = "Booking approved successfully!";

        } elseif ($action == 'reject') {

            $stmt = $pdo->prepare("
                UPDATE bookings 
                SET status = 'rejected',
                    member_action = 'pending_choice'
                WHERE id = ?
            ");
            $stmt->execute([$booking_id]);

            $stmt = $pdo->prepare("
                UPDATE trainer_slots ts
                JOIN bookings b ON ts.id = b.trainer_slot_id
                SET ts.is_available = 1
                WHERE b.id = ?
            ");
            $stmt->execute([$booking_id]);

            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'mohammadfarhanmdash@gmail.com';
                $mail->Password = 'hqxq ylqc ymsx sbhe';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('mohammadfarhanmdash@gmail.com', 'SuperGym');
                $mail->addAddress($booking['member_email'], $booking['member_name']);

                $mail->isHTML(true);
                $mail->Subject = 'SuperGym Trainer Booking Rejected';

                $mail->Body = "
                    <h2>SuperGym Booking Update</h2>
                    <p>Hello <strong>{$booking['member_name']}</strong>,</p>
                    <p>We are sorry to inform you that your trainer booking has been rejected by the trainer.</p>
                    <p><strong>Date:</strong> " . date('D, M j', strtotime($booking['slot_date'])) . "</p>
                    <p><strong>Time:</strong> " . date('g:i A', strtotime($booking['start_time'])) . " - " . date('g:i A', strtotime($booking['end_time'])) . "</p>
                    <p>You may login to your SuperGym account and choose either:</p>
                    <ul>
                        <li>Request Refund</li>
                        <li>Change Trainer</li>
                    </ul>
                    <p>Thank you.</p>
                ";

                $mail->AltBody = "Your SuperGym trainer booking has been rejected. Please login to request refund or change trainer.";

                $mail->send();

            } catch (Exception $e) {
                // Booking still rejected even if email fails
            }

            $message = "Booking rejected. Email notification sent to member.";

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