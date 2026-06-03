<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'member') {
    header("Location: dashboard.php");
    exit();
}

$member_id = $_SESSION['user_id'];
$message = '';
$error = '';

$stmt = $pdo->prepare("
    SELECT b.*, 
           CASE 
               WHEN b.booking_type = 'gym' THEN gs.session_date
               WHEN b.booking_type = 'trainer' THEN ts.slot_date
           END as booking_date,
           CASE 
               WHEN b.booking_type = 'gym' THEN CONCAT(gs.start_time, ' - ', gs.end_time)
               WHEN b.booking_type = 'trainer' THEN CONCAT(ts.start_time, ' - ', ts.end_time)
           END as booking_time,
           CASE 
               WHEN b.booking_type = 'gym' THEN gs.start_time
               WHEN b.booking_type = 'trainer' THEN ts.start_time
           END as booking_start_time,
           CASE 
               WHEN b.booking_type = 'trainer' THEN u.name
               ELSE NULL
           END as trainer_name,
           CASE 
               WHEN b.booking_type = 'gym' THEN gs.id
               WHEN b.booking_type = 'trainer' THEN ts.id
           END as session_or_slot_id,
           b.payment_status,
           b.payment_amount,
           b.refund_status,
           b.payment_date,
           b.refund_request_date
    FROM bookings b
    LEFT JOIN gym_sessions gs ON b.gym_session_id = gs.id
    LEFT JOIN trainer_slots ts ON b.trainer_slot_id = ts.id
    LEFT JOIN trainers t ON ts.trainer_id = t.id
    LEFT JOIN users u ON t.user_id = u.id
    WHERE b.member_id = ?
    ORDER BY booking_date DESC, booking_time ASC
");
$stmt->execute([$member_id]);
$bookings = $stmt->fetchAll();

$upcoming_bookings = [];
$past_bookings = [];

foreach ($bookings as $booking) {
    $booking_date = $booking['booking_date'];
    $today = date('Y-m-d');
    
    if ($booking_date >= $today && $booking['status'] != 'cancelled' && $booking['status'] != 'rejected' && $booking['status'] != 'completed') {
        $upcoming_bookings[] = $booking;
    } else {
        $past_bookings[] = $booking;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperGym - My Bookings</title>
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
        .btn-add-to-cart {
            background-color: #000;
            color: #fff;
            font-weight: bold;
            border: none;
            padding: 12px 28px;
            border-radius: 50px;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(214, 255, 0, 0.3);
        }
        .btn-add-to-cart:hover {
            background-color: #c0e800;
            color: #000;
            transform: scale(1.02);
            box-shadow: 0 6px 20px rgba(214, 255, 0, 0.4);
        }
        .btn-primary-custom {
            background-color: #d6ff00;
            color: #000;
            font-weight: bold;
            border: none;
            padding: 10px 25px;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .btn-primary-custom:hover {
            background-color: #c0e800;
            color: #000;
            transform: scale(1.02);
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
        .btn-cancel {
            background-color: #ef4444;
            color: #fff;
            font-weight: bold;
            border: none;
            padding: 5px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
        }
        .btn-cancel:hover {
            background-color: #dc2626;
            color: #fff;
        }
        .btn-pay {
            background-color: #d6ff00;
            color: #000;
            font-weight: bold;
            padding: 5px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            margin-right: 5px;
            display: inline-block;
        }
        .btn-pay:hover {
            background-color: #c0e800;
            color: #000;
            text-decoration: none;
        }
        .btn-refund {
            background-color: #f59e0b;
            color: #fff;
            font-weight: bold;
            padding: 5px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            margin-right: 5px;
            display: inline-block;
        }
        .btn-refund:hover {
            background-color: #d97706;
            color: #fff;
            text-decoration: none;
        }
        .btn-receipt {
            background-color: #3b82f6;
            color: #fff;
            font-weight: bold;
            padding: 5px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            margin-right: 5px;
            display: inline-block;
        }
        .btn-receipt:hover {
            background-color: #2563eb;
            color: #fff;
            text-decoration: none;
        }
        .paid-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            background-color: #22c55e;
            color: #fff;
        }
        .unpaid-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            background-color: #6b7280;
            color: #fff;
        }
        .refund-pending-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            background-color: #f59e0b;
            color: #000;
        }
        .refund-completed-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            background-color: #ef4444;
            color: #fff;
        }
        .welcome-text {
            color: #ddd;
            font-size: 14px;
            margin-left: 20px;
            padding-left: 20px;
            border-left: 1px solid #555;
        }
        .table-dark td, 
        .table-dark th {
            text-align: center;
            vertical-align: middle;
        }
        .table-dark {
            background-color: #1a1a1a;
            border-radius: 10px;
            overflow: hidden;
        }
        .table-dark td, .table-dark th {
            border-color: #333;
            color: #ddd;
        }
        .table-dark th {
            color: #d6ff00;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending {
            background-color: #f59e0b;
            color: #000;
        }
        .status-approved {
            background-color: #22c55e;
            color: #fff;
        }
        .status-rejected {
            background-color: #ef4444;
            color: #fff;
        }
        .status-cancelled {
            background-color: #6b7280;
            color: #fff;
        }
        .status-completed {
            background-color: #3b82f6;
            color: #fff;
        }
        .content-card {
            background-color: #EEF527;
            border: 1px solid #333;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .content-card h3 {
            color: #000;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ccc;
        }
        .cart-btn-container {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
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
                <li class="nav-item"><a class="nav-link" href="book_trainer.php">Book Trainer</a></li>
                <li class="nav-item"><a class="nav-link" href="my_bookings.php" style="color: #d6ff00 !important;">My Bookings</a></li>
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
            <h1>My Bookings</h1>
            <p class="text-muted">View and manage your upcoming gym session and trainer bookings</p>
        </div>
    </div>

    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>

    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">📅 Upcoming Bookings</h3>
            <button type="button" id="addToCartBtn" class="btn-add-to-cart">➕ Add Selected to Cart</button>
        </div>
        
        <?php if(count($upcoming_bookings) > 0): ?>
            <div class="table-responsive">
                <form id="cartForm" method="post">
                    <table class="table table-dark">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Trainer</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($upcoming_bookings as $booking): ?>
                                <?php 
                                $can_refund = false;
                                $hours_until_booking = null;
                                if ($booking['booking_type'] == 'trainer' && 
                                    $booking['payment_status'] == 'paid' && 
                                    $booking['refund_status'] == 'none' &&
                                    $booking['booking_date'] && $booking['booking_start_time']) {
                                    
                                    $booking_datetime = strtotime($booking['booking_date'] . ' ' . $booking['booking_start_time']);
                                    $now = time();
                                    $hours_until_booking = ($booking_datetime - $now) / 3600;
                                    
                                    $can_refund = ($hours_until_booking > 24);
                                }
                                $can_add_to_cart = ($booking['booking_type'] == 'trainer' && 
                                                    $booking['payment_status'] != 'paid' && 
                                                    $booking['payment_status'] != 'refunded' && 
                                                    $booking['status'] == 'approved');
                                ?>
                                <tr>
                                    <td>
                                        <?php if($can_add_to_cart): ?>
                                            <input type="checkbox" class="booking-checkbox" value="<?php echo $booking['id']; ?>">
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $booking['booking_type'] == 'gym' ? '🏋️ Gym Session' : '👨‍🏫 Personal Trainer'; ?></td>
                                    <td><?php echo htmlspecialchars($booking['booking_date'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($booking['booking_time'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($booking['trainer_name'] ?? '-'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $booking['status']; ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($booking['booking_type'] == 'trainer'): ?>
                                            <?php if($booking['payment_status'] == 'paid'): ?>
                                                <span class="paid-badge">✓ Paid (RM<?php echo $booking['payment_amount']; ?>)</span>
                                            <?php elseif($booking['payment_status'] == 'refunded'): ?>
                                                <span class="refund-completed-badge">↺ Refunded</span>
                                            <?php else: ?>
                                                <span class="unpaid-badge">Unpaid (RM50)</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($booking['payment_status'] == 'paid'): ?>
                                            <a href="receipt.php?booking_id=<?php echo $booking['id']; ?>" class="btn-receipt">🧾 Receipt</a>
                                        <?php endif; ?>
                                        
                                        <?php if($can_refund): ?>
                                            <a href="request_refund.php?booking_id=<?php echo $booking['id']; ?>" class="btn-refund">↺ Request Refund</a>
                                        <?php elseif($booking['payment_status'] == 'paid' && $booking['refund_status'] == 'none' && $hours_until_booking <= 24 && $hours_until_booking > 0): ?>
                                            <span class="text-muted" title="Refund only available more than 24 hours before session">⏰ No refund</span>
                                        <?php endif; ?>
                                        
                                        <?php if($booking['refund_status'] == 'requested'): ?>
                                            <span class="refund-pending-badge">⏳ Refund Pending</span>
                                        <?php endif; ?>
                                        
                                        <?php if(($booking['status'] == 'pending' || $booking['status'] == 'approved') && $booking['payment_status'] != 'paid' && $booking['payment_status'] != 'refunded'): ?>
                                            <form action="cancel_booking.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to cancel this booking?')">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                <input type="hidden" name="booking_type" value="<?php echo $booking['booking_type']; ?>">
                                                <input type="hidden" name="session_or_slot_id" value="<?php echo $booking['session_or_slot_id']; ?>">
                                                <button type="submit" name="cancel_booking" class="btn-cancel">Cancel</button>
                                            </form>
                                        <?php elseif($booking['status'] == 'pending' || $booking['status'] == 'approved'): ?>
                                            <span class="text-muted">-</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </form>
            </div>
        <?php else: ?>
            <p class="text-muted">No upcoming bookings.</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('selectAll').addEventListener('change', function() {
    var checkboxes = document.querySelectorAll('.booking-checkbox');
    checkboxes.forEach(function(cb) {
        cb.checked = document.getElementById('selectAll').checked;
    });
});

document.getElementById('addToCartBtn').addEventListener('click', function() {
    var checkboxes = document.querySelectorAll('.booking-checkbox:checked');
    var selectedIds = [];
    checkboxes.forEach(function(cb) {
        selectedIds.push(cb.value);
    });
    
    if (selectedIds.length === 0) {
        alert('Please select at least one booking to add to cart.');
        return;
    }
    
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ booking_ids: selectedIds })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'cart.php';
        } else {
            alert(data.message || 'Error adding to cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
});
</script>
</body>
</html>