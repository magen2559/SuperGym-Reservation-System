<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'trainer') {
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id FROM trainers WHERE user_id = ?");
$stmt->execute([$user_id]);
$trainer = $stmt->fetch();

if(!$trainer) {
    $stmt = $pdo->prepare("INSERT INTO trainers (user_id, specialty, bio) VALUES (?, 'Fitness Coach', 'Professional trainer')");
    $stmt->execute([$user_id]);
    $trainer_id = $pdo->lastInsertId();
} else {
    $trainer_id = $trainer['id'];
}

$stmt = $pdo->prepare("
    SELECT b.*, u.name as member_name, ts.slot_date, ts.start_time, ts.end_time
    FROM bookings b
    JOIN trainer_slots ts ON b.trainer_slot_id = ts.id
    JOIN users u ON b.member_id = u.id
    WHERE ts.trainer_id = ? AND b.status = 'pending'
    ORDER BY ts.slot_date, ts.start_time
");
$stmt->execute([$trainer_id]);
$pending_bookings = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT b.*, u.name as member_name, ts.slot_date, ts.start_time, ts.end_time
    FROM bookings b
    JOIN trainer_slots ts ON b.trainer_slot_id = ts.id
    JOIN users u ON b.member_id = u.id
    WHERE ts.trainer_id = ? AND b.status = 'approved'
    ORDER BY ts.slot_date, ts.start_time
    LIMIT 10
");
$stmt->execute([$trainer_id]);
$approved_bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperGym - Trainer Dashboard</title>
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
            padding: 5px 15px;
            border-radius: 5px;
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
        .btn-approve {
            background-color: #22c55e;
            color: #fff;
            border: none;
            padding: 5px 15px;
            border-radius: 5px;
            margin-right: 5px;
        }
        .btn-reject {
            background-color: #ef4444;
            color: #fff;
            border: none;
            padding: 5px 15px;
            border-radius: 5px;
        }
        .badge-pending {
            background-color: #fde047;
            color: #000;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 12px;
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
        .card {
            background-color: #1a1a1a;
            border: 1px solid #333;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        .card-header {
            background-color: #222;
            border-bottom: 1px solid #333;
        }
        .card-header h3 {
            color: #fff;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <div class="d-flex align-items-center">
            <a class="navbar-brand" href="index.php">SUPERGYM</a>
            <span class="welcome-text">Welcome, Coach <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        </div>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon bg-white"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="trainer_dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="trainer_schedule.php">My Schedule</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.php">My Account</a></li>
            </ul>
            <div class="ms-4">
                <a href="logout.php" class="btn btn-outline-custom">Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="container my-5">
    <h1>Trainer Dashboard</h1>
    <p class="text-muted mb-4">Manage your booking requests and schedule</p>

    <!-- Pending Requests -->
    <div class="card">
        <div class="card-header">
            <h3 class="mb-0">📋 Pending Requests <span class="badge-pending ms-2"><?php echo count($pending_bookings); ?></span></h3>
        </div>
        <div class="card-body p-0">
            <?php if(count($pending_bookings) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-dark mb-0">
                        <thead>
                            <tr><th>Member</th><th>Date</th><th>Time</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($pending_bookings as $booking): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($booking['member_name']); ?></td>
                                    <td><?php echo date('D, M j', strtotime($booking['slot_date'])); ?></td>
                                    <td><?php echo date('g:i A', strtotime($booking['start_time'])); ?> - <?php echo date('g:i A', strtotime($booking['end_time'])); ?></td>
                                    <td>
                                        <form action="update_booking_status.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn-approve">Accept</button>
                                        </form>
                                        <form action="update_booking_status.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn-reject">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted p-4">No pending requests.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Upcoming Sessions -->
    <div class="card">
        <div class="card-header">
            <h3 class="mb-0">✅ Upcoming Sessions</h3>
        </div>
        <div class="card-body p-0">
            <?php if(count($approved_bookings) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-dark mb-0">
                        <thead><tr><th>Member</th><th>Date</th><th>Time</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach($approved_bookings as $booking): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($booking['member_name']); ?></td>
                                    <td><?php echo date('D, M j', strtotime($booking['slot_date'])); ?></td>
                                    <td><?php echo date('g:i A', strtotime($booking['start_time'])); ?> - <?php echo date('g:i A', strtotime($booking['end_time'])); ?></td>
                                    <td><span class="text-success">Approved</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted p-4">No upcoming sessions.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer>
    <div class="container">
        <div style="font-size: 1.8rem; font-weight: bold; font-style: italic; color: #d6ff00; margin-bottom: 15px;">SUPERGYM</div>
        <p>© 2024 SuperGym Booking System. All Rights Reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>