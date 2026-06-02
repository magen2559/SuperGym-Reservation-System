<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'staff') {
    header("Location: dashboard.php");
    exit();
}

$success = '';
$error = '';
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_capacity'])) {
    $session_id = $_POST['session_id'];
    $new_capacity = (int)$_POST['max_capacity'];
    
    if ($new_capacity < 1) {
        $error = "Capacity must be at least 1.";
    } elseif ($new_capacity > 100) {
        $error = "Capacity cannot exceed 100 for safety reasons.";
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
           (gs.max_capacity - gs.current_bookings) as available_spots,
           ROUND((gs.current_bookings / gs.max_capacity) * 100, 1) as occupancy_rate
    FROM gym_sessions gs
    WHERE gs.session_date = ?
    ORDER BY gs.start_time
");
$stmt->execute([$selected_date]);
$sessions = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT 
        SUM(max_capacity) as total_capacity,
        SUM(current_bookings) as total_booked
    FROM gym_sessions 
    WHERE session_date = ?
");
$stmt->execute([date('Y-m-d')]);
$today_stats = $stmt->fetch();

$stmt->execute([date('Y-m-d', strtotime('+1 day'))]);
$tomorrow_stats = $stmt->fetch();

$stmt = $pdo->query("
    SELECT 
        SUM(max_capacity) as total_capacity,
        SUM(current_bookings) as total_booked,
        COUNT(*) as total_sessions
    FROM gym_sessions 
    WHERE session_date >= CURDATE() AND session_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
");
$week_stats = $stmt->fetch();
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
        .btn-active {
            background-color: #d6ff00;
            color: #000;
            font-weight: bold;
            padding: 8px 20px;
            border-radius: 10px;
            text-decoration: none;
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
            font-size: 0.8rem;
            text-transform: uppercase;
        }
        .capacity-card {
            background-color: #1a1a1a;
            border: 1px solid #333;
            border-radius: 15px;
            transition: transform 0.3s;
            height: 100%;
        }
        .capacity-card:hover {
            transform: translateY(-3px);
            border-color: #d6ff00;
        }
        .progress {
            height: 10px;
            border-radius: 5px;
            background-color: #333;
        }
        .progress-bar {
            background-color: #d6ff00;
            border-radius: 5px;
        }
        .capacity-current {
            font-size: 1.2rem;
            font-weight: bold;
        }
        .capacity-max input {
            width: 80px;
            display: inline-block;
            background-color: #2a2a2a;
            border: 1px solid #555;
            color: #fff;
            border-radius: 5px;
            padding: 5px;
            text-align: center;
        }
        .capacity-max input:focus {
            outline: none;
            border-color: #d6ff00;
        }
        .warning-low {
            color: #22c55e;
        }
        .warning-medium {
            color: #f59e0b;
        }
        .warning-high {
            color: #ef4444;
        }
        .warning-full {
            color: #ef4444;
            font-weight: bold;
        }
        .date-selector {
            background-color: #1a1a1a;
            border: 1px solid #333;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
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
                <li class="nav-item"><a class="nav-link" href="staff_dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_users.php">Users</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_trainers.php">Trainers</a></li>
                <li class="nav-item"><a class="nav-link" href="equipment.php">Equipment</a></li>
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
            <p class="text-muted">Monitor and control gym session occupancy to ensure safety</p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo $week_stats['total_sessions'] ?? 0; ?></div>
                <div class="stat-label">Sessions (Next 7 Days)</div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo $week_stats['total_capacity'] ?? 0; ?></div>
                <div class="stat-label">Total Capacity</div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo $week_stats['total_booked'] ?? 0; ?></div>
                <div class="stat-label">Total Booked</div>
            </div>
        </div>
    </div>

    <div class="date-selector">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Select Date</label>
                <input type="date" id="datePicker" class="form-control" value="<?php echo $selected_date; ?>">
            </div>
            <div class="col-md-2">
                <button id="goToDate" class="btn btn-primary-custom w-100">Go</button>
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-2 justify-content-end">
                    <a href="?date=<?php echo date('Y-m-d'); ?>" class="btn <?php echo $selected_date == date('Y-m-d') ? 'btn-active' : 'btn-outline-custom'; ?>">Today</a>
                    <a href="?date=<?php echo date('Y-m-d', strtotime('+1 day')); ?>" class="btn <?php echo $selected_date == date('Y-m-d', strtotime('+1 day')) ? 'btn-active' : 'btn-outline-custom'; ?>">Tomorrow</a>
                    <a href="?date=<?php echo date('Y-m-d', strtotime('+2 day')); ?>" class="btn <?php echo $selected_date == date('Y-m-d', strtotime('+2 day')) ? 'btn-active' : 'btn-outline-custom'; ?>">+2 Days</a>
                    <a href="?date=<?php echo date('Y-m-d', strtotime('+3 day')); ?>" class="btn <?php echo $selected_date == date('Y-m-d', strtotime('+3 day')) ? 'btn-active' : 'btn-outline-custom'; ?>">+3 Days</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo $today_stats['total_capacity'] ?? 0; ?> / <?php echo $today_stats['total_booked'] ?? 0; ?></div>
                <div class="stat-label">Today - Capacity / Booked</div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo $tomorrow_stats['total_capacity'] ?? 0; ?> / <?php echo $tomorrow_stats['total_booked'] ?? 0; ?></div>
                <div class="stat-label">Tomorrow - Capacity / Booked</div>
            </div>
        </div>
    </div>

    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <h3 class="mb-3">
        📅 Sessions for <?php echo date('D, M j, Y', strtotime($selected_date)); ?>
    </h3>

    <div class="row">
        <?php if(count($sessions) > 0): ?>
            <?php foreach($sessions as $session): ?>
                <?php 
                $available = $session['available_spots'];
                $occupancy = $session['occupancy_rate'];
                
                if ($occupancy < 50) {
                    $warning_class = 'warning-low';
                    $status_text = 'Good';
                } elseif ($occupancy < 80) {
                    $warning_class = 'warning-medium';
                    $status_text = 'Moderate';
                } elseif ($occupancy < 100) {
                    $warning_class = 'warning-high';
                    $status_text = 'Busy';
                } else {
                    $warning_class = 'warning-full';
                    $status_text = 'Full';
                }
                ?>
                <div class="col-md-6 mb-4">
                    <div class="capacity-card p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h4><?php echo date('D, M j', strtotime($session['session_date'])); ?></h4>
                                <p class="text-muted mb-0"><?php echo date('g:i A', strtotime($session['start_time'])); ?> - <?php echo date('g:i A', strtotime($session['end_time'])); ?></p>
                            </div>
                            <div class="text-end">
                                <span class="capacity-current <?php echo $warning_class; ?>">
                                    <?php echo $session['current_bookings']; ?>
                                </span>
                                <span class="text-muted">/</span>
                                <span class="text-muted"><?php echo $session['max_capacity']; ?></span>
                            </div>
                        </div>
                        
                        <div class="progress mb-2">
                            <div class="progress-bar" style="width: <?php echo $occupancy; ?>%;"></div>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <small class="text-muted"><?php echo $available; ?> spots left</small>
                            <small class="<?php echo $warning_class; ?>"><?php echo $status_text; ?> (<?php echo $occupancy; ?>%)</small>
                        </div>
                        
                        <form method="POST" class="capacity-max mt-2">
                            <input type="hidden" name="session_id" value="<?php echo $session['id']; ?>">
                            <div class="input-group">
                                <span class="input-group-text bg-dark text-muted border-secondary">Max Capacity</span>
                                <input type="number" name="max_capacity" value="<?php echo $session['max_capacity']; ?>" min="1" max="100" class="form-control" style="width: 80px;">
                                <button type="submit" name="update_capacity" class="btn btn-primary-custom">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col">
                <div class="alert alert-warning">No gym sessions found for <?php echo date('D, M j, Y', strtotime($selected_date)); ?>.</div>
            </div>
        <?php endif; ?>
    </div>

    <div class="card bg-dark border-secondary mt-4">
        <div class="card-header bg-dark border-secondary">
            <h5 class="mb-0">📋 Capacity Guidelines</h5>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-3">
                    <span class="warning-low">●</span> &lt;50% - Good
                </div>
                <div class="col-md-3">
                    <span class="warning-medium">●</span> 50-80% - Moderate
                </div>
                <div class="col-md-3">
                    <span class="warning-high">●</span> 80-100% - Busy
                </div>
                <div class="col-md-3">
                    <span class="warning-full">●</span> 100% - Full
                </div>
            </div>
            <hr class="bg-secondary">
            <div class="text-muted small">
                <p class="mb-0">⚠️ Note: When a session reaches 100% capacity, members cannot book that session.</p>
                <p class="mb-0">💡 Tip: Adjust capacity based on safety regulations and social distancing requirements.</p>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('goToDate').addEventListener('click', function() {
        var selectedDate = document.getElementById('datePicker').value;
        if (selectedDate) {
            window.location.href = '?date=' + selectedDate;
        }
    });
</script>
</body>
</html>