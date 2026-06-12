<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="footer-logo">SUPERGYM</div>
                <p class="footer-description">
                    Your ultimate fitness destination. Book gym sessions, train with professional coaches 
                    and achieve your fitness goals with SuperGym.
                </p>
                <div class="footer-social">
                    <a href="https://www.facebook.com/supergymcentre/?locale=ms_MY" target="_blank" class="social-icon" aria-label="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://www.instagram.com/supergymcenter/" target="_blank" class="social-icon" aria-label="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                </div>
            </div>

            <div class="col-md-2 mb-4">
                <h5>QUICK LINKS</h5>
                <ul class="footer-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="classes.php">Sessions</a></li>
                    <li><a href="trainers.php">Trainers</a></li>
                </ul>
            </div>

            <div class="col-md-3 mb-4">
                <h5>MEMBERSHIP</h5>
                <ul class="footer-links">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="my_bookings.php">My Bookings</a></li>
                        <li><a href="booking_history.php">Booking History</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Join Now</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="col-md-3 mb-4">
                <h5>CONTACT US</h5>
                <ul class="footer-contact">
                    <li>
                        <i class="fas fa-phone-alt"></i>
                        <a href="tel:069764434">06-976 4434</a>
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <a href="mailto:supergymcentre18@gmail.com">supergymcentre18@gmail.com</a>
                    </li>
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        <a href="https://www.google.com/maps/search/?api=1&query=46+Jalan+Perdagangan+7+Bukit+Gambir+Johor" target="_blank" rel="noopener noreferrer">
                            46, Jln Perdagangan 7, Bukit Gambir<br>84700 Bukit Gambir, Johor Darul Ta'zim
                        </a>
                    </li>
                    <li>
                        <i class="fas fa-clock"></i>
                        Mon - Sun: 8:00 AM - 10:00 PM
                    </li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> SuperGym Booking System. All rights reserved.</p>
        </div>
    </div>
</footer>

<style>
    .footer {
        background: linear-gradient(180deg, #0a0a0a 0%, #050505 100%);
        color: #888;
        padding-top: 60px;
        border-top: 1px solid #222;
        margin-top: 60px;
    }

    .footer-logo {
        font-size: 2rem;
        font-weight: 800;
        font-style: italic;
        color: #d6ff00;
        margin-bottom: 15px;
        letter-spacing: -0.5px;
    }

    .footer-description {
        font-size: 0.85rem;
        line-height: 1.6;
        margin-bottom: 20px;
        color: #888;
        max-width: 90%;
    }

    .footer h5 {
        color: #d6ff00;
        font-size: 1rem;
        margin-bottom: 20px;
        font-weight: 700;
        letter-spacing: 1.5px;
        text-align: center;
        position: relative;
        display: inline-block;
        width: auto;
        padding-bottom: 10px;
    }
    .footer .col-md-2,
    .footer .col-md-3 {
        text-align: center;
    }
    
    .footer h5::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 100%;
        height: 2px;
        background: #d6ff00;
    }

    .footer-links,
    .footer-contact {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-links li,
    .footer-contact li {
        margin-bottom: 12px;
    }

    .footer-links a {
        color: #888;
        text-decoration: none;
        transition: all 0.3s;
        font-size: 0.85rem;
        display: inline-block;
    }

    .footer-links a:hover {
        color: #d6ff00;
        transform: translateX(5px);
    }

    .footer-contact li {
        font-size: 0.85rem;
        color: #888;
        display: flex;
        align-items: flex-start;
        justify-content: left;
        gap: 12px;
    }

    .footer-contact li i {
        width: 18px;
        color: #d6ff00;
        font-size: 0.9rem;
        margin-top: 2px;
    }

    .footer-contact a {
        color: #888;
        text-decoration: none;
        transition: color 0.3s;
        word-break: break-word;
        text-align: left;
    }

    .footer-contact a:hover {
        color: #d6ff00;
    }

    .footer-social {
        display: flex;
        gap: 12px;
        margin-top: 20px;
    }

    .social-icon {
        width: 38px;
        height: 38px;
        background-color: #1a1a1a;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #888;
        text-decoration: none;
        transition: all 0.3s;
        border: 1px solid #333;
    }

    .social-icon:hover {
        background-color: #d6ff00;
        color: #000;
        transform: translateY(-3px);
        border-color: #d6ff00;
    }

    .footer-bottom {
        border-top: 1px solid #222;
        padding: 20px 0;
        margin-top: 20px;
        font-size: 0.75rem;
        text-align: center;
    }

    @media (max-width: 768px) {
        .footer {
            text-align: center;
            padding-top: 40px;
        }
        .footer-description {
            max-width: 100%;
        }
        .footer-contact li {
            justify-content: center;
        }
        .footer-social {
            justify-content: center;
        }
    }
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">