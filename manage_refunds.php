<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'staff') {
    header("Location: dashboard.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT b.*, u.name AS member_name, u.email AS member_email,
           ts.slot_date, ts.start_time, ts.end_time,
           trainer_user.name AS trainer_name
    FROM bookings b
    JOIN users u ON b.member_id = u.id
    JOIN trainer_slots ts ON b.trainer_slot_id = ts.id
    JOIN trainers t ON ts.trainer_id = t.id
    JOIN users trainer_user ON t.user_id = trainer_user.id
    WHERE b.refund_status = 'requested'
    ORDER BY b.booking_date DESC
");
$stmt->execute();
$refunds = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Refunds</title>
    <style>
        body { background:#111; color:#fff; font-family:Arial; padding:40px; }
        h1 { color:#d6ff00; }
        table { width:100%; border-collapse:collapse; background:#1a1a1a; }
        th, td { padding:12px; border:1px solid #333; text-align:center; }
        th { color:#d6ff00; }
        .btn { background:#d6ff00; color:#000; padding:8px 14px; border-radius:6px; text-decoration:none; font-weight:bold; }
        .back { color:#d6ff00; display:inline-block; margin-bottom:20px; }
    </style>
</head>
<body>

<a href="staff_dashboard.php" class="back">← Back to Staff Dashboard</a>
<h1>Refund Requests</h1>

<?php if(count($refunds) == 0): ?>
    <p>No refund requests.</p>
<?php else: ?>
<table>
    <tr>
        <th>Member</th>
        <th>Email</th>
        <th>Trainer</th>
        <th>Date</th>
        <th>Time</th>
        <th>Amount</th>
        <th>Reason</th>
        <th>Action</th>
    </tr>

    <?php foreach($refunds as $refund): ?>
    <tr>
        <td><?php echo htmlspecialchars($refund['member_name']); ?></td>
        <td><?php echo htmlspecialchars($refund['member_email']); ?></td>
        <td><?php echo htmlspecialchars($refund['trainer_name']); ?></td>
        <td><?php echo htmlspecialchars($refund['slot_date']); ?></td>
        <td><?php echo htmlspecialchars($refund['start_time'] . ' - ' . $refund['end_time']); ?></td>
        <td>RM<?php echo htmlspecialchars($refund['payment_amount']); ?></td>
        <td><?php echo htmlspecialchars($refund['refund_reason']); ?></td>
        <td>
            <a class="btn" href="process_refund.php?booking_id=<?php echo $refund['id']; ?>"
               onclick="return confirm('Mark this refund as processed?');">
               Mark Refunded
            </a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

</body>
</html>