<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperGym Reservation System</title>
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
            padding: 8px 16px;
        }
        .nav-link:hover {
            color: #d6ff00 !important;
        }
        .nav-link:focus,
        .nav-link:active {
            color: #d6ff00 !important;
            outline: none;
            box-shadow: none;
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
        }
        .btn-outline-custom:hover {
            background-color: #d6ff00;
            color: #000;
        }
        .hero-section {
            min-height: 700px;
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=2070&auto=format');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
        }
        .stat-box {
            text-align: center;
            padding: 20px;
        }
        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            color: #d6ff00;
        }
        .stat-label {
            text-transform: uppercase;
            color: #aaa;
            font-weight: bold;
            font-size: 0.8rem;
        }
        .cta-section {
            background-color: #d6ff00;
            padding: 80px 20px;
            border-radius: 30px;
            text-align: center;
            margin: 50px 0;
        }
        .cta-section h2 {
            color: #000;
            font-size: 3rem;
            font-weight: bold;
            font-style: italic;
        }
        .cta-section p {
            color: #333;
        }
        .btn-dark-custom {
            background-color: #000;
            color: #d6ff00;
            font-weight: bold;
            padding: 15px 40px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-dark-custom:hover {
            background-color: #222;
            color: #d6ff00;
        }
        footer {
            background-color: #0a0a0a;
            padding: 40px;
            text-align: center;
            border-top: 1px solid #222;
            margin-top: 50px;
        }
        .badge-custom {
            background-color: #d6ff00;
            color: #000;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 20px;
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
                <li class="nav-item"><a class="nav-link" href="classes.php">Gym Sessions</a></li>
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

<section class="hero-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <div class="badge-custom">BOOK • TRAIN • TRANSFORM</div>
                <h1 style="font-size: 4rem; font-weight: bold; font-style: italic;">
                    PUSH BEYOND <br>
                    <span style="color: #d6ff00;">YOUR LIMITS</span>
                </h1>
                <p class="mt-4" style="font-size: 1.2rem; color: #ccc;">
                    Book gym sessions, train with professional coaches and track your progress with SuperGym.
                </p>
                <div class="mt-5">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="dashboard.php" class="btn btn-primary-custom me-3">Go to Dashboard</a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary-custom me-3">Start Your Journey</a>
                        <a href="login.php" class="btn btn-outline-custom">Member Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="container my-5">
    <div class="row">
        <div class="col-md-3 col-6">
            <div class="stat-box">
                <div class="stat-number">24/7</div>
                <div class="stat-label">Access</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-box">
                <div class="stat-number">10+</div>
                <div class="stat-label">Elite Trainers</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-box">
                <div class="stat-number">5000+</div>
                <div class="stat-label">Active Members</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-box">
                <div class="stat-number">50+</div>
                <div class="stat-label">Weekly Classes</div>
            </div>
        </div>
    </div>
</section>

<section class="container">
    <div class="cta-section">
        <h2>Ready to Train?</h2>
        <p class="mt-3">Book your gym session or personal trainer now. First session is free for new members!</p>
        <div class="mt-4">
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="book_gym.php" class="btn-dark-custom">Book Your Session</a>
            <?php else: ?>
                <a href="register.php" class="btn-dark-custom">Book Your Session</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>