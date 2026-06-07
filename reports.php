<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

if ($_SESSION['user_role'] != 'staff') {
    header("Location: dashboard.php");
    exit();
}

$report_type = isset($_GET['type']) ? $_GET['type'] : 'dashboard';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-3 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$popular_date = isset($_GET['popular_date']) ? $_GET['popular_date'] : date('Y-m-d');
$export = isset($_GET['export']) ? $_GET['export'] : '';

$stmt = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'member') as total_members,
        (SELECT COUNT(*) FROM users WHERE role = 'trainer') as total_trainers,
        (SELECT COUNT(*) FROM users WHERE role = 'staff') as total_staff,
        (SELECT COUNT(*) FROM bookings) as total_bookings,
        (SELECT COUNT(*) FROM bookings WHERE status = 'pending') as pending_bookings,
        (SELECT COUNT(*) FROM bookings WHERE status = 'approved') as approved_bookings,
        (SELECT COUNT(*) FROM bookings WHERE status = 'cancelled') as cancelled_bookings,
        (SELECT COUNT(*) FROM bookings WHERE payment_status = 'paid') as paid_bookings,
        (SELECT SUM(payment_amount) FROM bookings WHERE payment_status = 'paid') as total_revenue
");
$overall_stats = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT 
        DATE(b.booking_date) as date,
        COUNT(*) as total_bookings,
        SUM(CASE WHEN b.booking_type = 'gym' THEN 1 ELSE 0 END) as gym_bookings,
        SUM(CASE WHEN b.booking_type = 'trainer' THEN 1 ELSE 0 END) as trainer_bookings,
        SUM(CASE WHEN b.payment_status = 'paid' THEN 1 ELSE 0 END) as paid_bookings,
        SUM(CASE WHEN b.payment_status = 'paid' THEN b.payment_amount ELSE 0 END) as daily_revenue
    FROM bookings b
    WHERE DATE(b.booking_date) BETWEEN DATE_SUB(CURDATE(), INTERVAL 3 DAY) AND CURDATE()
    GROUP BY DATE(b.booking_date)
    ORDER BY DATE(b.booking_date) DESC
");
$stmt->execute();
$daily_stats = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT 
        gs.session_date,
        gs.start_time,
        gs.end_time,
        COUNT(b.id) as booking_count,
        gs.max_capacity,
        gs.current_bookings,
        ROUND((gs.current_bookings / gs.max_capacity) * 100, 1) as occupancy_rate
    FROM gym_sessions gs
    LEFT JOIN bookings b ON gs.id = b.gym_session_id AND b.status NOT IN ('cancelled', 'rejected')
    WHERE gs.session_date = ?
    GROUP BY gs.id
    ORDER BY booking_count DESC, occupancy_rate DESC
    LIMIT 3
");
$stmt->execute([$popular_date]);
$popular_sessions = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT 
        u.name as trainer_name,
        t.specialty,
        COUNT(b.id) as total_bookings,
        SUM(CASE WHEN b.status = 'approved' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN b.status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN b.status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
        SUM(CASE WHEN b.payment_status = 'paid' THEN 1 ELSE 0 END) as paid_count,
        ROUND(COALESCE(SUM(CASE WHEN b.status = 'approved' THEN 1 ELSE 0 END) / NULLIF(COUNT(b.id), 0) * 100, 0), 1) as approval_rate
    FROM users u
    JOIN trainers t ON u.id = t.user_id
    LEFT JOIN trainer_slots ts ON t.trainer_id = ts.trainer_id
    LEFT JOIN bookings b ON ts.id = b.trainer_slot_id
    WHERE u.role = 'trainer'
    GROUP BY u.id, t.specialty
    ORDER BY total_bookings DESC
");
$stmt->execute();
$trainer_stats = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT 
        DATE(b.booking_date) as date,
        COUNT(*) as total_bookings,
        SUM(CASE WHEN b.payment_status = 'paid' THEN 1 ELSE 0 END) as paid_bookings
    FROM bookings b
    WHERE DATE(b.booking_date) BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND CURDATE()
    GROUP BY DATE(b.booking_date)
    ORDER BY DATE(b.booking_date) ASC
");
$stmt->execute();
$daily_trend = $stmt->fetchAll();

$trend_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $display_date = date('d M', strtotime($date));
    $found = false;
    foreach ($daily_trend as $row) {
        if ($row['date'] == $date) {
            $trend_data[] = [
                'date' => $display_date,
                'total' => $row['total_bookings'],
                'paid' => $row['paid_bookings']
            ];
            $found = true;
            break;
        }
    }
    if (!$found) {
        $trend_data[] = [
            'date' => $display_date,
            'total' => 0,
            'paid' => 0
        ];
    }
}

$stmt = $pdo->prepare("
    SELECT 
        status,
        COUNT(*) as count,
        ROUND(COUNT(*) / (SELECT COUNT(*) FROM bookings) * 100, 1) as percentage
    FROM bookings
    GROUP BY status
");
$stmt->execute();
$status_distribution = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT 
        u.id,
        u.name,
        u.email,
        COUNT(b.id) as total_bookings,
        SUM(CASE WHEN b.status = 'approved' THEN 1 ELSE 0 END) as completed_bookings,
        SUM(CASE WHEN b.payment_status = 'paid' THEN 1 ELSE 0 END) as paid_bookings,
        SUM(b.payment_amount) as total_spent,
        u.created_at as registered_date
    FROM users u
    LEFT JOIN bookings b ON u.id = b.member_id
    WHERE u.role = 'member'
    GROUP BY u.id
    ORDER BY total_bookings DESC
    LIMIT 5
");
$stmt->execute();
$member_activity = $stmt->fetchAll();

if ($export == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="supergym_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Report Type', 'Generated On', 'Period Start', 'Period End']);
    fputcsv($output, ['SuperGym System Report', date('Y-m-d H:i:s'), $start_date, $end_date]);
    fputcsv($output, []);
    
    fputcsv($output, ['=== Daily Booking Report ===']);
    fputcsv($output, ['Date', 'Total Bookings', 'Gym', 'Trainer', 'Paid', 'Revenue (RM)']);
    foreach ($daily_stats as $row) {
        fputcsv($output, [
            $row['date'],
            $row['total_bookings'],
            $row['gym_bookings'],
            $row['trainer_bookings'],
            $row['paid_bookings'],
            $row['daily_revenue']
        ]);
    }
    
    fputcsv($output, []);
    fputcsv($output, ['=== Trainer Performance ===']);
    fputcsv($output, ['Trainer', 'Specialty', 'Total', 'Approved', 'Pending', 'Paid', 'Approval Rate (%)']);
    foreach ($trainer_stats as $row) {
        fputcsv($output, [
            $row['trainer_name'],
            $row['specialty'],
            $row['total_bookings'],
            $row['approved_count'],
            $row['pending_count'],
            $row['paid_count'],
            $row['approval_rate']
        ]);
    }
    
    fclose($output);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperGym - Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
        .stat-card {
            background-color: #1a1a1a;
            border: 1px solid #333;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #d6ff00;
        }
        .report-card {
            background-color: #1a1a1a;
            border: 1px solid #333;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .report-card .row .col-md-6 {
            text-align: center;
        }
        .report-title {
            color: #d6ff00;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #333;
        }
        .table-dark {
            background-color: #1a1a1a;
            border-radius: 10px;
            overflow: hidden;
        }
        .table-dark td, .table-dark th {
            border-color: #333;
            color: #ddd;
            text-align: center;
            vertical-align: middle;
        }
        .table-dark th {
            color: #d6ff00;
        }
        .filter-bar {
            background-color: #1a1a1a;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .date-filter-group {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .chart-container {
            position: relative;
            height: 400px;
            width: 80%;
            margin: 0 auto;
            margin-top: 20px;
        }
        .chart-container canvas {
            width: 100% !important;
            height: 100% !important;
        }
        #statusChart {
            height: 300px;
            width: 60%;
            margin: 0 auto;
            display: block;
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
                <li class="nav-item"><a class="nav-link" href="staff_dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_users.php">Users</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_trainers.php">Trainers</a></li>
                <li class="nav-item"><a class="nav-link" href="manage_bookings.php">Bookings</a></li>
                <li class="nav-item"><a class="nav-link" href="equipment.php">Equipment</a></li>
                <li class="nav-item"><a class="nav-link" href="gym_capacity.php">Gym Capacity</a></li>
                <li class="nav-item"><a class="nav-link" href="reports.php" style="color: #d6ff00 !important;">Reports</a></li>
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
            <h1>System Reports</h1>
            <p class="text-muted">Analyze booking trends, trainer performance and operational data</p>
        </div>
        <div class="col-auto">
            <a href="?export=csv&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn btn-primary-custom">📥 Export CSV</a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo $overall_stats['total_members']; ?></div>
                <div class="stat-label">Total Members</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo $overall_stats['total_trainers']; ?></div>
                <div class="stat-label">Total Trainers</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo $overall_stats['total_bookings']; ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-number">RM <?php echo number_format($overall_stats['total_revenue'] ?? 0, 2); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>
    </div>

    <div class="report-card">
        <h4 class="report-title">📊 Booking Status Distribution</h4>
        <div class="row">
            <div class="col-md-6">
                <canvas id="statusChart" height="250"></canvas>
            </div>
            <div class="col-md-6">
                <div class="table-responsive">
                    <table class="table table-dark">
                        <thead>
                            <tr><th>Status</th><th>Count</th><th>Percentage</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($status_distribution as $status): ?>
                            <tr>
                                <td><?php echo ucfirst($status['status']); ?></td>
                                <td><?php echo $status['count']; ?></td>
                                <td><?php echo $status['percentage']; ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="report-card">
        <h4 class="report-title">📅 Daily Booking Report (Last 3 Days)</h4>
        <?php if(count($daily_stats) > 0): ?>
        <div class="table-responsive">
            <table class="table table-dark">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Gym</th>
                        <th>Trainer</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Revenue (RM)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($daily_stats as $stat): ?>
                    <tr>
                        <td><?php echo date('d M Y', strtotime($stat['date'])); ?></td>
                        <td><?php echo $stat['gym_bookings']; ?></td>
                        <td><?php echo $stat['trainer_bookings']; ?></td>
                        <td><strong><?php echo $stat['total_bookings']; ?></strong></td>
                        <td><?php echo $stat['paid_bookings']; ?></td>
                        <td>RM <?php echo number_format($stat['daily_revenue'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="alert alert-info text-center mb-0">No booking data available for the last 3 days.</div>
        <?php endif; ?>
    </div>

    <div class="report-card">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
            <h4 class="report-title mb-0">🏋️ Most Popular Gym Sessions (Top 3)</h4>
            <div class="date-filter-group">
                <input type="date" id="popularDatePicker" class="form-control" style="width: auto;" value="<?php echo $popular_date; ?>">
                <button id="goToPopularDate" class="btn btn-primary-custom">Go</button>
                <a href="?popular_date=<?php echo date('Y-m-d'); ?>" class="btn btn-outline-custom">Today</a>
                <a href="?popular_date=<?php echo date('Y-m-d', strtotime('+1 day')); ?>" class="btn btn-outline-custom">Tomorrow</a>
            </div>
        </div>
        <?php if(count($popular_sessions) > 0): ?>
        <div class="table-responsive">
            <table class="table table-dark">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Bookings</th>
                        <th>Capacity</th>
                        <th>Occupancy</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($popular_sessions as $session): ?>
                    <tr>
                        <td><?php echo date('D, M j', strtotime($session['session_date'])); ?></td>
                        <td><?php echo date('g:i A', strtotime($session['start_time'])); ?> - <?php echo date('g:i A', strtotime($session['end_time'])); ?></td>
                        <td><?php echo $session['booking_count']; ?></td>
                        <td><?php echo $session['max_capacity']; ?></td>
                        <td>
                            <div class="progress" style="height: 8px; width: 100px;">
                                <div class="progress-bar" style="width: <?php echo min($session['occupancy_rate'], 100); ?>%; background-color: <?php echo $session['occupancy_rate'] < 50 ? '#22c55e' : ($session['occupancy_rate'] < 80 ? '#f59e0b' : '#ef4444'); ?>;"></div>
                            </div>
                            <span class="ms-2 small"><?php echo $session['occupancy_rate']; ?>%</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="alert alert-info text-center mb-0">No gym sessions found for <?php echo date('D, M j, Y', strtotime($popular_date)); ?>.</div>
        <?php endif; ?>
    </div>

    <div class="report-card">
        <h4 class="report-title">👨‍🏫 Trainer Performance Report</h4>
        <?php if(count($trainer_stats) > 0): ?>
        <div class="table-responsive">
            <table class="table table-dark">
                <thead>
                    <tr>
                        <th>Trainer</th>
                        <th>Specialty</th>
                        <th>Total</th>
                        <th>Approved</th>
                        <th>Pending</th>
                        <th>Paid</th>
                        <th>Approval Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($trainer_stats as $trainer): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($trainer['trainer_name']); ?></td>
                        <td><?php echo htmlspecialchars($trainer['specialty']); ?></td>
                        <td><?php echo $trainer['total_bookings']; ?></td>
                        <td><?php echo $trainer['approved_count']; ?></td>
                        <td><?php echo $trainer['pending_count']; ?></td>
                        <td><?php echo $trainer['paid_count']; ?></td>
                        <td>
                            <div class="progress" style="height: 8px; width: 80px;">
                                <div class="progress-bar" style="width: <?php echo $trainer['approval_rate']; ?>%;"></div>
                            </div>
                            <span class="ms-2 small"><?php echo $trainer['approval_rate']; ?>%</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="alert alert-info text-center mb-0">No trainer performance data available.</div>
        <?php endif; ?>
    </div>

    <div class="report-card">
        <h4 class="report-title">📈 Daily Booking Trend (Last 7 Days)</h4>
        <div class="chart-container">
            <canvas id="trendChart"></canvas>
        </div>
    </div>

    <div class="report-card">
        <h4 class="report-title">👥 Top Active Members (Top 5)</h4>
        <?php if(count($member_activity) > 0): ?>
        <div class="table-responsive">
            <table class="table table-dark">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Email</th>
                        <th>Total</th>
                        <th>Completed</th>
                        <th>Paid</th>
                        <th>Spent (RM)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($member_activity as $member): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($member['name']); ?></td>
                        <td><?php echo htmlspecialchars($member['email']); ?></td>
                        <td><?php echo $member['total_bookings']; ?></td>
                        <td><?php echo $member['completed_bookings']; ?></td>
                        <td><?php echo $member['paid_bookings']; ?></td>
                        <td><?php echo number_format($member['total_spent'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="alert alert-info text-center mb-0">No member activity data available.</div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    var statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: [<?php 
                $labels = '';
                foreach($status_distribution as $s) {
                    $labels .= "'" . ucfirst($s['status']) . "',";
                }
                echo rtrim($labels, ',');
            ?>],
            datasets: [{
                data: [<?php 
                    $data = '';
                    foreach($status_distribution as $s) {
                        $data .= $s['count'] . ',';
                    }
                    echo rtrim($data, ',');
                ?>],
                backgroundColor: ['#22c55e', '#f59e0b', '#ef4444', '#6b7280', '#3b82f6'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom', labels: { color: '#fff' } }
            }
        }
    });

    var trendCanvas = document.getElementById('trendChart');
    if (trendCanvas) {
        var labels = [<?php 
            $labels = '';
            foreach($trend_data as $data) {
                $labels .= "'" . $data['date'] . "',";
            }
            echo rtrim($labels, ',');
        ?>];
        var totals = [<?php 
            $totals = '';
            foreach($trend_data as $data) {
                $totals .= $data['total'] . ',';
            }
            echo rtrim($totals, ',');
        ?>];
        
        var hasData = false;
        for (var i = 0; i < totals.length; i++) {
            if (totals[i] > 0) {
                hasData = true;
                break;
            }
        }
        
        if (labels.length > 0) {
            var trendCtx = trendCanvas.getContext('2d');
            new Chart(trendCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Total Bookings',
                        data: totals,
                        backgroundColor: '#d6ff00',
                        borderColor: '#c0e800',
                        borderWidth: 1,
                        borderRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { labels: { color: '#fff' } },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Bookings: ' + context.raw;
                                }
                            }
                        }
                    },
                    scales: {
                        y: { 
                            ticks: { color: '#fff', stepSize: 1 }, 
                            grid: { color: '#333' },
                            title: { display: true, text: 'Number of Bookings', color: '#fff' }
                        }, 
                        x: { 
                            ticks: { color: '#aaa' },
                            grid: { display: false },
                            title: { display: true, text: 'Date', color: '#fff' }
                        } 
                    }
                }
            });
        } else {
            trendCanvas.parentElement.innerHTML = '<div class="alert alert-warning mb-0">No booking data available for the last 7 days.</div>';
        }
    }

    var goBtn = document.getElementById('goToPopularDate');
    if (goBtn) {
        goBtn.addEventListener('click', function() {
            var selectedDate = document.getElementById('popularDatePicker').value;
            if (selectedDate) {
                window.location.href = '?popular_date=' + selectedDate;
            }
        });
    }
</script>
</body>
</html>