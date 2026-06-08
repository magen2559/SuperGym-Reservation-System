<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'trainer') {
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT trainer_id FROM trainers WHERE user_id = ?");
$stmt->execute([$user_id]);
$trainer = $stmt->fetch();

if (!$trainer) {
    header("Location: trainer_dashboard.php?error=Trainer profile not found");
    exit();
}

$trainer_id = $trainer['trainer_id'];

$stmt = $pdo->prepare("
    SELECT 
        b.id AS booking_id,
        b.status,
        b.payment_status,
        b.booking_date,
        u.name AS member_name,
        u.email AS member_email,
        u.phone AS member_phone,
        u.fitness_goal AS fitness_goal,
        ts.slot_date,
        ts.start_time,
        ts.end_time
    FROM bookings b
    JOIN trainer_slots ts ON b.trainer_slot_id = ts.id
    JOIN users u ON b.member_id = u.id
    WHERE ts.trainer_id = ?
      AND b.booking_type = 'trainer'
      AND b.status = 'approved'
    ORDER BY ts.slot_date ASC, ts.start_time ASC
");
$stmt->execute([$trainer_id]);
$assigned_members = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperGym - Assigned Members</title>

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

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .content-card {
            background-color: #1a1a1a;
            border: 1px solid #333;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .content-card h3 {
            color: #d6ff00;
            margin-bottom: 20px;
        }

        .table-dark {
            background-color: #1a1a1a;
            border-radius: 10px;
            overflow: hidden;
        }

        .table-dark td,
        .table-dark th {
            text-align: center;
            vertical-align: middle;
            border-color: #333;
            color: #ddd;
            padding: 12px 8px;
        }

        .table-dark th {
            color: #d6ff00;
        }

        .status-approved {
            background-color: #22c55e;
            color: #fff;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }

        .paid-badge {
            background-color: #22c55e;
            color: #fff;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }

        .text-muted {
            color: #aaa !important;
        }

        h1 {
            color: #fff;
        }

        .summary-card {
            background-color: #EEF527;
            color: #000;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            margin-bottom: 25px;
        }

        .summary-card h2 {
            font-weight: bold;
            margin-bottom: 5px;
        }

        footer {
            background-color: #0a0a0a;
            padding: 40px;
            text-align: center;
            border-top: 1px solid #222;
            margin-top: 50px;
        }
    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <div class="d-flex align-items-center">
            <a class="navbar-brand" href="index.php">SUPERGYM</a>
            <span class="welcome-text">
                Welcome, Coach <?php echo htmlspecialchars($_SESSION['user_name']); ?>
            </span>
        </div>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon bg-white"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="trainer_dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="trainer_schedule.php">My Schedule</a></li>
                <li class="nav-item"><a class="nav-link" href="assigned_members.php" style="color: #d6ff00 !important;">Assigned Members</a></li>
                <li class="nav-item"><a class="nav-link" href="trainer_history.php">History</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.php">My Account</a></li>
            </ul>

            <div class="ms-4">
                <a href="logout.php" class="btn btn-outline-custom">Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="main-container">

    <div class="my-4">
        <h1>Assigned Members</h1>
        <p class="text-muted">View members linked to your accepted trainer bookings</p>
    </div>

    <div class="summary-card">
        <h2><?php echo count($assigned_members); ?></h2>
        <p class="mb-0">Total Assigned Members / Approved Sessions</p>
    </div>

    <div class="content-card">
        <h3>👥 Assigned Member List</h3>

        <?php if(count($assigned_members) > 0): ?>
            <div class="table-responsive">
                <table class="table table-dark">
                    <thead>
                        <tr>
                            <th>Member Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Fitness Goal</th>
                            <th>Session Date</th>
                            <th>Session Time</th>
                            <th>Payment</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($assigned_members as $member): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($member['member_name']); ?></td>
                                <td><?php echo htmlspecialchars($member['member_email']); ?></td>
                                <td><?php echo htmlspecialchars($member['member_phone'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($member['fitness_goal'] ?? 'No goal added'); ?></td>
                                <td><?php echo date('D, M j, Y', strtotime($member['slot_date'])); ?></td>
                                <td>
                                    <?php echo date('g:i A', strtotime($member['start_time'])); ?> -
                                    <?php echo date('g:i A', strtotime($member['end_time'])); ?>
                                </td>
                                <td>
                                    <?php if($member['payment_status'] == 'paid'): ?>
                                        <span class="paid-badge">✓ Paid</span>
                                    <?php else: ?>
                                        <span class="text-muted"><?php echo ucfirst($member['payment_status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-approved">✓ Approved</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted">No assigned members yet. Approved trainer bookings will appear here.</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>