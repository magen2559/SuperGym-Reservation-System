<?php
require_once 'include/db.php';

$stmt = $pdo->prepare("
    SELECT gs.*, 
           (gs.max_capacity - gs.current_bookings) as available_spots
    FROM gym_sessions gs
    WHERE gs.session_date >= CURDATE()
    ORDER BY gs.session_date, gs.start_time
");
$stmt->execute();
$sessions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperGym - Classes & Schedules</title>
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
            color: #aaa;
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
        .btn-disabled {
            background-color: #444;
            color: #aaa;
            font-weight: bold;
            padding: 8px 20px;
            border-radius: 10px;
            border: none;
            cursor: not-allowed;
        }
        .class-card {
            background-color: #1a1a1a;
            border: 1px solid #333;
            border-radius: 15px;
            transition: transform 0.3s;
            height: 100%;
        }
        .class-card:hover {
            transform: translateY(-5px);
            border-color: #d6ff00;
        }
        .available-spots {
            font-size: 12px;
            padding: 3px 10px;
            border-radius: 20px;
            display: inline-block;
        }
        .spots-low { background-color: #fde047; color: #000; }
        .spots-medium { background-color: #22c55e; color: #000; }
        .spots-high { background-color: #d6ff00; color: #000; }
        .spots-full { background-color: #ef4444; color: #fff; }
        .hero-small {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=2070&auto=format');
            background-size: cover;
            background-position: center;
            padding: 60px 0;
            text-align: center;
            margin-bottom: 40px;
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
        <a class="navbar-brand" href="index.php">SUPERGYM</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon bg-white"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="classes.php" style="color: #d6ff00 !important;">Classes</a></li>
                <li class="nav-item"><a class="nav-link" href="trainers.php">Trainers</a></li>
            </ul>
            <div class="ms-4">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="btn btn-primary-custom me-2">Dashboard</a>
                    <a href="logout.php" class="btn btn-outline-custom">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-custom me-2">Login</a>
                    <a href="register.php" class="btn btn-primary-custom">Join Now</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<div class="hero-small">
    <div class="container">
        <h1>Our Classes</h1>
        <p class="lead">Explore our gym sessions and find the perfect time for your workout</p>
    </div>
</div>

<div class="container my-5">
    <?php if(count($sessions) == 0): ?>
        <div class="alert alert-warning">No upcoming classes available at the moment. Please check back later.</div>
    <?php else: ?>
        <div class="row">
            <?php foreach($sessions as $session): ?>
                <?php 
                $available = $session['available_spots'];
                if ($available <= 0) {
                    $spots_class = 'spots-full';
                    $spots_text = 'Fully Booked';
                } elseif ($available <= 5) {
                    $spots_class = 'spots-low';
                    $spots_text = $available . ' spots left (Low)';
                } elseif ($available <= 10) {
                    $spots_class = 'spots-medium';
                    $spots_text = $available . ' spots left';
                } else {
                    $spots_class = 'spots-high';
                    $spots_text = $available . ' spots left';
                }
                ?>
                <div class="col-md-4 mb-4">
                    <div class="class-card p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h4><?php echo date('D, M j', strtotime($session['session_date'])); ?></h4>
                                <p class="text-muted mb-0"><?php echo date('g:i A', strtotime($session['start_time'])); ?> - <?php echo date('g:i A', strtotime($session['end_time'])); ?></p>
                            </div>
                            <span class="available-spots <?php echo $spots_class; ?>"><?php echo $spots_text; ?></span>
                        </div>
                        
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <?php if($available > 0): ?>
                                <a href="book_gym.php" class="btn btn-primary-custom w-100 mt-2">Book Now</a>
                            <?php else: ?>
                                <button class="btn-disabled w-100 mt-2" disabled>Fully Booked</button>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center mt-2">
                                <a href="register.php" class="btn btn-primary-custom w-100">Join Membership to Book</a>
                                <small class="text-muted d-block mt-2">Register as a member to book this session</small>
                            </div>
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