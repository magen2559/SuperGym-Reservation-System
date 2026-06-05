<?php
require_once 'include/db.php';

$stmt = $pdo->prepare("
    SELECT u.name, t.specialty, t.bio 
    FROM trainers t 
    JOIN users u ON u.id = t.user_id
");
$stmt->execute();
$trainers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperGym - Our Trainers</title>
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
        .btn-outline-custom {
            border: 2px solid #d6ff00;
            color: #d6ff00;
            font-weight: bold;
            padding: 8px 20px;
            border-radius: 10px;
            text-decoration: none;
            background-color: transparent;
        }
        .trainer-card {
            background-color: #1a1a1a;
            border: 1px solid #333;
            border-radius: 15px;
            transition: transform 0.3s;
            height: 100%;
            text-align: center;
            padding: 30px;
        }
        .trainer-card:hover {
            transform: translateY(-5px);
            border-color: #d6ff00;
        }
        .trainer-icon {
            font-size: 4rem;
            margin-bottom: 15px;
        }
        .trainer-name {
            color: #d6ff00;
            font-size: 1.5rem;
            font-weight: bold;
        }
        .hero-small {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1571902943202-507ec2618e8f?q=80&w=2070&auto=format');
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
                <li class="nav-item"><a class="nav-link" href="classes.php">Classes</a></li>
                <li class="nav-item"><a class="nav-link" href="trainers.php" style="color: #d6ff00 !important;">Trainers</a></li>
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
        <h1>Our Elite Trainers</h1>
        <p class="lead">Meet our professional coaches ready to guide you</p>
    </div>
</div>

<div class="container my-5">
    <div class="row">
        <?php if(count($trainers) > 0): ?>
            <?php foreach($trainers as $trainer): ?>
                <div class="col-md-4 mb-4">
                    <div class="trainer-card">
                        <div class="trainer-icon">👨‍🏫</div>
                        <h3 class="trainer-name"><?php echo htmlspecialchars($trainer['name']); ?></h3>
                        <p class="text-warning"><?php echo htmlspecialchars($trainer['specialty'] ?? 'Fitness Coach'); ?></p>
                        <p class="text-muted small"><?php echo htmlspecialchars(substr($trainer['bio'] ?? 'Professional trainer dedicated to helping you achieve your fitness goals.', 0, 100)); ?></p>
                        <a href="book_trainer.php" class="btn btn-primary-custom mt-3">Book Session</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">Trainer profiles coming soon!</div>
            </div>
        <?php endif; ?>
    </div>
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