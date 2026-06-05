<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'member') {
    header("Location: dashboard.php");
    exit();
}

$member_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_trainer'])) {
    $slot_id = $_POST['slot_id'];

    $stmt = $pdo->prepare("
        SELECT id FROM bookings 
        WHERE member_id = ? AND trainer_slot_id = ? AND status NOT IN ('cancelled', 'rejected')
    ");
    $stmt->execute([$member_id, $slot_id]);

    if ($stmt->fetch()) {
        $error_message = "You have already booked this time slot!";
    } else {
        $stmt = $pdo->prepare("SELECT is_available FROM trainer_slots WHERE id = ?");
        $stmt->execute([$slot_id]);
        $slot = $stmt->fetch();

        if ($slot && $slot['is_available'] == 1) {

            if (isset($_SESSION['change_booking_id'])) {
                $old_booking_id = $_SESSION['change_booking_id'];
                $payment_amount = $_SESSION['change_payment_amount'];
                $bill_code = $_SESSION['change_bill_code'];
                $transaction_id = $_SESSION['change_transaction_id'];

                $stmt = $pdo->prepare("
                    INSERT INTO bookings 
                    (member_id, booking_type, trainer_slot_id, status, payment_status, payment_amount, bill_code, transaction_id, payment_date)
                    VALUES (?, 'trainer', ?, 'pending', 'paid', ?, ?, ?, NOW())
                ");

                if ($stmt->execute([$member_id, $slot_id, $payment_amount, $bill_code, $transaction_id])) {
                    $stmt = $pdo->prepare("
                        UPDATE bookings 
                        SET status = 'cancelled',
                            member_action = 'trainer_changed'
                        WHERE id = ?
                    ");
                    $stmt->execute([$old_booking_id]);

                    $stmt = $pdo->prepare("UPDATE trainer_slots SET is_available = 0 WHERE id = ?");
                    $stmt->execute([$slot_id]);

                    unset($_SESSION['change_booking_id']);
                    unset($_SESSION['change_payment_amount']);
                    unset($_SESSION['change_bill_code']);
                    unset($_SESSION['change_transaction_id']);

                    $success_message = "Trainer changed successfully! Waiting for trainer approval.";
                } else {
                    $error_message = "Booking failed. Please try again.";
                }

            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO bookings 
                    (member_id, booking_type, trainer_slot_id, status, payment_status, payment_amount) 
                    VALUES (?, 'trainer', ?, 'pending', 'unpaid', 50.00)
                ");

                if ($stmt->execute([$member_id, $slot_id])) {
                    $booking_id = $pdo->lastInsertId();

                    $stmt = $pdo->prepare("UPDATE trainer_slots SET is_available = 0 WHERE id = ?");
                    $stmt->execute([$slot_id]);

                    header("Location: process_payment.php?booking_id=" . $booking_id);
                    exit();
                } else {
                    $error_message = "Booking failed. Please try again.";
                }
            }

        } else {
            $error_message = "This time slot is no longer available.";
        }
    }
}

$stmt = $pdo->prepare("
    SELECT ts.*, 
           u.name as trainer_name, 
           t.specialty,
           t.bio,
           (SELECT COUNT(*) FROM bookings 
            WHERE trainer_slot_id = ts.id 
            AND member_id = ? 
            AND status NOT IN ('cancelled', 'rejected')) as user_booked,
           (SELECT COUNT(*) FROM bookings 
            WHERE trainer_slot_id = ts.id 
            AND status NOT IN ('cancelled', 'rejected')) as total_booked
    FROM trainer_slots ts
    JOIN trainers t ON t.id = ts.trainer_id
    JOIN users u ON u.id = t.user_id
    WHERE ts.slot_date >= CURDATE()
    ORDER BY ts.slot_date, ts.start_time
");
$stmt->execute([$member_id]);
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
        .btn-disabled {
            background-color: #6b7280;
            color: #aaa;
            font-weight: bold;
            padding: 8px 20px;
            border-radius: 10px;
            border: none;
            cursor: not-allowed;
        }
        .btn-booked {
            background-color: #22c55e;
            color: #fff;
            font-weight: bold;
            padding: 8px 20px;
            border-radius: 10px;
            border: none;
            cursor: not-allowed;
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
        .trainer-name {
            color: #d6ff00;
            font-size: 1.2rem;
            font-weight: bold;
        }
        .specialty {
            color: #aaa;
            font-size: 0.85rem;
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
        .slot-booked {
            opacity: 0.7;
            background-color: #1a1a1a;
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
                <li class="nav-item"><a class="nav-link" href="my_bookings.php">My Bookings</a></li>
                <li class="nav-item"><a class="nav-link" href="booking_history.php">Booking History</a></li>
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
            <p class="text-muted">Select a trainer and time slot for personalized coaching</p>
        </div>
    </div>

    <?php if($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if($error_message): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <?php if(count($slots) == 0): ?>
        <div class="alert alert-warning">No trainer slots available at the moment. Please check back later.</div>
    <?php else: ?>
        <div class="row">
            <?php foreach($slots as $slot): ?>
                <?php
                $is_user_booked = ($slot['user_booked'] > 0);
                $is_full = ($slot['total_booked'] > 0 && $slot['is_available'] == 0);
                ?>
                <div class="col-md-4 mb-4">
                    <div class="trainer-card p-4 <?php echo $is_user_booked ? 'slot-booked' : ''; ?>">
                        <div class="mb-3">
                            <div class="trainer-name"><?php echo htmlspecialchars($slot['trainer_name']); ?></div>
                            <div class="specialty"><?php echo htmlspecialchars($slot['specialty'] ?? 'Fitness Coach'); ?></div>
                            <?php if(!empty($slot['bio'])): ?>
                                <p class="small text-muted mt-2"><?php echo htmlspecialchars(substr($slot['bio'], 0, 80)); ?>...</p>
                            <?php endif; ?>
                        </div>
                        <div class="border-top border-secondary pt-3 mt-2">
                            <p class="mb-0">📅 <?php echo date('D, M j', strtotime($slot['slot_date'])); ?></p>
                            <p class="text-warning fw-bold mb-0">⏰ <?php echo date('g:i A', strtotime($slot['start_time'])); ?> - <?php echo date('g:i A', strtotime($slot['end_time'])); ?></p>
                        </div>
                        
                        <?php if($is_user_booked): ?>
                            <div class="mt-3 text-center">
                                <button class="btn-booked w-100" disabled>✓ Already Booked (Pending)</button>
                            </div>
                        <?php elseif($is_full): ?>
                            <div class="mt-3 text-center">
                                <button class="btn-disabled w-100" disabled>✗ Slot Unavailable</button>
                            </div>
                        <?php else: ?>
                            <form method="POST" class="mt-3">
                                <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                                <button type="submit" name="book_trainer" class="btn btn-primary-custom w-100">Book This Trainer</button>
                            </form>
                        <?php endif; ?>
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