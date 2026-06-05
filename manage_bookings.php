<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'staff') {
    header("Location: dashboard.php");
    exit();
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $booking_id = $_GET['id'];
    $action = $_GET['action'];

    if ($action == 'approve') {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'approved' WHERE id = ?");
        $stmt->execute([$booking_id]);
    }

    if ($action == 'reject') {
        $stmt = $pdo->prepare("SELECT gym_session_id FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch();

        if ($booking && $booking['gym_session_id']) {
            $stmt = $pdo->prepare("UPDATE gym_sessions SET current_bookings = current_bookings - 1 WHERE id = ? AND current_bookings > 0");
            $stmt->execute([$booking['gym_session_id']]);
        }

        $stmt = $pdo->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$booking_id]);
    }

    header("Location: manage_bookings.php");
    exit();
}

$stmt = $pdo->query("
    SELECT 
        b.id,
        b.booking_type,
        b.status,
        b.booking_date,
        u.name AS member_name,
        gs.session_date,
        gs.start_time,
        gs.end_time
    FROM bookings b
    JOIN users u ON b.member_id = u.id
    LEFT JOIN gym_sessions gs ON b.gym_session_id = gs.id
    WHERE b.status = 'pending'
    ORDER BY b.booking_date DESC
");
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SuperGym - Manage Bookings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #111; color: #fff; }
        .navbar { background-color: #1a1a1a; border-bottom: 1px solid #333; padding: 6px; }
        .navbar-brand { font-weight: bold; font-size: 30px; color: #d6ff00 !important; padding-left: 15px; }
        .nav-link { color: #fff !important; font-weight: bold; text-transform: uppercase; }
        .nav-link:hover { color: #d6ff00 !important; }
        .welcome-text { color: #ddd; font-size: 14px; margin-left: 20px; padding-left: 20px; border-left: 1px solid #555; }
        .btn-outline-custom { border: 2px solid #d6ff00; color: #d6ff00; font-weight: bold; padding: 8px 20px; border-radius: 10px; text-decoration: none; background-color: transparent; }
        .btn-outline-custom:hover { background-color: #d6ff00; color: #000; }
        .box { background-color: #1a1a1a; border: 1px solid #333; border-radius: 15px; padding: 25px; }
        table { color: #fff !important; }
        th { color: #d6ff00 !important; }
        td { color: #fff !important; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container-fluid">
        <div class="d-flex align-items-center">
            <a class="navbar-brand" href="staff_dashboard.php">SUPERGYM</a>
            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        </div>

        <div class="ms-auto">
            <a href="staff_dashboard.php" class="btn btn-outline-custom">Dashboard</a>
            <a href="logout.php" class="btn btn-outline-custom ms-2">Logout</a>
        </div>
    </div>
</nav>

<div class="container my-5">
    <h1>Pending Booking Requests</h1>
    <p class="text-muted">Approve or reject gym booking requests</p>

    <div class="box mt-4">
        <?php if (count($bookings) == 0): ?>
            <div class="alert alert-warning mb-0">No pending booking requests.</div>
        <?php else: ?>
            <table class="table table-dark table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Requested At</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($bookings as $booking): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($booking['member_name']); ?></td>
                            <td><?php echo ucfirst($booking['booking_type']); ?></td>
                            <td><?php echo $booking['session_date']; ?></td>
                            <td>
                                <?php 
                                if ($booking['start_time'] && $booking['end_time']) {
                                    echo date('g:i A', strtotime($booking['start_time'])) . " - " . date('g:i A', strtotime($booking['end_time']));
                                } else {
                                    echo "-";
                                }
                                ?>
                            </td>
                            <td><?php echo $booking['booking_date']; ?></td>
                            <td><span class="badge bg-warning text-dark"><?php echo ucfirst($booking['status']); ?></span></td>
                            <td>
                                <a href="manage_bookings.php?action=approve&id=<?php echo $booking['id']; ?>" class="btn btn-success btn-sm">Approve</a>
                                <a href="manage_bookings.php?action=reject&id=<?php echo $booking['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Reject this booking?');">Reject</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

</body>
</html>