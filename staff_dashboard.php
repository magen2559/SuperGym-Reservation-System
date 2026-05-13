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
            background-color: #EEF527;
            border: 1px solid #333;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            transition: transform 0.3s;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: #fff;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #000;
        }

        .stat-card .text-muted {
            color: #333 !important;
            font-weight: 500;
        }

        .menu-card {
            background-color: #EEF527;
            border: 1px solid #333;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            text-decoration: none;
            transition: transform 0.3s;
            display: block;
            height: 100%;
        }

        .menu-card:hover {
            transform: translateY(-5px);
            border-color: #fff;
        }

        .menu-card h3 {
            color: #000;
            margin-bottom: 10px;
        }

        .menu-card .menu-icon {
            color: #000;
        }

        .menu-card .text-muted {
            color: #333 !important;
        }

        .menu-icon {
            font-size: 2.8rem;
            margin-bottom: 15px;
        }

        h1 {
            color: #fff;
        }

        h3 {
            color: #fff;
        }

        .text-muted {
            color: #aaa !important;
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
    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg sticky-top">

    <div class="container">

        <div class="d-flex align-items-center">

            <a class="navbar-brand" href="index.php">
                SUPERGYM
            </a>

            <span class="welcome-text">
                Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
            </span>

        </div>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon bg-white"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">

            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="manage_users.php">Users</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_trainers.php">Trainers</a></li>
                <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.php">My Account</a></li>
            </ul>

            <div class="ms-4">
                <a href="logout.php" class="btn btn-outline-custom">
                    Logout
                </a>
            </div>

        </div>

    </div>

</nav>

<div class="container my-5">

    <div class="row mb-4">

        <div class="col">

            <h1>Staff Dashboard</h1>

            <p class="text-muted">
                Manage gym operations, users, trainers, and reports
            </p>

        </div>

    </div>

    <div class="row mb-5">

        <div class="col-md-3 mb-3">

            <div class="stat-card">

                <div class="stat-number">
                    <?php echo $total_members; ?>
                </div>

                <div class="text-muted">
                    Total Members
                </div>

            </div>

        </div>

        <div class="col-md-3 mb-3">

            <div class="stat-card">

                <div class="stat-number">
                    <?php echo $total_trainers; ?>
                </div>

                <div class="text-muted">
                    Total Trainers
                </div>

            </div>

        </div>

        <div class="col-md-3 mb-3">

            <div class="stat-card">

                <div class="stat-number">
                    <?php echo $total_bookings; ?>
                </div>

                <div class="text-muted">
                    Total Bookings
                </div>

            </div>

        </div>

        <div class="col-md-3 mb-3">

            <div class="stat-card">

                <div class="stat-number">
                    <?php echo $pending_bookings; ?>
                </div>

                <div class="text-muted">
                    Pending Requests
                </div>

            </div>

        </div>

    </div>

    <div class="row g-4">

        <div class="col-md-4">

            <a href="manage_users.php" class="menu-card">

                <div class="menu-icon">
                    👥
                </div>

                <h3>Manage Users</h3>

                <p class="text-muted small">
                    View, add, edit, or delete member accounts
                </p>

            </a>

        </div>

        <div class="col-md-4">

            <a href="manage_trainers.php" class="menu-card">

                <div class="menu-icon">
                    👨‍🏫
                </div>

                <h3>Manage Trainers</h3>

                <p class="text-muted small">
                    View, add, edit, or delete trainer accounts
                </p>

            </a>

        </div>

        <div class="col-md-4">

            <a href="gym_capacity.php" class="menu-card">

                <div class="menu-icon">
                    🏋️
                </div>

                <h3>Gym Capacity</h3>

                <p class="text-muted small">
                    Update gym session capacity limits
                </p>

            </a>

        </div>

        <div class="col-md-4">

            <a href="equipment.php" class="menu-card">

                <div class="menu-icon">
                    ⚙️
                </div>

                <h3>Manage Equipment</h3>

                <p class="text-muted small">
                    Track and manage gym equipment inventory
                </p>

            </a>

        </div>

        <div class="col-md-4">

            <a href="reports.php" class="menu-card">

                <div class="menu-icon">
                    📊
                </div>

                <h3>Generate Reports</h3>

                <p class="text-muted small">
                    View booking and gym usage reports
                </p>

            </a>

        </div>

        <div class="col-md-4">

            <a href="profile.php" class="menu-card">

                <div class="menu-icon">
                    👤
                </div>

                <h3>My Account</h3>

                <p class="text-muted small">
                    Update your profile and account settings
                </p>

            </a>

        </div>

    </div>

</div>

<footer>

    <div class="container">

        <div style="font-size: 1.8rem; font-weight: bold; font-style: italic; color: #d6ff00; margin-bottom: 15px;">
            SUPERGYM
        </div>

        <p>
            © SuperGym Booking System. All Rights Reserved.
        </p>

    </div>

</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>