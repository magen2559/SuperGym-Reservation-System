<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'member') {
    header("Location: dashboard.php");
    exit();
}

$member_id = $_SESSION['user_id'];
$preselected_slot_id = isset($_GET['slot_id']) ? (int)$_GET['slot_id'] : 0;
$auto_select_message = '';
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_trainer'])) {
    $slot_id = $_POST['slot_id'];
    
    $stmt = $pdo->prepare("
        SELECT id FROM bookings 
        WHERE member_id = ? AND trainer_slot_id = ? AND status NOT IN ('cancelled', 'rejected')
    ");
    $stmt->execute([$member_id, $slot_id]);
    if ($stmt->fetch()) {
        $error_message = "You have already booked this time slot!";
    } else {
        $stmt = $pdo->prepare("SELECT is_available FROM trainer_slots WHERE id = ?");
        $stmt->execute([$slot_id]);
        $slot = $stmt->fetch();
        
        if ($slot && $slot['is_available'] == 1) {
            $stmt = $pdo->prepare("
                INSERT INTO bookings (member_id, booking_type, trainer_slot_id, status) 
                VALUES (?, 'trainer', ?, 'pending')
            ");
            if ($stmt->execute([$member_id, $slot_id])) {
                $stmt = $pdo->prepare("UPDATE trainer_slots SET is_available = 0 WHERE id = ?");
                $stmt->execute([$slot_id]);
                $success_message = "Trainer session booked successfully! Waiting for trainer approval.";
            } else {
                $error_message = "Booking failed. Please try again.";
            }
        } else {
            $error_message = "This time slot is no longer available.";
        }
    }
}

$stmt = $pdo->prepare("
    SELECT ts.*, 
           u.name as trainer_name, 
           t.specialty,
           t.bio,
           (SELECT COUNT(*) FROM bookings 
            WHERE trainer_slot_id = ts.id 
            AND member_id = ? 
            AND status NOT IN ('cancelled', 'rejected')) as user_booked,
           (SELECT COUNT(*) FROM bookings 
            WHERE trainer_slot_id = ts.id 
            AND status NOT IN ('cancelled', 'rejected')) as total_booked
    FROM trainer_slots ts
    JOIN trainers t ON t.id = ts.trainer_id
    JOIN users u ON u.id = t.user_id
    WHERE (ts.slot_date > CURDATE()) 
       OR (ts.slot_date = CURDATE() AND ts.end_time > CURTIME())
    ORDER BY u.name, ts.slot_date, ts.start_time
");
$stmt->execute([$member_id]);
$slots = $stmt->fetchAll();

$grouped_slots = [];
foreach ($slots as $slot) {
    $trainer_id = $slot['trainer_id'];
    $trainer_name = $slot['trainer_name'];
    if (!isset($grouped_slots[$trainer_id])) {
        $grouped_slots[$trainer_id] = [
            'trainer_name' => $trainer_name,
            'specialty' => $slot['specialty'],
            'bio' => $slot['bio'],
            'slots' => []
        ];
    }
    $grouped_slots[$trainer_id]['slots'][] = $slot;
}

if ($preselected_slot_id > 0) {
    $auto_select_message = "You have been redirected to book this trainer slot.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperGym - Book Personal Trainer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #111;
            color: #fff;
        }
        .navbar {
            background-color: #1a1a1a;
            border-bottom: 1px solid #333;
            padding: 6px;
        }
        .navbar .container {
            max-width: 100%;
            width: 100%;
            padding-left: 0;
            padding-right: 0;
            margin: 0;
        }
        .navbar-brand,
        .navbar-brand:hover,
        .navbar-brand:focus,
        .navbar-brand:active {
            font-weight: bold;
            font-size: 30px;
            color: #d6ff00 !important;
            text-decoration: none;
            padding-left: 15px;
        }
        .nav-link {
            color: #fff !important;
            font-weight: bold;
            text-transform: uppercase;
        }
        .nav-link:hover {
            color: #d6ff00 !important;
        }
        .btn-outline-custom {
            border: 2px solid #d6ff00;
            color: #d6ff00;
            font-weight: bold;
            padding: 8px 20px;
            border-radius: 10px;
            text-decoration: none;
            background-color: transparent;
        }
        .btn-outline-custom:hover {
            background-color: #d6ff00;
            color: #000;
        }
        .welcome-text {
            color: #ddd;
            font-size: 14px;
            margin-left: 20px;
            padding-left: 20px;
            border-left: 1px solid #555;
        }
        .trainer-group {
            margin-bottom: 40px;
            padding: 20px;
            background-color: #1a1a1a;
            border-radius: 15px;
            border: 1px solid #333;
        }
        .trainer-header {
            border-bottom: 1px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .trainer-header h3 {
            color: #d6ff00;
            margin-bottom: 5px;
        }
        .trainer-header .specialty {
            color: #aaa;
            font-size: 0.85rem;
        }
        .trainer-card {
            background-color: #EEF527;
            border: 1px solid #333;
            border-radius: 15px;
            transition: transform 0.3s;
            height: 100%;
        }
        .trainer-card:hover {
            transform: translateY(-5px);
            border-color: #fff;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }
        .trainer-card .mb-0 {
            color: #000;
        }
        .trainer-card .text-warning {
            color: #000 !important;
        }
        .trainer-card .btn-primary-custom {
            background-color: #000;
            color: #EEF527;
            font-weight: bold;
            border: none;
            padding: 8px 20px;
            border-radius: 10px;
            width: 100%;
        }
        .trainer-card .btn-primary-custom:hover {
            background-color: #333;
            color: #EEF527;
        }
        .trainer-card .btn-disabled {
            background-color: #999;
            color: #333;
            font-weight: bold;
            padding: 8px 20px;
            border-radius: 10px;
            border: none;
            cursor: not-allowed;
            width: 100%;
        }
        .trainer-card .btn-booked {
            background-color: #22c55e;
            color: #fff;
            font-weight: bold;
            padding: 8px 20px;
            border-radius: 10px;
            border: none;
            cursor: not-allowed;
            width: 100%;
        }
        .trainer-card .btn-ongoing {
            background-color: #f59e0b;
            color: #000;
            font-weight: bold;
            padding: 8px 20px;
            border-radius: 10px;
            border: none;
            cursor: not-allowed;
            width: 100%;
        }
        .trainer-card.highlight {
            border: 3px solid #000;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.5);
            transform: scale(1.02);
        }
        .slot-booked {
            opacity: 0.8;
        }
        footer {
            background-color: #0a0a0a;
            padding: 40px;
            text-align: center;
            border-top: 1px solid #222;
            margin-top: 50px;
        }
        h1 {
            color: #fff;
        }
        .text-muted {
            color: #aaa !important;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <div class="d-flex align-items-center">
            <a class="navbar-brand" href="index.php">SUPERGYM</a>
            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        </div>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon bg-white"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="member_dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="book_gym.php">Book Gym</a></li>
                <li class="nav-item"><a class="nav-link" href="book_trainer.php" style="color: #d6ff00 !important;">Book Trainer</a></li>
                <li class="nav-item"><a class="nav-link" href="my_bookings.php">My Bookings</a></li>
                <li class="nav-item"><a class="nav-link" href="booking_history.php">Booking History</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.php">My Account</a></li>
            </ul>
            <div class="ms-4">
                <a href="logout.php" class="btn btn-outline-custom">Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col">
            <h1>Book Personal Trainer</h1>
            <p class="text-muted">Select a trainer and time slot for personalized coaching</p>
        </div>
    </div>

    <?php if($auto_select_message): ?>
        <div class="alert alert-info"><?php echo $auto_select_message; ?></div>
    <?php endif; ?>

    <?php if($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if($error_message): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <?php if(count($slots) == 0): ?>
        <div class="alert alert-warning">No trainer slots available at the moment. Please check back later.</div>
    <?php else: ?>
        <?php foreach($grouped_slots as $trainer_id => $trainer_data): ?>
            <div class="trainer-group">
                <div class="trainer-header">
                    <h3>👨‍🏫 <?php echo htmlspecialchars($trainer_data['trainer_name']); ?></h3>
                    <p class="specialty mb-1"><?php echo htmlspecialchars($trainer_data['specialty'] ?? 'Fitness Coach'); ?></p>
                    <?php if(!empty($trainer_data['bio'])): ?>
                        <p class="text-muted small mb-0"><?php echo htmlspecialchars(substr($trainer_data['bio'], 0, 100)); ?>...</p>
                    <?php endif; ?>
                </div>
                <div class="row">
                    <?php foreach($trainer_data['slots'] as $slot): ?>
                        <?php
                        $is_user_booked = ($slot['user_booked'] > 0);
                        $is_full = ($slot['total_booked'] > 0 && $slot['is_available'] == 0);
                        $is_highlight = ($preselected_slot_id == $slot['id']);
                        
                        $isOngoing = false;
                        $today = date('Y-m-d');
                        $currentTime = date('H:i:s');
                        if ($slot['slot_date'] == $today) {
                            if ($slot['start_time'] <= $currentTime && $slot['end_time'] >= $currentTime) {
                                $isOngoing = true;
                            }
                        }
                        ?>
                        <div class="col-md-4 mb-4">
                            <div class="trainer-card p-4 <?php echo $is_user_booked ? 'slot-booked' : ''; ?> <?php echo $is_highlight ? 'highlight' : ''; ?>">
                                <div class="mb-2">
                                    <p class="mb-0">📅 <?php echo date('D, M j', strtotime($slot['slot_date'])); ?></p>
                                    <p class="fw-bold mb-0" style="color: #000;">⏰ <?php echo date('g:i A', strtotime($slot['start_time'])); ?> - <?php echo date('g:i A', strtotime($slot['end_time'])); ?></p>
                                </div>
                                
                                <?php if($isOngoing): ?>
                                    <div class="mt-3 text-center">
                                        <button class="btn-ongoing w-100" disabled>⏳ Ongoing</button>
                                    </div>
                                <?php elseif($is_user_booked): ?>
                                    <div class="mt-3 text-center">
                                        <button class="btn-booked w-100" disabled>✓ Already Booked</button>
                                    </div>
                                <?php elseif($is_full): ?>
                                    <div class="mt-3 text-center">
                                        <button class="btn-disabled w-100" disabled>✗ Slot Unavailable</button>
                                    </div>
                                <?php else: ?>
                                    <form method="POST" class="mt-3">
                                        <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                                        <button type="submit" name="book_trainer" class="btn btn-primary-custom">Book Now</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var highlighted = document.querySelector('.trainer-card.highlight');
        if (highlighted) {
            highlighted.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
</script>
</body>
</html>