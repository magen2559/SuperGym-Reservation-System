<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="footer-logo">SUPERGYM</div>
                <p class="footer-description">
                    Your ultimate fitness destination. Book gym sessions, train with professional coaches, 
                    and achieve your fitness goals with SuperGym.
                </p>
            </div>

            <div class="col-md-2 mb-4">
                <h5>Quick Links</h5>
                <ul class="footer-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="classes.php">Classes</a></li>
                    <li><a href="trainers.php">Trainers</a></li>
                </ul>
            </div>

            <div class="col-md-3 mb-4">
                <h5>Membership</h5>
                <ul class="footer-links">
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Join Now</a></li>
                    <li><a href="my_bookings.php">My Bookings</a></li>
                    <li><a href="booking_history.php">Booking History</a></li>
                </ul>
            </div>

            <div class="col-md-3 mb-4">
                <h5>Contact Us</h5>
                <ul class="footer-contact">
                    <li>📍 123 Fitness Street, Johor Bahru</li>
                    <li>📞 +60 12-345 6789</li>
                    <li>✉️ info@supergym.com</li>
                    <li>🕐 Mon - Sun: 6:00 AM - 10:00 PM</li>
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
        background-color: #0a0a0a;
        color: #888;
        padding-top: 60px;
        border-top: 1px solid #222;
        margin-top: 60px;
    }

    .footer-logo {
        font-size: 1.8rem;
        font-weight: bold;
        font-style: italic;
        color: #d6ff00;
        margin-bottom: 15px;
    }

    .footer-description {
        font-size: 0.85rem;
        line-height: 1.6;
        margin-bottom: 20px;
        color: #888;
    }

    .footer h5 {
        color: #d6ff00;
        font-size: 1.1rem;
        margin-bottom: 20px;
        font-weight: bold;
        letter-spacing: 1px;
    }

    .footer-links,
    .footer-contact {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-links li,
    .footer-contact li {
        margin-bottom: 10px;
    }

    .footer-links a {
        color: #888;
        text-decoration: none;
        transition: color 0.3s;
        font-size: 0.85rem;
    }

    .footer-links a:hover {
        color: #d6ff00;
        padding-left: 5px;
    }

    .footer-contact li {
        font-size: 0.85rem;
        color: #888;
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
    }
</style>