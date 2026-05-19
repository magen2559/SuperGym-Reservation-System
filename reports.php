<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'staff') {
    header("Location: dashboard.php");
    exit();
}

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM bookings
");
$stmt->execute();
$overall_stats = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT 
        DATE(b.booking_date) as date,
        COUNT(*) as total,
        SUM(CASE WHEN b.booking_type = 'gym' THEN 1 ELSE 0 END) as gym_bookings,
        SUM(CASE WHEN b.booking_type = 'trainer' THEN 1 ELSE 0 END) as trainer_bookings
    FROM bookings b
    WHERE DATE(b.booking_date) BETWEEN ? AND ?
    GROUP BY DATE(b.booking_date)
    ORDER BY DATE(b.booking_date) DESC
");
$stmt->execute([$start_date, $end_date]);
$daily_stats = $stmt->fetchAll();

$stmt = $pdo->query("SELECT COUNT(*) as total_members FROM users WHERE role = 'member'");
$member_count = $stmt->fetch()['total_members'];

$stmt = $pdo->query("SELECT COUNT(*) as total_trainers FROM users WHERE role = 'trainer'");
$trainer_count = $stmt->fetch()['total_trainers'];

$stmt = $pdo->prepare("
    SELECT 
        DATE(booking_date) as date,
        COUNT(*) as total
    FROM bookings
    WHERE booking_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(booking_date)
");
$stmt->execute();
$weekly_stats = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT 
        gs.session_date,
        gs.start_time,
        gs.end_time,
        COUNT(b.id) as booking_count,
        gs.max_capacity
    FROM gym_sessions gs
    LEFT JOIN bookings b ON gs.id = b.gym_session_id AND b.status NOT IN ('cancelled', 'rejected')
    WHERE gs.session_date BETWEEN ? AND ?
    GROUP BY gs.id
    ORDER BY booking_count DESC
    LIMIT 5
");
$stmt->execute([$start_date, $end_date]);
$popular_sessions = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT 
        u.name as trainer_name,
        COUNT(b.id) as total_bookings,
        SUM(CASE WHEN b.status = 'approved' THEN 1 ELSE 0 END) as approved
    FROM users u
    JOIN trainers t ON u.id = t.user_id
    LEFT JOIN trainer_slots ts ON t.id = ts.trainer_id
    LEFT JOIN bookings b ON ts.id = b.trainer_slot_id
    WHERE u.role = 'trainer'
    GROUP BY u.id
    ORDER BY total_bookings DESC
");
$stmt->execute();
$trainer_stats = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperGym - Reports</title>
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
        .stat-card {
            background-color: #1a1a1a;
            border: 1px solid #333;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #d6ff00;
        }
        .stat-label {
            color: #aaa;
            font-size: 12px;
            text-transform: uppercase;
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
        .report-card {
            background-color: #1a1a1a;
            border: 1px solid #333;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .report-title {
            color: #d6ff00;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #333;
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
        .form-control {
            background-color: #2a2a2a;
            border: 1px solid #333;
            color: #fff;
        }
        .form-control:focus {
            background-color: #2a2a2a;
            border-color: #d6ff00;
            color: #fff;
            box-shadow: none;
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
                <li class="nav-item"><a class="nav-link" href="staff_dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_users.php">Users</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_trainers.php">Trainers</a></li>
                <li class="nav-item"><a class="nav-link" href="gym_capacity.php">Gym Capacity</a></li>
                <li class="nav-item"><a class="nav-link" href="reports.php" style="color: #d6ff00 !important;">Reports</a></li>
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
            <h1>System Reports</h1>
            <p class="text-muted">View booking statistics, user data, and system analytics</p>
        </div>
    </div>

    <div class="report-card">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary-custom w-100">Apply Filter</button>
            </div>
        </form>
    </div>

    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo $overall_stats['total_bookings']; ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo $member_count; ?></div>
                <div class="stat-label">Total Members</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo $trainer_count; ?></div>
                <div class="stat-label">Total Trainers</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo $overall_stats['approved'] ?? 0; ?></div>
                <div class="stat-label">Approved Bookings</div>
            </div>
        </div>
    </div>

    <div class="report-card">
        <h4 class="report-title">Booking Status Distribution</h4>
        <div class="row">
            <div class="col-md-2 col-4 mb-2 text-center">
                <div class="stat-number" style="font-size: 1.5rem;"><?php echo $overall_stats['pending']; ?></div>
                <div class="text-muted small">Pending</div>
            </div>
            <div class="col-md-2 col-4 mb-2 text-center">
                <div class="stat-number" style="font-size: 1.5rem; color: #86efac;"><?php echo $overall_stats['approved']; ?></div>
                <div class="text-muted small">Approved</div>
            </div>
            <div class="col-md-2 col-4 mb-2 text-center">
                <div class="stat-number" style="font-size: 1.5rem; color: #fca5a5;"><?php echo $overall_stats['rejected']; ?></div>
                <div class="text-muted small">Rejected</div>
            </div>
            <div class="col-md-2 col-4 mb-2 text-center">
                <div class="stat-number" style="font-size: 1.5rem; color: #9ca3af;"><?php echo $overall_stats['cancelled']; ?></div>
                <div class="text-muted small">Cancelled</div>
            </div>
            <div class="col-md-2 col-4 mb-2 text-center">
                <div class="stat-number" style="font-size: 1.5rem; color: #60a5fa;"><?php echo $overall_stats['completed']; ?></div>
                <div class="text-muted small">Completed</div>
            </div>
        </div>
    </div>

    <div class="report-card">
        <h4 class="report-title">Daily Booking Report (<?php echo date('d M Y', strtotime($start_date)); ?> - <?php echo date('d M Y', strtotime($end_date)); ?>)</h4>
        <div class="table-responsive">
            <table class="table table-dark">
                <thead>
                    <tr><th>Date</th><th>Gym Bookings</th><th>Trainer Bookings</th><th>Total</th></tr>
                </thead>
                <tbody>
                    <?php if(count($daily_stats) > 0): ?>
                        <?php foreach($daily_stats as $stat): ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($stat['date'])); ?></td>
                                <td><?php echo $stat['gym_bookings']; ?></td>
                                <td><?php echo $stat['trainer_bookings']; ?></td>
                                <td><strong><?php echo $stat['total']; ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center text-muted">No data available for selected date range.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="report-card">
        <h4 class="report-title">Most Popular Gym Sessions</h4>
        <div class="table-responsive">
            <table class="table table-dark">
                <thead>
                    <tr><th>Date</th><th>Time</th><th>Bookings</th><th>Max Capacity</th><th>Utilization</th></tr>
                </thead>
                <tbody>
                    <?php if(count($popular_sessions) > 0): ?>
                        <?php foreach($popular_sessions as $session): ?>
                            <?php $utilization = ($session['max_capacity'] > 0) ? round(($session['booking_count'] / $session['max_capacity']) * 100) : 0; ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($session['session_date'])); ?></td>
                                <td><?php echo date('g:i A', strtotime($session['start_time'])); ?> - <?php echo date('g:i A', strtotime($session['end_time'])); ?></td>
                                <td><?php echo $session['booking_count']; ?></td>
                                <td><?php echo $session['max_capacity']; ?></td>
                                <td>
                                    <div class="progress-bar-custom" style="width: 100px; display: inline-block;">
                                        <div class="progress-fill" style="width: <?php echo $utilization; ?>%;"></div>
                                    </div>
                                    <span class="ms-2 small"><?php echo $utilization; ?>%</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted">No data available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="report-card">
        <h4 class="report-title">Trainer Booking Statistics</h4>
        <div class="table-responsive">
            <table class="table table-dark">
                <thead>
                    <tr><th>Trainer Name</th><th>Total Bookings</th><th>Approved Sessions</th><th>Acceptance Rate</th></tr>
                </thead>
                <tbody>
                    <?php if(count($trainer_stats) > 0): ?>
                        <?php foreach($trainer_stats as $trainer): ?>
                            <?php $rate = ($trainer['total_bookings'] > 0) ? round(($trainer['approved'] / $trainer['total_bookings']) * 100) : 0; ?>
                            <tr>
                                <td><?php echo htmlspecialchars($trainer['trainer_name']); ?></td>
                                <td><?php echo $trainer['total_bookings']; ?></td>
                                <td><?php echo $trainer['approved']; ?></td>
                                <td>
                                    <div class="progress-bar-custom" style="width: 100px; display: inline-block;">
                                        <div class="progress-fill" style="width: <?php echo $rate; ?>%;"></div>
                                    </div>
                                    <span class="ms-2 small"><?php echo $rate; ?>%</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center text-muted">No data available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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