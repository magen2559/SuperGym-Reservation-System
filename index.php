<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperGym - Push Beyond Your Limits</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background-color: #111;
            color: #fff;
            font-family: 'Segoe UI', 'Poppins', 'Arial', sans-serif;
            overflow-x: hidden;
        }
        .navbar {
            background-color: #1a1a1a;
            border-bottom: 1px solid #333;
            padding: 6px;
            transition: all 0.3s;
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
            transition: color 0.3s;
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
            transition: all 0.3s;
        }
        .btn-primary-custom:hover {
            background-color: #c0e800;
            color: #000;
            transform: translateY(-2px);
        }
        .btn-outline-custom {
            border: 2px solid #d6ff00;
            color: #d6ff00;
            font-weight: bold;
            padding: 8px 20px;
            border-radius: 10px;
            text-decoration: none;
            background-color: transparent;
            transition: all 0.3s;
        }
        .btn-outline-custom:hover {
            background-color: #d6ff00;
            color: #000;
        }
        .hero-carousel {
            height: 100vh;
            min-height: 600px;
        }
        .carousel-item {
            height: 100vh;
            min-height: 600px;
            position: relative;
        }
        .carousel-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.6) 100%);
            z-index: 1;
        }
        .carousel-item img {
            object-fit: cover;
            height: 100%;
            width: 100%;
        }
        .carousel-caption {
            bottom: 50%;
            transform: translateY(50%);
            z-index: 2;
            text-align: left;
            left: 10%;
            right: auto;
            width: 50%;
        }
        .carousel-caption .badge {
            background-color: #d6ff00;
            color: #000;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: bold;
            margin-bottom: 20px;
            display: inline-block;
        }
        .carousel-caption h1 {
            font-size: 4rem;
            font-weight: bold;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        .carousel-caption h1 span {
            color: #d6ff00;
        }
        .carousel-caption p {
            font-size: 1.1rem;
            color: #ccc;
            margin-bottom: 30px;
        }
        .carousel-control-prev, .carousel-control-next {
            width: 5%;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .hero-carousel:hover .carousel-control-prev,
        .hero-carousel:hover .carousel-control-next {
            opacity: 1;
        }
        .carousel-indicators button {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #fff;
            margin: 0 8px;
        }
        .carousel-indicators button.active {
            background-color: #d6ff00;
            transform: scale(1.2);
        }
        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }
        .section-title h2 {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 15px;
            color: #fff;
        }
        .section-title h2 span {
            color: #d6ff00;
        }
        .section-title p {
            color: #aaa;
            font-size: 1rem;
        }
        .features-section {
            padding: 80px 0;
            background-color: #111;
        }
        .feature-card {
            background-color: #1a1a1a;
            border-radius: 15px;
            padding: 40px 30px;
            text-align: center;
            border: 1px solid #333;
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s cubic-bezier(0.2, 0.9, 0.4, 1.1);
        }
        .feature-card.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .feature-card:hover {
            transform: translateY(-10px);
            border-color: #d6ff00;
        }
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #d6ff00 0%, #b8e000 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 2rem;
            color: #000;
        }
        .feature-card h3 {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 15px;
            color: #fff;
        }
        .feature-card p {
            color: #aaa;
            font-size: 0.9rem;
            line-height: 1.6;
        }
        .trainer-showcase {
            padding: 80px 0;
            background-color: #0a0a0a;
        }
        .trainer-card {
            background-color: #1a1a1a;
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid #333;
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s cubic-bezier(0.2, 0.9, 0.4, 1.1);
        }
        .trainer-card.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .trainer-card:hover {
            transform: translateY(-10px);
            border-color: #d6ff00;
        }
        .trainer-image {
            height: 300px;
            overflow: hidden;
        }
        .trainer-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        .trainer-card:hover .trainer-image img {
            transform: scale(1.1);
        }
        .trainer-info {
            padding: 25px;
            text-align: center;
        }
        .trainer-info h4 {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 5px;
            color: #fff;
        }
        .trainer-info p {
            color: #d6ff00;
            font-size: 0.85rem;
            margin-bottom: 15px;
        }
        .trainer-info .btn-sm {
            background: transparent;
            border: 1px solid #d6ff00;
            color: #d6ff00;
            padding: 6px 20px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: bold;
            transition: all 0.3s;
            display: inline-block;
            text-decoration: none;
        }
        .trainer-info .btn-sm:hover {
            background: #d6ff00;
            color: #000;
        }
        .cta-section {
            background-color: #d6ff00;
            padding: 80px 20px;
            text-align: center;
            margin: 50px 0;
            width: 100%;
            border-radius: 0;
        }
        .cta-section h2 {
            color: #000;
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .cta-section p {
            color: #333;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }
        .btn-dark-custom {
            background-color: #000;
            color: #d6ff00;
            font-weight: bold;
            padding: 14px 40px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-dark-custom:hover {
            background-color: #222;
            color: #d6ff00;
            transform: translateY(-2px);
        }
        footer {
            background-color: #0a0a0a;
            padding: 40px;
            text-align: center;
            border-top: 1px solid #222;
            margin-top: 50px;
        }

        @media (max-width: 768px) {
            .carousel-caption {
                width: 80%;
                left: 10%;
                bottom: 40%;
                transform: translateY(50%);
            }
            .carousel-caption h1 {
                font-size: 2rem;
            }
            .section-title h2 {
                font-size: 1.8rem;
            }
            .feature-card, .trainer-card {
                margin-bottom: 20px;
            }
            .cta-section {
                padding: 50px 20px;
            }
            .cta-section h2 {
                font-size: 1.8rem;
            }
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

<div id="heroCarousel" class="carousel slide hero-carousel" data-bs-ride="carousel" data-bs-interval="5000">
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
    </div>
    <div class="carousel-inner">
        <div class="carousel-item active">
            <img src="https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=2070&auto=format" alt="Gym Workout">
            <div class="carousel-caption">
                <div class="badge">LIMITLESS POTENTIAL</div>
                <h1>PUSH BEYOND <br><span>YOUR LIMITS</span></h1>
                <p>Experience world-class facilities and professional trainers dedicated to your success.</p>
                <div class="mt-4">
                    <a href="register.php" class="btn btn-primary-custom">Start Your Journey</a>
                </div>
            </div>
        </div>
        <div class="carousel-item">
            <img src="https://images.unsplash.com/photo-1571902943202-507ec2618e8f?q=80&w=2070&auto=format" alt="Personal Training">
            <div class="carousel-caption">
                <div class="badge">EXPERT GUIDANCE</div>
                <h1>TRAIN WITH <br><span>ELITE COACHES</span></h1>
                <p>Get personalized training plans from certified professionals who care about your progress.</p>
                <div class="mt-4">
                    <a href="trainers.php" class="btn btn-primary-custom">Meet Our Trainers</a>
                </div>
            </div>
        </div>
        <div class="carousel-item">
            <img src="https://images.unsplash.com/photo-1517836357463-d25dfeac3438?q=80&w=2070&auto=format" alt="Group Classes">
            <div class="carousel-caption">
                <div class="badge">COMMUNITY POWER</div>
                <h1>WORKOUT <br><span>TOGETHER</span></h1>
                <p>Join our vibrant community and stay motivated with group sessions and events.</p>
                <div class="mt-4">
                    <a href="classes.php" class="btn btn-primary-custom">View Sessions</a>
                </div>
            </div>
        </div>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
    </button>
</div>

<section class="features-section">
    <div class="container">
        <div class="section-title">
            <h2>Why Choose <span>SuperGym</span></h2>
            <p>Experience fitness like never before with our premium amenities and services</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card scroll-animate">
                    <div class="feature-icon"><i class="fas fa-dumbbell"></i></div>
                    <h3>Modern Equipment</h3>
                    <p>State-of-the-art machines and free weights from leading brands for optimal results.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card scroll-animate">
                    <div class="feature-icon"><i class="fas fa-chalkboard-user"></i></div>
                    <h3>Expert Trainers</h3>
                    <p>Certified professionals with years of experience to guide you every step.</p>
                </div>
            </div>
            <div class="col-md-4">
            <div class="feature-card scroll-animate">
                <div class="feature-icon"><i class="fas fa-clock"></i></div>
                <h3>Flexible Hours</h3>
                <p>Open daily from 8:00 AM to 10:00 PM.<br>Book gym sessions anytime that fits your schedule.</p>
            </div>
        </div>
        </div>
    </div>
</section>

<section class="trainer-showcase">
    <div class="container">
        <div class="section-title">
            <h2>Meet Our <span>Elite Trainers</span></h2>
            <p>World-class coaches dedicated to helping you achieve greatness</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="trainer-card scroll-animate">
                    <div class="trainer-image">
                        <img src="https://images.pexels.com/photos/4976936/pexels-photo-4976936.jpeg" alt="Trainer">
                    </div>
                    <div class="trainer-info">
                        <h4>Frank</h4>
                        <p> HIIT Training</p>
                        <a href="trainers.php" class="btn-sm">View All Trainers →</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="trainer-card scroll-animate">
                    <div class="trainer-image">
                        <img src="https://images.pexels.com/photos/4720822/pexels-photo-4720822.jpeg" alt="Trainer">
                    </div>
                    <div class="trainer-info">
                        <h4>Pond</h4>
                        <p>CrossFit</p>
                        <a href="trainers.php" class="btn-sm">View All Trainers →</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="trainer-card scroll-animate">
                    <div class="trainer-image">
                        <img src="https://images.pexels.com/photos/3768695/pexels-photo-3768695.jpeg" alt="Trainer">
                    </div>
                    <div class="trainer-info">
                        <h4>Anna</h4>
                        <p>Zumba Dance</p>
                        <a href="trainers.php" class="btn-sm">View All Trainers →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="container">
        <h2>Ready to Transform Your Life?</h2>
        <p>Join SuperGym today and start your fitness journey with us.</p>
        <div>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="book_gym.php" class="btn-dark-custom">Book Your Session</a>
            <?php else: ?>
                <a href="register.php" class="btn-dark-custom">Join Now →</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const animateElements = document.querySelectorAll('.scroll-animate');
    
    function checkScroll() {
        animateElements.forEach(element => {
            const elementTop = element.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            
            if (elementTop < windowHeight - 100) {
                element.classList.add('visible');
            }
        });
    }
    
    window.addEventListener('scroll', checkScroll);
    window.addEventListener('load', checkScroll);
</script>
</body>
</html>