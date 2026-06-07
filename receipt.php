<?php
session_start();
require_once 'include/db.php';

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

$stmt = $pdo->prepare("
    SELECT b.*, 
           u.name as member_name, 
           u.email as member_email,
           CASE 
               WHEN b.booking_type = 'trainer' THEN trainer.name
               ELSE NULL
           END as trainer_name,
           CASE 
               WHEN b.booking_type = 'gym' THEN gs.session_date
               WHEN b.booking_type = 'trainer' THEN ts.slot_date
           END as session_date,
           CASE 
               WHEN b.booking_type = 'gym' THEN CONCAT(gs.start_time, ' - ', gs.end_time)
               WHEN b.booking_type = 'trainer' THEN CONCAT(ts.start_time, ' - ', ts.end_time)
           END as session_time
    FROM bookings b
    JOIN users u ON b.member_id = u.id
    LEFT JOIN gym_sessions gs ON b.gym_session_id = gs.id
    LEFT JOIN trainer_slots ts ON b.trainer_slot_id = ts.id
    LEFT JOIN trainers t ON ts.trainer_id = t.trainer_id
    LEFT JOIN users trainer ON t.user_id = trainer.id
    WHERE b.id = ?
");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking || $booking['payment_status'] != 'paid') {
    header("Location: my_bookings.php?error=Invalid receipt");
    exit();
}

$show_success = isset($_SESSION['payment_success']);
if ($show_success) {
    unset($_SESSION['payment_success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - SuperGym</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #111;
            color: #fff;
        }
        .receipt-container {
            max-width: 600px;
            margin: 50px auto;
        }
        .receipt-card {
            background-color: #1a1a1a;
            border: 1px solid #333;
            border-radius: 15px;
            overflow: hidden;
        }
        .receipt-header {
            background-color: #d6ff00;
            color: #000;
            padding: 20px;
            text-align: center;
        }
        .receipt-header h2 {
            margin: 0;
        }
        .receipt-body {
            padding: 25px;
        }
        .receipt-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #333;
        }
        .receipt-row:last-child {
            border-bottom: none;
        }
        .receipt-label {
            color: #aaa;
            font-weight: bold;
        }
        .receipt-value {
            color: #fff;
            font-weight: bold;
        }
        .total-row {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #d6ff00;
        }
        .total-label {
            color: #d6ff00;
            font-size: 1.2rem;
        }
        .total-value {
            color: #d6ff00;
            font-size: 1.2rem;
        }
        .btn-print {
            background-color: #d6ff00;
            color: #000;
            font-weight: bold;
            padding: 10px 20px;
            border-radius: 10px;
            border: none;
            margin-right: 10px;
        }
        .btn-print:hover {
            background-color: #c0e800;
        }
        .btn-back {
            background-color: #6b7280;
            color: #fff;
            font-weight: bold;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
        }
        .btn-back:hover {
            background-color: #4b5563;
            color: #fff;
        }
        .alert-success {
            background-color: #22c55e;
            color: #fff;
            border: none;
            text-align: center;
            margin-bottom: 20px;
        }
        @media print {
            body {
                background-color: white;
                color: black;
            }
            .no-print {
                display: none;
            }
            .receipt-card {
                border: 1px solid #ccc;
                box-shadow: none;
            }
            .receipt-header {
                background-color: #f0f0f0;
                color: #000;
            }
            .receipt-body {
                color: #000;
            }
            .receipt-label {
                color: #555;
            }
            .receipt-value {
                color: #000;
            }
        }
    </style>
</head>
<body>

<div class="container receipt-container">
    
    <?php if($show_success): ?>
        <div class="alert alert-success">
            ✅ Payment successful! Your booking is now confirmed.
        </div>
    <?php endif; ?>
    
    <div class="receipt-card">
        <div class="receipt-header">
            <h2>💪 SUPERGYM</h2>
            <p>Payment Receipt</p>
        </div>
        
        <div class="receipt-body">
            <div class="receipt-row">
                <span class="receipt-label">Receipt No:</span>
                <span class="receipt-value">#<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Transaction ID:</span>
                <span class="receipt-value"><?php echo htmlspecialchars($booking['transaction_id'] ?? 'N/A'); ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Payment Date:</span>
                <span class="receipt-value"><?php echo date('d F Y, h:i A', strtotime($booking['payment_date'])); ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Member Name:</span>
                <span class="receipt-value"><?php echo htmlspecialchars($booking['member_name']); ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Member Email:</span>
                <span class="receipt-value"><?php echo htmlspecialchars($booking['member_email']); ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Booking Type:</span>
                <span class="receipt-value"><?php echo $booking['booking_type'] == 'gym' ? '🏋️ Gym Session' : '👨‍🏫 Personal Trainer'; ?></span>
            </div>
            <?php if($booking['booking_type'] == 'trainer'): ?>
            <div class="receipt-row">
                <span class="receipt-label">Trainer:</span>
                <span class="receipt-value"><?php echo htmlspecialchars($booking['trainer_name']); ?></span>
            </div>
            <?php endif; ?>
            <div class="receipt-row">
                <span class="receipt-label">Session Date:</span>
                <span class="receipt-value"><?php echo date('d F Y', strtotime($booking['session_date'])); ?></span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Session Time:</span>
                <span class="receipt-value"><?php echo htmlspecialchars($booking['session_time']); ?></span>
            </div>
            <div class="receipt-row total-row">
                <span class="receipt-label total-label">Total Paid:</span>
                <span class="receipt-value total-value">RM <?php echo number_format($booking['payment_amount'], 2); ?></span>
            </div>
        </div>
    </div>
    
    <div class="text-center mt-4 no-print">
        <button onclick="window.print()" class="btn-print">🖨️ Print Receipt</button>
        <a href="my_bookings.php" class="btn-back">← Back to My Bookings</a>
    </div>
</div>

</body>
</html>