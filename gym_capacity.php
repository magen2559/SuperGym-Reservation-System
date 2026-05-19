<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'staff') {
    header("Location: dashboard.php");
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_capacity'])) {
    $session_id = $_POST['session_id'];
    $new_capacity = (int)$_POST['max_capacity'];
    
    if ($new_capacity < 1) {
        $error = "Capacity must be at least 1.";
    } else {
        $stmt = $pdo->prepare("UPDATE gym_sessions SET max_capacity = ? WHERE id = ?");
        if ($stmt->execute([$new_capacity, $session_id])) {
            $success = "Gym capacity updated successfully!";
        } else {
            $error = "Failed to update capacity.";
        }
    }
}

$stmt = $pdo->prepare("
    SELECT gs.*, 
           (gs.max_capacity - gs.current_bookings) as available_spots
    FROM gym_sessions gs
    WHERE gs.session_date >= CURDATE()
    ORDER BY gs.session_date, gs.start_time
");
$stmt->execute();
$sessions = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT 
        SUM(max_capacity) as total_capacity,
        SUM(current_bookings) as total_booked
    FROM gym_sessions 
    WHERE session_date >= CURDATE()
");
$stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperGym - Gym Capacity Management</title>
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
        .capacity-card {
            background-color: #1a1a1a;
            border: 1px solid #333;
            border-radius: 15px;
            transition: transform 0.3s;
        }
        .capacity-card:hover {
            transform: translateY(-3px);
            border-color: #d6ff00;
        }
        .capacity-current {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .capacity-max input {
            width: 80px;
            display: inline-block;
            background-color: #2a2a2a;
            border: 1px solid #333;
            color: #fff;
            border-radius: 5px;
            padding: 5px;
            text-align: center;
        }
        .capacity-max input:focus {
            outline: none;
            border-color: #d6ff00;
        }
        .progress-bar-custom {
            height: 8px;
            border-radius: 4px;
            background-color: #333;
        }
        .progress-fill {
            height: 8px;
            border-radius: 4px;
            background-color: #d6ff00;
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
        .table-dark {
            background-color: #1a1a1a;
        }
        .table-dark td, .table-dark th {
            border-color: #333;
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
                <li class="nav-item"><a class="nav-link" href="gym_capacity.php" style="color: #d6ff00 !important;">Gym Capacity</a></li>
                <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
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
            <h1>Gym Capacity Management</h1>
            <p class="text-muted">View and update maximum capacity for each gym session</p>
        </div>
    </div>

    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row mb-5">
        <div class="col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_capacity'] ?? 0; ?></div>
                <div class="text-muted">Total Available Spots</div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_booked'] ?? 0; ?></div>
                <div class="text-muted">Total Booked Spots</div>
            </div>
        </div>
    </div>

    <div class="row">
        <?php if(count($sessions) > 0): ?>
            <?php foreach($sessions as $session): ?>
                <?php 
                $percentage = ($session['max_capacity'] > 0) ? ($session['current_bookings'] / $session['max_capacity']) * 100 : 0;
                $percentage = round($percentage);
                ?>
                <div class="col-md-4 mb-4">
                    <div class="capacity-card p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h4><?php echo date('D, M j', strtotime($session['session_date'])); ?></h4>
                                <p class="text-muted mb-0"><?php echo date('g:i A', strtotime($session['start_time'])); ?> - <?php echo date('g:i A', strtotime($session['end_time'])); ?></p>
                            </div>
                            <div class="capacity-current">
                                <span class="text-warning"><?php echo $session['current_bookings']; ?></span>
                                <span class="text-muted">/</span>
                                <span class="text-muted"><?php echo $session['max_capacity']; ?></span>
                            </div>
                        </div>
                        
                        <div class="progress-bar-custom mb-3">
                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%;"></div>
                        </div>
                        <p class="small text-muted text-end"><?php echo $percentage; ?>% full</p>
                        
                        <form method="POST" class="capacity-max">
                            <input type="hidden" name="session_id" value="<?php echo $session['id']; ?>">
                            <div class="input-group">
                                <span class="input-group-text bg-dark text-muted border-secondary">Max Capacity</span>
                                <input type="number" name="max_capacity" value="<?php echo $session['max_capacity']; ?>" min="1" max="100" class="form-control">
                                <button type="submit" name="update_capacity" class="btn btn-primary-custom">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col">
                <div class="alert alert-warning">No upcoming gym sessions found.</div>
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