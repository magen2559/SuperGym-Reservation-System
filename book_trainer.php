<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'member') {
    header("Location: dashboard.php");
    exit();
}

$success_message = '';
$error_message = '';


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['slot_id'])) {
    $slot_id = $_POST['slot_id'];
    $member_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("SELECT id FROM bookings WHERE member_id = ? AND trainer_slot_id = ? AND status NOT IN ('cancelled', 'rejected')");
    $stmt->execute([$member_id, $slot_id]);
    if ($stmt->fetch()) {
        $error_message = "You have already booked this time slot!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO bookings (member_id, booking_type, trainer_slot_id, status) VALUES (?, 'trainer', ?, 'pending')");
        if ($stmt->execute([$member_id, $slot_id])) {
            $stmt = $pdo->prepare("UPDATE trainer_slots SET is_available = FALSE WHERE id = ?");
            $stmt->execute([$slot_id]);
            $success_message = "Trainer session booked successfully! Waiting for trainer approval.";
        } else {
            $error_message = "Booking failed. Please try again.";
        }
    }
}

$stmt = $pdo->prepare("
    SELECT ts.*, u.name as trainer_name, t.specialty
    FROM trainer_slots ts
    JOIN trainers t ON t.id = ts.trainer_id
    JOIN users u ON u.id = t.user_id
    WHERE ts.slot_date >= CURDATE() AND ts.is_available = TRUE
    ORDER BY ts.slot_date, ts.start_time
");
$stmt->execute();
$slots = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>SuperGym - Book Personal Trainer</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&amp;family=Lexend:wght@700;800;900&amp;display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
</head>
<body class="bg-[#131313] text-white">

<nav class="bg-[#121212] border-b border-[#2A2A2A] px-6 py-4">
    <div class="container mx-auto flex justify-between items-center">
        <div class="text-2xl font-black italic text-[#d6ff00]">SUPERGYM</div>
        <div class="space-x-4">
            <a href="member_dashboard.php" class="text-gray-400 hover:text-white">Back to Dashboard</a>
            <a href="logout.php" class="text-gray-400 hover:text-white">Logout</a>
        </div>
    </div>
</nav>

<main class="container mx-auto px-6 py-8">
    <h1 class="text-3xl font-bold mb-2">Book Personal Trainer</h1>
    <p class="text-gray-400 mb-6">Select a trainer and time slot</p>

    <?php if($success_message): ?>
        <div class="bg-green-900/50 border border-green-500 text-green-200 px-4 py-3 rounded mb-4"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if($error_message): ?>
        <div class="bg-red-900/50 border border-red-500 text-red-200 px-4 py-3 rounded mb-4"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <?php if(count($slots) == 0): ?>
        <div class="bg-yellow-900/50 border border-yellow-500 text-yellow-200 px-4 py-3 rounded">
            No available trainer slots at the moment. Please check back later.
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach($slots as $slot): ?>
                <div class="bg-[#1a1a1a] border border-[#2A2A2A] rounded-xl p-4">
                    <div class="mb-3">
                        <p class="text-lg font-semibold text-[#d6ff00]"><?php echo htmlspecialchars($slot['trainer_name']); ?></p>
                        <p class="text-sm text-gray-400"><?php echo htmlspecialchars($slot['specialty']); ?></p>
                    </div>
                    <div class="border-t border-[#2A2A2A] pt-3 mt-2">
                        <p class="text-white"><?php echo date('D, M j', strtotime($slot['slot_date'])); ?></p>
                        <p class="text-[#d6ff00] font-bold"><?php echo date('g:i A', strtotime($slot['start_time'])); ?> - <?php echo date('g:i A', strtotime($slot['end_time'])); ?></p>
                    </div>
                    <form method="POST" class="mt-3">
                        <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                        <button type="submit" class="w-full bg-[#d6ff00] text-black font-bold py-2 rounded-lg hover:bg-[#c0e800] transition-colors">
                            Book This Trainer
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

</body>
</html>