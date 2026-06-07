<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'member') {
    header("Location: dashboard.php");
    exit();
}

$member_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

$time_slots = [
    '08:00:00', '09:00:00', '10:00:00', '11:00:00', '12:00:00',
    '13:00:00', '14:00:00', '15:00:00', '16:00:00', '17:00:00',
    '18:00:00', '19:00:00', '20:00:00', '21:00:00', '22:00:00'
];

for ($i = 0; $i < 30; $i++) {
    $date = date('Y-m-d', strtotime("+$i days"));
    
    foreach ($time_slots as $start_time) {
        $end_time = date('H:i:s', strtotime($start_time . ' +1 hour'));
        
        $check = $pdo->prepare("
            SELECT id FROM gym_sessions 
            WHERE session_date = ? AND start_time = ? AND end_time = ?
        ");
        $check->execute([$date, $start_time, $end_time]);
        
        if (!$check->fetch()) {
            $insert = $pdo->prepare("
                INSERT INTO gym_sessions 
                (session_date, start_time, end_time, max_capacity, current_bookings)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insert->execute([$date, $start_time, $end_time, 20, 0]);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_session'])) {
    $session_id = $_POST['session_id'];
    
    $stmt = $pdo->prepare("
        SELECT id FROM bookings 
        WHERE member_id = ? AND gym_session_id = ? AND status NOT IN ('cancelled', 'rejected')
    ");
    $stmt->execute([$member_id, $session_id]);

    if ($stmt->fetch()) {
        $error_message = "You have already booked this session!";
    } else {
        $stmt = $pdo->prepare("SELECT max_capacity, current_bookings FROM gym_sessions WHERE id = ?");
        $stmt->execute([$session_id]);
        $session = $stmt->fetch();
        
        if ($session && $session['current_bookings'] < $session['max_capacity']) {
            $stmt = $pdo->prepare("
                INSERT INTO bookings (member_id, booking_type, gym_session_id, status) 
                VALUES (?, 'gym', ?, 'pending')
            ");

            if ($stmt->execute([$member_id, $session_id])) {
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

$selected_date = $_GET['date'] ?? date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT gs.*, 
           (gs.max_capacity - gs.current_bookings) as available_spots
    FROM gym_sessions gs
    WHERE gs.session_date = ?
      AND (
          gs.session_date > CURDATE()
          OR (gs.session_date = CURDATE() AND gs.start_time > CURTIME())
      )
    ORDER BY gs.start_time
");
$stmt->execute([$selected_date]);
$sessions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperGym - Book Gym Session</title>
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
        .btn-primary-custom {
            background-color: #d6ff00;
            color: #000;
            font-weight: bold;
            border: none;
            padding: 8px 20px;
            border-radius: 10px;
        }
        .btn-primary-custom:hover {
            background-color: #c0e800;
            color: #000;
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
        .btn-disabled {
            background-color: #444;
            color: #aaa;
            font-weight: bold;
            padding: 8px 20px;
            border-radius: 10px;
            border: none;
            cursor: not-allowed;
        }
        .welcome-text {
            color: #ddd;
            font-size: 14px;
            margin-left: 20px;
            padding-left: 20px;
            border-left: 1px solid #555;
        }
        .session-card {
            background-color: #1a1a1a;
            border: 1px solid #333;
            border-radius: 15px;
            transition: transform 0.3s;
            height: 100%;
        }
        .session-card:hover {
            transform: translateY(-5px);
            border-color: #d6ff00;
        }
        .available-spots {
            font-size: 12px;
            padding: 3px 10px;
            border-radius: 20px;
            display: inline-block;
        }
        .spots-low {
            background-color: #fde047;
            color: #000;
        }
        .spots-medium {
            background-color: #22c55e;
            color: #000;
        }
        .spots-high {
            background-color: #d6ff00;
            color: #000;
        }
        .spots-full {
            background-color: #ef4444;
            color: #fff;
        }
        .btn-ongoing {
            background-color: #6b7280;
            color: #fff;
            font-weight: bold;
            padding: 8px 20px;
            border-radius: 10px;
            border: none;
            cursor: not-allowed;
            width: 100%;
            margin-top: 10px;
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
                <li class="nav-item"><a class="nav-link" href="book_gym.php" style="color: #d6ff00 !important;">Book Gym</a></li>
                <li class="nav-item"><a class="nav-link" href="book_trainer.php">Book Trainer</a></li>
                <li class="nav-item"><a class="nav-link" href="cart.php">Cart</a></li>
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
            <h1>Book Gym Session</h1>
            <p class="text-muted">Select a time slot to book your gym session</p>
            
            <form method="GET" class="mb-4">
                <label class="form-label">Select Date</label>
                <div class="d-flex gap-2">
                    <input type="date" name="date" class="form-control"
                           value="<?php echo htmlspecialchars($selected_date); ?>"
                           min="<?php echo date('Y-m-d'); ?>">
                    <button type="submit" class="btn btn-primary-custom">Search</button>
                </div>
            </form>
        </div>
    </div>

    <?php if($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if($error_message): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <?php if(count($sessions) == 0): ?>
        <div class="alert alert-warning">No available gym sessions at the moment. Please check back later.</div>
    <?php else: ?>
        <div class="row">
            <?php foreach($sessions as $session): ?>
                <?php 
                $available = $session['available_spots'];
                
                $isOngoing = false;
                $today = date('Y-m-d');
                $currentTime = date('H:i:s');

                if ($session['session_date'] == $today) {
                    if ($session['start_time'] <= $currentTime && $session['end_time'] >= $currentTime) {
                        $isOngoing = true;
                    }
                }
                
                if ($available <= 0) {
                    $spots_class = 'spots-full';
                    $spots_text = 'Fully Booked';
                } elseif ($available <= 5) {
                    $spots_class = 'spots-low';
                    $spots_text = $available . ' spots left (Low)';
                } elseif ($available <= 10) {
                    $spots_class = 'spots-medium';
                    $spots_text = $available . ' spots left';
                } else {
                    $spots_class = 'spots-high';
                    $spots_text = $available . ' spots left';
                }
                ?>
                <div class="col-md-4 mb-4">
                    <div class="session-card p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h4><?php echo date('D, M j', strtotime($session['session_date'])); ?></h4>
                                <p class="text-muted mb-0"><?php echo date('g:i A', strtotime($session['start_time'])); ?> - <?php echo date('g:i A', strtotime($session['end_time'])); ?></p>
                            </div>
                            <span class="available-spots <?php echo $spots_class; ?>"><?php echo $spots_text; ?></span>
                        </div>
                        
                        <?php if($isOngoing): ?>
                            <button class="btn-ongoing w-100 mt-2" disabled>⏳ Ongoing</button>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="session_id" value="<?php echo $session['id']; ?>">
                                <?php if($available > 0): ?>
                                    <button type="submit" name="book_session" class="btn btn-primary-custom w-100 mt-2">Book Now</button>
                                <?php else: ?>
                                    <button type="button" class="btn-disabled w-100 mt-2" disabled>Fully Booked</button>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>