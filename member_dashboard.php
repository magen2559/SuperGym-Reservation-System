<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'member') {
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT b.*, 
           CASE 
               WHEN b.booking_type = 'gym' THEN gs.session_date
               WHEN b.booking_type = 'trainer' THEN ts.slot_date
           END as booking_date,
           CASE 
               WHEN b.booking_type = 'gym' THEN CONCAT(gs.start_time, ' - ', gs.end_time)
               WHEN b.booking_type = 'trainer' THEN CONCAT(ts.start_time, ' - ', ts.end_time)
           END as booking_time,
           CASE 
               WHEN b.booking_type = 'trainer' THEN u.name
               ELSE NULL
           END as trainer_name
    FROM bookings b
    LEFT JOIN gym_sessions gs ON b.gym_session_id = gs.id
    LEFT JOIN trainer_slots ts ON b.trainer_slot_id = ts.id
    LEFT JOIN trainers t ON ts.trainer_id = t.id
    LEFT JOIN users u ON t.user_id = u.id
    WHERE b.member_id = ?
    ORDER BY booking_date DESC
    LIMIT 10
");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperGym - Member Dashboard</title>
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
        .welcome-text {
            color: #ddd;
            font-size: 14px;
            margin-left: 20px;
            padding-left: 20px;
            border-left: 1px solid #555;
        }
        .card {
            background-color: #EEF527; 
            border: 1px solid #333;
            border-radius: 15px;
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
            border-color: #fff;
        }
        .card h3 {
            color: #000; 
        }
        .card h4 {
            color: #000;  
        }
        .card p {
            color: #333; 
        }
        .card.p-4 h3 {
            color: #000;
        }
        .table-dark {
            background-color: #1a1a1a;
        }
        .table-dark td, .table-dark th {
            border-color: #333;
            color: #ddd;
        }
        .table-dark th {
            color: #d6ff00;
        }
        .status-approved { color: #86efac; }
        .status-pending { color: #fde047; }
        .status-rejected { color: #fca5a5; }
        .status-cancelled { color: #9ca3af; }
        footer {
            background-color: #0a0a0a;
            padding: 40px;
            text-align: center;
            border-top: 1px solid #222;
            margin-top: 50px;
        }
        footer div {
            color: #666;
        }
        .text-muted {
            color: #aaa !important;
        }
        h1 {
            color: #fff;
        }
        .card .text-muted {
            color: #555 !important;
        }
        .card .btn-primary-custom {
            background-color: #000;
            color: #EEF527;
        }
        .card .btn-primary-custom:hover {
            background-color: #333;
            color: #EEF527;
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
                <li class="nav-item"><a class="nav-link" href="member_dashboard.php" style="color: #d6ff00 !important;">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="book_gym.php">Book Gym</a></li>
                <li class="nav-item"><a class="nav-link" href="book_trainer.php">Book Trainer</a></li>
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
            <h1>Member Dashboard</h1>
            <p class="text-muted">Manage your gym sessions and trainer bookings</p>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-md-4 mb-3">
            <div class="card p-4 text-center h-100">
                <div style="font-size: 3rem;">🏋️</div>
                <h4 class="mt-2">Book Gym Session</h4>
                <p class="text-muted">Reserve your gym time</p>
                <a href="book_gym.php" class="btn btn-primary-custom mt-auto">Book Now</a>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card p-4 text-center h-100">
                <div style="font-size: 3rem;">👨‍🏫</div>
                <h4 class="mt-2">Book Personal Trainer</h4>
                <p class="text-muted">Train with professionals</p>
                <a href="book_trainer.php" class="btn btn-primary-custom mt-auto">Book Now</a>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card p-4 text-center h-100">
                <div style="font-size: 3rem;">📅</div>
                <h4 class="mt-2">My Bookings</h4>
                <p class="text-muted">View your schedule</p>
                <a href="member_dashboard.php" class="btn btn-primary-custom mt-auto">View All</a>
            </div>
        </div>
    </div>

    
    <div class="card p-4">
        <h3 class="mb-3">Recent Bookings</h3>
        <?php if(count($bookings) > 0): ?>
            <div class="table-responsive">
                <table class="table table-dark">
                    <thead>
                        <tr><th>Type</th><th>Date</th><th>Time</th><th>Trainer</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($bookings as $booking): ?>
                            <tr>
                                <td data-label="Type"><?php echo $booking['booking_type'] == 'gym' ? '🏋️ Gym' : '👨‍🏫 Trainer'; ?></td>
                                <td data-label="Date"><?php echo htmlspecialchars($booking['booking_date'] ?? 'N/A'); ?></td>
                                <td data-label="Time"><?php echo htmlspecialchars($booking['booking_time'] ?? 'N/A'); ?></td>
                                <td data-label="Trainer"><?php echo htmlspecialchars($booking['trainer_name'] ?? '-'); ?></td>
                                <td data-label="Status"><span class="status-<?php echo $booking['status']; ?>"><?php echo ucfirst($booking['status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted">No bookings yet. Book your first session!</p>
        <?php endif; ?>
    </div>
</div>

<footer>
    <div class="container">
        <div style="font-size: 1.8rem; font-weight: bold; font-style: italic; color: #d6ff00; margin-bottom: 15px;">SUPERGYM</div>
        <p>© SuperGym Booking System. All Rights Reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>