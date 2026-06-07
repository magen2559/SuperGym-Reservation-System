<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'trainer') {
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

$stmt = $pdo->prepare("SELECT trainer_id FROM trainers WHERE user_id = ?");
$stmt->execute([$user_id]);
$trainer = $stmt->fetch();

if (!$trainer) {
    $stmt = $pdo->prepare("INSERT INTO trainers (user_id, specialty, bio) VALUES (?, 'Fitness Coach', 'Professional trainer')");
    $stmt->execute([$user_id]);
    $trainer_id = $pdo->lastInsertId();
} else {
    $trainer_id = $trainer['trainer_id'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_slot'])) {
    $slot_date = $_POST['slot_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    
    $min_time = "08:00";
    $max_time = "22:00";
    
    if (empty($slot_date) || empty($start_time) || empty($end_time)) {
        $error = "Please fill in all fields.";
    } elseif ($start_time >= $end_time) {
        $error = "End time must be after start time.";
    } elseif ($start_time < $min_time || $start_time > $max_time) {
        $error = "Start time must be between 8:00 AM and 10:00 PM.";
    } elseif ($end_time < $min_time || $end_time > $max_time) {
        $error = "End time must be between 8:00 AM and 10:00 PM.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO trainer_slots (trainer_id, slot_date, start_time, end_time, is_available) 
            VALUES (?, ?, ?, ?, 1)
        ");
        if ($stmt->execute([$trainer_id, $slot_date, $start_time, $end_time])) {
            $success = "Time slot added successfully!";
        } else {
            $error = "Failed to add time slot.";
        }
    }
}

if (isset($_GET['delete_slot'])) {
    $slot_id = $_GET['delete_slot'];
    
    $stmt = $pdo->prepare("
        SELECT b.id as booking_id, b.payment_status, b.status as booking_status
        FROM bookings b
        WHERE b.trainer_slot_id = ? AND b.status NOT IN ('cancelled', 'rejected')
    ");
    $stmt->execute([$slot_id]);
    $booking = $stmt->fetch();
    
    $pdo->beginTransaction();
    
    try {
        if ($booking) {
            if ($booking['payment_status'] == 'paid') {
                $stmt = $pdo->prepare("
                    UPDATE bookings 
                    SET status = 'cancelled', 
                        refund_status = 'completed',
                        refund_completed_date = NOW(),
                        payment_status = 'refunded'
                    WHERE id = ?
                ");
                $stmt->execute([$booking['booking_id']]);
                $success = "Slot deleted. Refund has been processed for the member.";
            } else {
                $stmt = $pdo->prepare("
                    UPDATE bookings 
                    SET status = 'cancelled'
                    WHERE id = ?
                ");
                $stmt->execute([$booking['booking_id']]);
                $success = "Slot deleted. Booking has been cancelled.";
            }
        }
        
        $stmt = $pdo->prepare("DELETE FROM trainer_slots WHERE id = ? AND trainer_id = ?");
        $stmt->execute([$slot_id, $trainer_id]);
        
        $pdo->commit();
        
        if (!$booking) {
            $success = "Time slot deleted successfully!";
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Failed to delete slot: " . $e->getMessage();
    }
}

$stmt = $pdo->prepare("
    SELECT ts.*, 
           b.id as booking_id, 
           b.payment_status,
           b.payment_amount,
           b.status as booking_status
    FROM trainer_slots ts
    LEFT JOIN bookings b ON ts.id = b.trainer_slot_id AND b.status NOT IN ('cancelled', 'rejected')
    WHERE ts.trainer_id = ? 
      AND ts.slot_date >= CURDATE()
    ORDER BY ts.slot_date, ts.start_time
");
$stmt->execute([$trainer_id]);
$slots = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperGym - My Schedule</title>
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
        .btn-primary-custom {
            background-color: #d6ff00;
            color: #000;
            font-weight: bold;
            border: none;
            padding: 10px 30px;
            font-size: 16px;
            border-radius: 10px;
        }
        .btn-primary-custom:hover { background-color: #c0e800; color: #000; }
        .btn-outline-custom {
            border: 2px solid #d6ff00;
            color: #d6ff00;
            font-weight: bold;
            padding: 8px 20px;
            border-radius: 10px;
            text-decoration: none;
            background-color: transparent;
        }
        .btn-outline-custom:hover { background-color: #d6ff00; color: #000; }
        .btn-danger-custom {
            background-color: #ef4444;
            color: #fff;
            border: none;
            padding: 5px 12px;
            border-radius: 5px;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-danger-custom:hover { background-color: #dc2626; color: #fff; text-decoration: none; }
        .btn-danger-booked {
            background-color: #dc2626;
            color: #fff;
            border: none;
            padding: 5px 12px;
            border-radius: 5px;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
        }
        .welcome-text {
            color: #ddd;
            font-size: 14px;
            margin-left: 20px;
            padding-left: 20px;
            border-left: 1px solid #555;
        }
        .schedule-card {
            background-color: #1a1a1a;
            border: 1px solid #333;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .form-control {
            background-color: #2a2a2a;
            border: 1px solid #555;
            color: #fff;
        }
        .form-control:focus {
            background-color: #333;
            border-color: #d6ff00;
            color: #fff;
            box-shadow: none;
        }
        input[type="date"], input[type="time"] {
            background-color: #2a2a2a !important;
            color: #fff !important;
            border: 1px solid #555 !important;
        }
        input[type="date"]::-webkit-calendar-picker-indicator,
        input[type="time"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
        }
        .table-dark { background-color: #1a1a1a; border-radius: 10px; overflow: hidden; }
        .table-dark td, .table-dark th {
            text-align: center;
            vertical-align: middle;
            border-color: #333;
            color: #ddd;
        }
        .table-dark th { color: #d6ff00; }
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
        h1 { color: #fff; }
        .text-muted { color: #aaa !important; }
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
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
                <li class="nav-item"><a class="nav-link" href="trainer_schedule.php" style="color: #d6ff00 !important;">My Schedule</a></li>
                <li class="nav-item"><a class="nav-link" href="assigned_members.php">Assigned Members</a></li>
                <li class="nav-item"><a class="nav-link" href="trainer_history.php">History</a></li>
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
            <h1>My Schedule</h1>
            <p class="text-muted">Manage your available time slots for member bookings</p>
        </div>
    </div>

    <?php if($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="schedule-card">
        <h4 class="mb-3">➕ Add New Time Slot</h4>
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Date</label>
                    <input type="date" name="slot_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Start Time</label>
                    <input type="time" name="start_time" class="form-control" min="08:00" max="22:00" required>
                    <small class="text-muted">8:00 AM - 10:00 PM</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">End Time</label>
                    <input type="time" name="end_time" class="form-control" min="08:00" max="22:00" required>
                    <small class="text-muted">8:00 AM - 10:00 PM</small>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-md-12 text-end">
                    <button type="submit" name="add_slot" class="btn btn-primary-custom">Add Slot</button>
                </div>
            </div>
        </form>
    </div>

    <div class="schedule-card">
        <h4 class="mb-3">📅 My Available Slots</h4>
        <?php if(count($slots) > 0): ?>
            <div class="table-responsive">
                <table class="table table-dark">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($slots as $slot): ?>
                            <tr>
                                <td><?php echo date('D, M j', strtotime($slot['slot_date'])); ?></td>
                                <td><?php echo date('g:i A', strtotime($slot['start_time'])); ?></td>
                                <td><?php echo date('g:i A', strtotime($slot['end_time'])); ?></td>
                                <td>
                                    <?php if($slot['is_available'] == 1): ?>
                                        <span class="badge" style="background-color: #22c55e;">✓ Available</span>
                                    <?php else: ?>
                                        <span class="badge" style="background-color: #6b7280;">✗ Booked</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($slot['booking_id']): ?>
                                        <?php if($slot['payment_status'] == 'paid'): ?>
                                            <span class="paid-badge">✓ Paid (RM<?php echo htmlspecialchars($slot['payment_amount']); ?>)</span>
                                        <?php else: ?>
                                            <span class="unpaid-badge">⏳ Unpaid (RM<?php echo htmlspecialchars($slot['payment_amount']); ?>)</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($slot['is_available'] == 1): ?>
                                        <a href="?delete_slot=<?php echo $slot['id']; ?>" class="btn-danger-custom" onclick="return confirm('Delete this available slot?')">Delete</a>
                                    <?php else: ?>
                                        <?php if($slot['payment_status'] == 'paid'): ?>
                                            <a href="?delete_slot=<?php echo $slot['id']; ?>" class="btn-danger-booked" onclick="return confirm('WARNING: This slot has been PAID. Deleting will refund the member. Continue?')">Cancel & Refund</a>
                                        <?php else: ?>
                                            <a href="?delete_slot=<?php echo $slot['id']; ?>" class="btn-danger-custom" onclick="return confirm('This slot has been booked but NOT paid. Deleting will cancel the booking. Continue?')">Cancel Booking</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted">No time slots added yet. Click above to add your availability.</p>
        <?php endif; ?>
    </div>
</div>

<?php if (file_exists('footer.php')) include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>  