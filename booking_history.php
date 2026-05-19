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
      AND (b.status IN ('cancelled', 'rejected', 'completed') 
           OR (CASE 
                   WHEN b.booking_type = 'gym' THEN gs.session_date
                   WHEN b.booking_type = 'trainer' THEN ts.slot_date
               END) < CURDATE())
    ORDER BY booking_date DESC
");
$stmt->execute([$user_id]);
$past_bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperGym - Booking History</title>
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
        .status-completed { color: #60a5fa; }
        .history-card {
            background-color: #1a1a1a;
            border: 1px solid #333;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
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
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #aaa;
        }
        .badge-custom {
            background-color: #d6ff00;
            color: #000;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            margin-left: 10px;
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
                <li class="nav-item"><a class="nav-link" href="book_trainer.php">Book Trainer</a></li>
                <li class="nav-item"><a class="nav-link" href="my_bookings.php">My Bookings</a></li>
                <li class="nav-item"><a class="nav-link" href="booking_history.php" style="color: #d6ff00 !important;">Booking History</a></li>
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
            <h1>Booking History</h1>
            <p class="text-muted">View your past gym sessions and trainer appointments</p>
        </div>
    </div>

    <div class="history-card">
        <?php if(count($past_bookings) > 0): ?>
            <div class="table-responsive">
                <table class="table table-dark">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Trainer</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($past_bookings as $booking): ?>
                            <tr>
                                <td><?php echo $booking['booking_type'] == 'gym' ? '🏋️ Gym Session' : '👨‍🏫 Personal Trainer'; ?></td>
                                <td><?php echo htmlspecialchars($booking['booking_date'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($booking['booking_time'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($booking['trainer_name'] ?? '-'); ?></td>
                                <td>
                                    <span class="status-<?php echo $booking['status']; ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>No booking history found.</p>
                <a href="book_gym.php" class="btn btn-primary-custom mt-2">Book Your First Session</a>
            </div>
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