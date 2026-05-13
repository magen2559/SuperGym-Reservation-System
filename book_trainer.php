<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'member') {
    header("Location: dashboard.php");
    exit();
}

$success_message = '';
$error_message = '';

// 处理预约
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['slot_id'])) {
    $slot_id = $_POST['slot_id'];
    $member_id = $_SESSION['user_id'];
    
    // 检查是否已经预约了这个时段
    $stmt = $pdo->prepare("SELECT id FROM bookings WHERE member_id = ? AND trainer_slot_id = ? AND status NOT IN ('cancelled', 'rejected')");
    $stmt->execute([$member_id, $slot_id]);
    if ($stmt->fetch()) {
        $error_message = "You have already booked this time slot!";
    } else {
        // 创建预约
        $stmt = $pdo->prepare("INSERT INTO bookings (member_id, booking_type, trainer_slot_id, status) VALUES (?, 'trainer', ?, 'pending')");
        if ($stmt->execute([$member_id, $slot_id])) {
            // 标记时段为已预约
            $stmt = $pdo->prepare("UPDATE trainer_slots SET is_available = FALSE WHERE id = ?");
            $stmt->execute([$slot_id]);
            $success_message = "Trainer session booked successfully! Waiting for trainer approval.";
        } else {
            $error_message = "Booking failed. Please try again.";
        }
    }
}

// 获取可预约的教练时段
$stmt = $pdo->prepare("
    SELECT ts.*, u.name as trainer_name, t.specialty
    FROM trainer_slots ts
    JOIN trainers t ON t.id = ts.trainer_id
    JOIN users u ON u.id = t.user_id
    WHERE ts.slot_date >= CURDATE() AND ts.is_available = TRUE
    ORDER BY ts.slot_date, ts.start_time
");
$stmt->execute();
$slots = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperGym - Book Personal Trainer</title>
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
        .trainer-card {
            background-color: #1a1a1a;
            border: 1px solid #333;
            border-radius: 15px;
            transition: transform 0.3s;
            height: 100%;
        }
        .trainer-card:hover {
            transform: translateY(-5px);
            border-color: #d6ff00;
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
                <li class="nav-item"><a class="nav-link" href="member_dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="book_gym.php">Book Gym</a></li>
                <li class="nav-item"><a class="nav-link" href="book_trainer.php" style="color: #d6ff00 !important;">Book Trainer</a></li>
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
            <h1>Book Personal Trainer</h1>
            <p class="text-muted">Select a trainer and time slot</p>
        </div>
    </div>

    <?php if($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if($error_message): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <?php if(count($slots) == 0): ?>
        <div class="alert alert-warning">No available trainer slots at the moment. Please check back later.</div>
    <?php else: ?>
        <div class="row">
            <?php foreach($slots as $slot): ?>
                <div class="col-md-4 mb-4">
                    <div class="trainer-card p-4">
                        <div class="mb-3">
                            <h4 class="text-warning"><?php echo htmlspecialchars($slot['trainer_name']); ?></h4>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($slot['specialty']); ?></p>
                        </div>
                        <div class="border-top border-secondary pt-3 mt-2">
                            <p class="mb-0"><?php echo date('D, M j', strtotime($slot['slot_date'])); ?></p>
                            <p class="text-warning fw-bold mb-0"><?php echo date('g:i A', strtotime($slot['start_time'])); ?> - <?php echo date('g:i A', strtotime($slot['end_time'])); ?></p>
                        </div>
                        <form method="POST" class="mt-3">
                            <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                            <button type="submit" class="btn btn-primary-custom w-100">Book This Trainer</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
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