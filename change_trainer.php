<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'member') {
    header("Location: dashboard.php");
    exit();
}

$member_id = $_SESSION['user_id'];
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
$error_message = '';

$stmt = $pdo->prepare("
    SELECT *
    FROM bookings
    WHERE id = ?
    AND member_id = ?
    AND booking_type = 'trainer'
    AND status = 'rejected'
    AND payment_status = 'paid'
    AND member_action = 'pending_choice'
");
$stmt->execute([$booking_id, $member_id]);
$old_booking = $stmt->fetch();

if (!$old_booking) {
    header("Location: my_bookings.php?error=Invalid change trainer request");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['choose_slot'])) {
    $new_slot_id = (int)$_POST['slot_id'];

    $stmt = $pdo->prepare("
        SELECT *
        FROM trainer_slots
        WHERE id = ?
        AND is_available = 1
        AND (
            slot_date > CURDATE()
            OR (slot_date = CURDATE() AND end_time > CURTIME())
        )
    ");
    $stmt->execute([$new_slot_id]);
    $slot = $stmt->fetch();

    if (!$slot) {
        $error_message = "This slot is no longer available.";
    } else {
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("
                UPDATE bookings
                SET trainer_slot_id = ?,
                    status = 'pending',
                    member_action = NULL,
                    refund_status = 'not_requested',
                    refund_reason = NULL
                WHERE id = ?
            ");
            $stmt->execute([$new_slot_id, $booking_id]);

            $stmt = $pdo->prepare("
                UPDATE trainer_slots
                SET is_available = 0
                WHERE id = ?
            ");
            $stmt->execute([$new_slot_id]);

            $pdo->commit();

            header("Location: my_bookings.php?success=Trainer changed successfully. No extra payment needed. Waiting for trainer approval.");
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "Failed to change trainer.";
        }
    }
}

$stmt = $pdo->prepare("
    SELECT ts.*, u.name AS trainer_name, t.specialty, t.bio
    FROM trainer_slots ts
    JOIN trainers t ON t.trainer_id = ts.trainer_id
    JOIN users u ON u.id = t.user_id
    WHERE ts.is_available = 1
    AND (
        ts.slot_date > CURDATE()
        OR (ts.slot_date = CURDATE() AND ts.end_time > CURTIME())
    )
    ORDER BY u.name, ts.slot_date, ts.start_time
");
$stmt->execute();
$slots = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>SuperGym - Change Trainer</title>
    <style>
        body { background:#111; color:#fff; font-family:Arial; padding:40px; }
        h1 { color:#d6ff00; }
        .card { background:#eef527; color:#000; padding:20px; border-radius:15px; margin-bottom:20px; }
        .btn { background:#000; color:#eef527; padding:10px 18px; border:none; border-radius:8px; font-weight:bold; cursor:pointer; }
        .back { color:#d6ff00; text-decoration:none; display:inline-block; margin-bottom:20px; }
        .alert { background:#ef4444; padding:12px; border-radius:8px; margin-bottom:20px; }
    </style>
</head>
<body>

<a href="my_bookings.php" class="back">← Back to My Bookings</a>

<h1>Change Trainer</h1>
<p>Select a new trainer slot. You do not need to pay again.</p>

<?php if($error_message): ?>
    <div class="alert"><?php echo htmlspecialchars($error_message); ?></div>
<?php endif; ?>

<?php if(count($slots) == 0): ?>
    <p>No trainer slots available.</p>
<?php else: ?>
    <?php foreach($slots as $slot): ?>
        <div class="card">
            <h3><?php echo htmlspecialchars($slot['trainer_name']); ?></h3>
            <p><?php echo htmlspecialchars($slot['specialty'] ?? 'Fitness Coach'); ?></p>
            <p>
                📅 <?php echo date('D, M j', strtotime($slot['slot_date'])); ?><br>
                ⏰ <?php echo date('g:i A', strtotime($slot['start_time'])); ?> -
                <?php echo date('g:i A', strtotime($slot['end_time'])); ?>
            </p>

            <form method="POST">
                <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                <button type="submit" name="choose_slot" class="btn">Choose This Trainer</button>
            </form>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>