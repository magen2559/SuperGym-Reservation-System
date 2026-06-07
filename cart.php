<?php
session_start();
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'member') {
    header("Location: dashboard.php");
    exit();
}

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : null;
$bookings = $cart ? $cart['bookings'] : [];
$total_amount = $cart ? $cart['total_amount'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - SuperGym</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #111; color: #fff; }
        .navbar { background-color: #1a1a1a; border-bottom: 1px solid #333; padding: 6px; }
        .navbar-brand { font-weight: bold; font-size: 30px; color: #d6ff00 !important; }
        .cart-container { max-width: 800px; margin: 50px auto; }
        .cart-item { background-color: #1a1a1a; border-radius: 15px; padding: 15px; margin-bottom: 15px; border: 1px solid #333; }
        .cart-total { background-color: #1a1a1a; border-radius: 15px; padding: 20px; margin-top: 20px; border: 1px solid #333; }
        .btn-checkout { background-color: #d6ff00; color: #000; font-weight: bold; padding: 12px 30px; border-radius: 10px; }
        .btn-remove { background-color: #ef4444; color: #fff; padding: 5px 12px; border-radius: 5px; text-decoration: none; font-size: 12px; }
        .btn-back { background-color: #6b7280; color: #fff; padding: 10px 20px; border-radius: 10px; text-decoration: none; }
        .empty-cart { text-align: center; padding: 50px; background-color: #1a1a1a; border-radius: 15px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">SUPERGYM</a>
        <div class="ms-auto">
            <a href="my_bookings.php" class="btn btn-outline-custom" style="border: 2px solid #d6ff00; color: #d6ff00; padding: 8px 20px; border-radius: 10px;">← Back</a>
        </div>
    </div>
</nav>

<div class="container cart-container">
    <h1 class="mb-4">🛒 Shopping Cart</h1>
    
    <?php if(empty($bookings)): ?>
        <div class="empty-cart">
            <div style="font-size: 4rem;">🛒</div>
            <h3>Your cart is empty</h3>
            <p class="text-muted">Select bookings from My Bookings to add to cart.</p>
            <a href="book_trainer.php" class="btn-back">← Back to Trainer Booking</a>
        </div>
    <?php else: ?>
        <?php foreach($bookings as $index => $booking): ?>
            <div class="cart-item">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="text-warning">👨‍🏫 <?php echo htmlspecialchars($booking['trainer_name']); ?></h5>
                        <p class="mb-0">📅 <?php echo date('D, M j', strtotime($booking['slot_date'])); ?></p>
                        <p class="mb-0">⏰ <?php echo date('g:i A', strtotime($booking['start_time'])); ?> - <?php echo date('g:i A', strtotime($booking['end_time'])); ?></p>
                    </div>
                    <div class="text-end">
                        <p class="fw-bold text-warning mb-2">RM <?php echo number_format($booking['amount'], 2); ?></p>
                        <a href="remove_from_cart.php?index=<?php echo $index; ?>" class="btn-remove" onclick="return confirm('Remove this item?')">Remove</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="cart-total">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4>Total</h4>
                    <p class="text-muted"><?php echo count($bookings); ?> item(s)</p>
                </div>
                <div>
                    <h3 class="text-warning">RM <?php echo number_format($total_amount, 2); ?></h3>
                </div>
            </div>
            <div class="d-flex justify-content-between mt-4">
                <a href="book_trainer.php" class="btn-back">← Add More</a>
                <a href="process_bulk_payment.php" class="btn-checkout">💳 Proceed to Checkout</a>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html>