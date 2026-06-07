<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'staff') {
    header("Location: dashboard.php");
    exit();
}

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'member'");
$total_members = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'trainer'");
$total_trainers = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM bookings");
$total_bookings = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'");
$pending_bookings = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM bookings WHERE refund_status = 'requested'");
$pending_refunds = $stmt->fetch()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperGym - Staff Dashboard</title>

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

        .welcome-text {
            color: #ddd;
            font-size: 14px;
            margin-left: 20px;
            padding-left: 20px;
            border-left: 1px solid #555;
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

        .stat-card {
            background-color: #ffffff;
            border: 2px solid #d6ff00;
            border-radius: 15px;
            padding: 12px 8px;
            text-align: center;
            transition: all 0.3s ease;
            height: auto;
            min-height: 100px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: #EEF527;
            box-shadow: 0 8px 20px rgba(214, 255, 0, 0.15);
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            color: #000;
            margin-bottom: 5px;
        }

        .stat-card .text-muted {
            color: #555 !important;
            font-weight: 600;
            font-size: 14px;
            letter-spacing: 0.5px;
        }

        .menu-card {
            background-color: #EEF527;
            border: none;
            border-radius: 20px;
            padding: 30px 20px;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s ease;
            display: block;
            height: 100%;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .menu-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(214, 255, 0, 0.3);
            background-color: #f0f92e;
        }

        .menu-card h3 {
            color: #000;
            margin-bottom: 12px;
            font-weight: bold;
        }

        .menu-card .menu-icon {
            font-size: 3.2rem;
            margin-bottom: 15px;
            display: inline-block;
        }

        .menu-card .text-muted {
            color: #333 !important;
            font-size: 13px;
            font-weight: 500;
        }

        .menu-card:hover .menu-icon {
            transform: scale(1.05);
            transition: transform 0.2s;
        }

        .section-divider {
            margin: 40px 0 25px 0;
            position: relative;
            text-align: center;
        }

        .section-divider h2 {
            display: inline-block;
            background-color: #1a1a1a;
            padding: 8px 25px;
            border-radius: 50px;
            font-size: 20px;
            color: #d6ff00;
            border: 1px solid #333;
            letter-spacing: 2px;
        }

        .section-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, #d6ff00, #d6ff00, transparent);
            z-index: -1;
        }

        h1 {
            color: #fff;
            font-weight: bold;
        }

        .badge-refund {
            background-color: #f59e0b;
            color: #000;
            font-size: 12px;
            padding: 3px 8px;
            border-radius: 20px;
            margin-left: 8px;
        }

        footer {
            background-color: #0a0a0a;
            padding: 40px;
            text-align: center;
            border-top: 1px solid #222;
            margin-top: 50px;
        }

        footer p {
            color: #666;
        }

        .stats-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 30px;
        }
        
        .stats-row .stat-col {
            flex: 0 0 calc(28% - 1.8rem);
            max-width: calc(28% - 1.8rem);
        }

        .menu-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1.8rem;
        }
        
        .menu-row .menu-col {
            flex: 0 0 calc(30% - 1.8rem);
            max-width: calc(30% - 1.8rem);
        }
        
        @media (max-width: 768px) {
            .stats-row .stat-col,
            .menu-row .menu-col {
                flex: 0 0 calc(100% - 1rem);
                max-width: calc(100% - 1rem);
            }
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
                <li class="nav-item"><a class="nav-link" href="staff_dashboard.php" style="color: #d6ff00 !important;">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_users.php">Users</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_trainers.php">Trainers</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_bookings.php">Bookings</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_refunds.php">Refunds</a></li>
                <li class="nav-item"><a class="nav-link" href="equipment.php">Equipment</a></li>
                <li class="nav-item"><a class="nav-link" href="gym_capacity.php">Gym Capacity</a></li>
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
            <h1>Staff Dashboard</h1>
            <p class="text-white">Manage gym operations, users, trainers, and reports</p>
        </div>
    </div>

    <div class="section-divider">
        <h2>📊 STATISTICS</h2>
    </div>

    <div class="stats-row">
        <div class="stat-col">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_members; ?></div>
                <div class="text-muted">Total Members</div>
            </div>
        </div>
        <div class="stat-col">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_trainers; ?></div>
                <div class="text-muted">Total Trainers</div>
            </div>
        </div>
        <div class="stat-col">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_bookings; ?></div>
                <div class="text-muted">Total Bookings</div>
            </div>
        </div>
    </div>

    <div class="stats-row justify-content-center">
        <div class="stat-col">
            <a href="manage_bookings.php" style="text-decoration: none; display: block;">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $pending_bookings; ?></div>
                    <div class="text-muted">Pending Requests</div>
                </div>
            </a>
        </div>
        <div class="stat-col">
            <a href="manage_refunds.php" style="text-decoration: none; display: block;">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $pending_refunds; ?></div>
                    <div class="text-muted">Pending Refunds</div>
                </div>
            </a>
        </div>
    </div>

    <div class="section-divider">
        <h2>⚙️ MANAGEMENT MENU</h2>
    </div>

    <div class="menu-row">
        <div class="menu-col">
            <a href="manage_users.php" class="menu-card">
                <div class="menu-icon">👥</div>
                <h3>Manage Users</h3>
                <p class="text-muted small">View, add, edit or delete member accounts</p>
            </a>
        </div>

        <div class="menu-col">
            <a href="manage_trainers.php" class="menu-card">
                <div class="menu-icon">👨‍🏫</div>
                <h3>Manage Trainers</h3>
                <p class="text-muted small">View, add, edit or delete trainer accounts</p>
            </a>
        </div>

        <div class="menu-col">
            <a href="manage_bookings.php" class="menu-card">
                <div class="menu-icon">📅</div>
                <h3>Manage Bookings</h3>
                <p class="text-muted small">Approve or reject gym session booking requests</p>
            </a>
        </div>

        <div class="menu-col">
            <a href="manage_refunds.php" class="menu-card">
                <div class="menu-icon">💰</div>
                <h3>Manage Refunds</h3>
                <p class="text-muted small">Review and process member refund requests</p>
            </a>
        </div>

        <div class="menu-col">
            <a href="gym_capacity.php" class="menu-card">
                <div class="menu-icon">🏋️</div>
                <h3>Gym Capacity</h3>
                <p class="text-muted small">Update gym session capacity limits</p>
            </a>
        </div>

        <div class="menu-col">
            <a href="equipment.php" class="menu-card">
                <div class="menu-icon">⚙️</div>
                <h3>Manage Equipment</h3>
                <p class="text-muted small">Track and manage gym equipment inventory</p>
            </a>
        </div>

        <div class="menu-col">
            <a href="reports.php" class="menu-card">
                <div class="menu-icon">📊</div>
                <h3>Generate Reports</h3>
                <p class="text-muted small">View booking and gym usage reports</p>
            </a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>