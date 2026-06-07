<?php
ob_start();
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
    SELECT b.*, 
           u.name as member_name, 
           ts.slot_date, 
           ts.start_time, 
           ts.end_time, 
           b.payment_status, 
           b.status
    FROM bookings b
    JOIN trainer_slots ts ON b.trainer_slot_id = ts.id
    JOIN users u ON b.member_id = u.id
    WHERE ts.trainer_id = ? 
      AND CONCAT(ts.slot_date, ' ', ts.end_time) < NOW()
    ORDER BY ts.slot_date DESC, ts.start_time DESC
");
$stmt->execute([$trainer_id]);
$completed_bookings = $stmt->fetchAll();

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperGym - Trainer History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #111; color: #fff; }
        .navbar { background-color: #1a1a1a; border-bottom: 1px solid #333; padding: 6px; }
        .navbar .container { max-width: 100%; width: 100%; padding-left: 0; padding-right: 0; margin: 0; }

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

        .nav-link { color: #fff !important; font-weight: bold; text-transform: uppercase; }
        .nav-link:hover { color: #d6ff00 !important; }

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

        .paid-badge {
            background-color: #22c55e;
            color: #fff;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            display: inline-block;
        }

        .unpaid-badge {
            background-color: #f59e0b;
            color: #000;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            display: inline-block;
        }

        .status-badge {
            color: #fff;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }

        .status-approved { background-color: #22c55e; }
        .status-pending { background-color: #f59e0b; color: #000; }
        .status-rejected { background-color: #ef4444; }
        .status-cancelled { background-color: #6b7280; }
        .status-completed { background-color: #3b82f6; }

        .table-dark {
            background-color: #1a1a1a;
            border-radius: 10px;
            overflow: hidden;
        }

        .table-dark td,
        .table-dark th {
            border-color: #333;
            color: #ddd;
            text-align: center;
            vertical-align: middle;
        }

        .table-dark th { color: #d6ff00; }

        .content-card {
            background-color: #d6ff00;
            border: 1px solid #333;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .content-card h3 {
            color: #000;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #333;
        }

        h1 { color: #fff; }
        .text-muted { color: #aaa !important; }

        .simple-footer {
            background-color: #0a0a0a;
            padding: 40px;
            text-align: center;
            border-top: 1px solid #222;
            margin-top: 50px;
        }

        .simple-footer .logo {
            font-size: 1.8rem;
            font-weight: bold;
            font-style: italic;
            color: #d6ff00;
            margin-bottom: 15px;
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
                <li class="nav-item"><a class="nav-link" href="assigned_members.php">Assigned Members</a></li>
                <li class="nav-item"><a class="nav-link" href="trainer_history.php" style="color: #d6ff00 !important;">History</a></li>
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
            <h1>Trainer History</h1>
            <p class="text-muted">View completed and past trainer bookings</p>
        </div>
    </div>

    <div class="content-card">
        <h3>📜 Past Bookings</h3>

        <?php if (count($completed_bookings) > 0): ?>
            <div class="table-responsive">
                <table class="table table-dark">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Payment</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($completed_bookings as $booking): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['member_name']); ?></td>

                                <td><?php echo date('D, M j', strtotime($booking['slot_date'])); ?></td>

                                <td>
                                    <?php echo date('g:i A', strtotime($booking['start_time'])); ?> -
                                    <?php echo date('g:i A', strtotime($booking['end_time'])); ?>
                                </td>

                                <td>
                                    <?php if ($booking['payment_status'] == 'paid'): ?>
                                        <span class="paid-badge">✓ Paid</span>
                                    <?php else: ?>
                                        <span class="unpaid-badge">⏳ Unpaid</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="status-badge status-<?php echo htmlspecialchars($booking['status']); ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="color:#333;">No past bookings found.</p>
        <?php endif; ?>
    </div>
</div>

<div class="simple-footer">
    <div class="logo">SUPERGYM</div>
    <p>© SuperGym Booking System. All Rights Reserved.</p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>