<?php
require_once 'includes/session_check.php';
require_once 'includes/db.php';

if ($_SESSION['user_role'] != 'member') {
    header("Location: dashboard.php");
    exit();
}

$success_message = '';
$error_message = '';

// Handle booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['session_id'])) {
    $session_id = $_POST['session_id'];
    $member_id = $_SESSION['user_id'];
    
    // Check if already booked this session
    $stmt = $pdo->prepare("SELECT id FROM bookings WHERE member_id = ? AND gym_session_id = ? AND status NOT IN ('cancelled', 'rejected')");
    $stmt->execute([$member_id, $session_id]);
    if ($stmt->fetch()) {
        $error_message = "You have already booked this session!";
    } else {
        // Check capacity
        $stmt = $pdo->prepare("SELECT max_capacity, current_bookings FROM gym_sessions WHERE id = ?");
        $stmt->execute([$session_id]);
        $session = $stmt->fetch();
        
        if ($session && $session['current_bookings'] < $session['max_capacity']) {
            // Create booking
            $stmt = $pdo->prepare("INSERT INTO bookings (member_id, booking_type, gym_session_id, status) VALUES (?, 'gym', ?, 'pending')");
            if ($stmt->execute([$member_id, $session_id])) {
                // Update current bookings count
                $stmt = $pdo->prepare("UPDATE gym_sessions SET current_bookings = current_bookings + 1 WHERE id = ?");
                $stmt->execute([$session_id]);
                $success_message = "Gym session booked successfully! Waiting for approval.";
            } else {
                $error_message = "Booking failed. Please try again.";
            }
        } else {
            $error_message = "Session is fully booked!";
        }
    }
}

// Get available gym sessions
$stmt = $pdo->prepare("
    SELECT gs.*, 
           (gs.max_capacity - gs.current_bookings) as available_spots
    FROM gym_sessions gs
    WHERE gs.session_date >= CURDATE()
    ORDER BY gs.session_date, gs.start_time
");
$stmt->execute();
$sessions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>SuperGym - Book Gym Session</title>
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
    <h1 class="text-3xl font-bold mb-2">Book Gym Session</h1>
    <p class="text-gray-400 mb-6">Select a time slot to book your gym session</p>

    <?php if($success_message): ?>
        <div class="bg-green-900/50 border border-green-500 text-green-200 px-4 py-3 rounded mb-4"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if($error_message): ?>
        <div class="bg-red-900/50 border border-red-500 text-red-200 px-4 py-3 rounded mb-4"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach($sessions as $session): ?>
            <div class="bg-[#1a1a1a] border border-[#2A2A2A] rounded-xl p-4">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <p class="text-lg font-semibold"><?php echo date('D, M j', strtotime($session['session_date'])); ?></p>
                        <p class="text-[#d6ff00] font-bold"><?php echo date('g:i A', strtotime($session['start_time'])); ?> - <?php echo date('g:i A', strtotime($session['end_time'])); ?></p>
                    </div>
                    <span class="text-sm <?php echo $session['available_spots'] > 0 ? 'text-green-400' : 'text-red-400'; ?>">
                        <?php echo $session['available_spots']; ?> spots left
                    </span>
                </div>
                <form method="POST">
                    <input type="hidden" name="session_id" value="<?php echo $session['id']; ?>">
                    <button type="submit" class="w-full bg-[#d6ff00] text-black font-bold py-2 rounded-lg hover:bg-[#c0e800] transition-colors mt-2" <?php echo $session['available_spots'] <= 0 ? 'disabled' : ''; ?>>
                        <?php echo $session['available_spots'] > 0 ? 'Book Now' : 'Fully Booked'; ?>
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</main>

</body>
</html>