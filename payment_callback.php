<?php
session_start();
require_once 'include/db.php';

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

if ($booking_id > 0) {
    $stmt = $pdo->prepare("
        UPDATE bookings
        SET payment_status = 'paid',
            payment_date = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$booking_id]);

    echo "<script>
        alert('Payment successful! Your booking is now waiting for trainer approval.');
        window.location.href = 'my_bookings.php';
    </script>";
    exit();
}

echo "<script>
    alert('Payment failed or booking not found.');
    window.location.href = 'my_bookings.php';
</script>";
exit();
?>