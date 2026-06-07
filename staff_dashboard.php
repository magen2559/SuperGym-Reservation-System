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
            padding: 8px 15px;
        }

        .navbar .container {
            max-width: 100%;
            width: 100%;
        }

        .navbar-brand,
        .navbar-brand:hover {
            font-weight: bold;
            font-size: 30px;
            color: #d6ff00 !important;
            text-decoration: none;
        }

        .nav-link {
            color: #fff !important;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 14px;
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

        .dashboard-container {
            max-width: 1280px;
            margin: auto;
        }

        .stat-card,
        .menu-card {
            background: linear-gradient(135deg, #f3ff18, #dfff00);
            border-radius: 18px;
            padding: 28px 20px;
            text-align: center;
            text-decoration: none;
            height: 100%;
            min-height: 150px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(214, 255, 0, 0.15);
            transition: 0.3s ease;
        }

        .stat-card:hover,
        .menu-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 30px rgba(214, 255, 0, 0.35);
        }

        .stat-number {
            font-size: 42px;
            font-weight: 800;
            color: #000;
            line-height: 1;
            margin-bottom: 12px;
        }

        .stat-label {
            color: #222;
            font-weight: 600;
            font-size: 15px;
        }

        .menu-card {
            min-height: 210px;
        }

        .menu-icon {
            font-size: 42px;
            margin-bottom: 18px;
        }

        .menu-card h3 {
            color: #000;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .menu-card p {
            color: #333 !important;
            margin-bottom: 0;
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
            <a class="navbar-brand" href="index.php">SUPERGYM</a>

            <span class="welcome-text">
                Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
            </span>
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

<div class="container dashboard-container my-5">

    <div class="row mb-4">
        <div class="col">
            <h1 class="fw-bold">Staff Dashboard</h1>
            <p class="text-muted">Manage gym operations, users, trainers, and reports</p>
        </div>
    </div>

    <div class="row g-4 mb-5">

        <div class="col-lg-3 col-md-6">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_members; ?></div>
                <div class="stat-label">Total Members</div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_trainers; ?></div>
                <div class="stat-label">Total Trainers</div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_bookings; ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <a href="manage_bookings.php" class="text-decoration-none">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $pending_bookings; ?></div>
                    <div class="stat-label">Pending Requests</div>
                </div>
            </a>
        </div>

        <div class="col-lg-3 col-md-6">
            <a href="manage_refunds.php" class="text-decoration-none">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $pending_refunds; ?></div>
                    <div class="stat-label">Refund Requests</div>
                </div>
            </a>
        </div>

    </div>

    <div class="row g-4">

        <div class="col-lg-4 col-md-6">
            <a href="manage_users.php" class="menu-card">
                <div class="menu-icon">👥</div>
                <h3>Manage Users</h3>
                <p class="small">View, add, edit or delete member accounts</p>
            </a>
        </div>

        <div class="col-lg-4 col-md-6">
            <a href="manage_trainers.php" class="menu-card">
                <div class="menu-icon">👨‍🏫</div>
                <h3>Manage Trainers</h3>
                <p class="small">View, add, edit or delete trainer accounts</p>
            </a>
        </div>

        <div class="col-lg-4 col-md-6">
            <a href="manage_bookings.php" class="menu-card">
                <div class="menu-icon">📅</div>
                <h3>Manage Bookings</h3>
                <p class="small">Approve or reject gym session booking requests</p>
            </a>
        </div>

        <div class="col-lg-4 col-md-6">
            <a href="manage_refunds.php" class="menu-card">
                <div class="menu-icon">💰</div>
                <h3>Manage Refunds</h3>
                <p class="small">Approve or reject member refund requests</p>
            </a>
        </div>

        <div class="col-lg-4 col-md-6">
            <a href="gym_capacity.php" class="menu-card">
                <div class="menu-icon">🏋️</div>
                <h3>Gym Capacity</h3>
                <p class="small">Update gym session capacity limits</p>
            </a>
        </div>

        <div class="col-lg-4 col-md-6">
            <a href="equipment.php" class="menu-card">
                <div class="menu-icon">⚙️</div>
                <h3>Manage Equipment</h3>
                <p class="small">Track and manage gym equipment inventory</p>
            </a>
        </div>

        <div class="col-lg-4 col-md-6">
            <a href="reports.php" class="menu-card">
                <div class="menu-icon">📊</div>
                <h3>Generate Reports</h3>
                <p class="small">View booking and gym usage reports</p>
            </a>
        </div>

    </div>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>