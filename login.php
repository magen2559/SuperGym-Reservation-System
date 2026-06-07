<?php
session_start();

if (isset($_GET['redirect'])) {
    $_SESSION['login_redirect'] = $_GET['redirect'];
}
if (isset($_GET['slot_id'])) {
    $_SESSION['booking_slot_id'] = $_GET['slot_id'];
}

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperGym - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: #111;
            color: #fff;
        }
        .container {
            display: flex;
            min-height: 100vh;
        }
        .left-side {
            display: none;
            width: 50%;
            background-image: url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=2070&auto=format');
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .left-side .overlay {
            position: absolute;
            inset: 0;
            background-color: rgba(0,0,0,0.7);
        }
        .left-side .content {
            position: relative;
            z-index: 10;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px;
            height: 100%;
        }
        .left-side h1 {
            font-size: 60px;
            font-weight: bold;
            font-style: italic;
            color: #d6ff00;
        }
        .left-side p {
            color: #9ca3af;
            max-width: 400px;
        }
        .right-side {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px;
        }
        .form-box {
            width: 100%;
            max-width: 400px;
        }
        .logo-mobile {
            text-align: center;
            margin-bottom: 32px;
        }
        .logo-mobile h1 {
            font-weight: bold;
            font-size: 30px;
            color: #d6ff00 !important;
            text-decoration: none;
        }
        .form-box h2 {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .form-box .subtitle {
            color: #9ca3af;
            margin-bottom: 32px;
        }
        .error-msg {
            background-color: rgba(220, 38, 38, 0.3);
            border: 1px solid #ef4444;
            color: #fca5a5;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        .success-msg {
            background-color: rgba(34, 197, 94, 0.3);
            border: 1px solid #22c55e;
            color: #86efac;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            color: #9ca3af;
            margin-bottom: 8px;
        }
        .input-wrapper {
            position: relative;
        }
        .input-wrapper input {
            width: 100%;
            background-color: #2a2a2a;
            border: 1px solid #3a3a3a;
            border-radius: 8px;
            padding: 12px;
            color: #fff;
            font-size: 16px;
        }
        .input-wrapper input:focus {
            outline: none;
            border-color: #d6ff00;
        }
        .password-wrapper {
            position: relative;
        }
        .password-wrapper input {
            width: 100%;
            background-color: #2a2a2a;
            border: 1px solid #3a3a3a;
            border-radius: 8px;
            padding: 12px;
            padding-right: 55px;
            color: #fff;
            font-size: 16px;
        }
        .password-wrapper input:focus {
            outline: none;
            border-color: #d6ff00;
        }
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 11px;
            cursor: pointer;
            color: #d6ff00;
            font-size: 13px;
            font-weight: bold;
        }
        .password-hint {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 5px;
        }
        .btn-login {
            width: 100%;
            background-color: #d6ff00;
            color: #000;
            font-weight: bold;
            padding: 12px;
            border-radius: 8px;
            border: none;
            font-size: 16px;
            cursor: pointer;
            margin-top: 8px;
        }
        .btn-login:hover {
            background-color: #c0e800;
        }
        .register-link {
            text-align: center;
            color: #9ca3af;
            margin-top: 24px;
        }
        .register-link a {
            color: #d6ff00;
            text-decoration: none;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
        @media (min-width: 768px) {
            .left-side {
                display: block;
            }
            .right-side {
                width: 50%;
            }
            .logo-mobile {
                display: none;
            }
        }
    </style>
    <script>
        function togglePassword(inputId, element) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                element.textContent = "Hide";
            } else {
                input.type = "password";
                element.textContent = "Show";
            }
        }
    </script>
</head>
<body>
<div class="container">
    <div class="left-side">
        <div class="overlay"></div>
        <div class="content">
            <div>
                <h1>SUPERGYM</h1>
                <p class="mt-4">YOUR FITNESS JOURNEY STARTS HERE.</p>
            </div>
            <div>
                <p>Book gym sessions, train with professional coaches, and achieve your fitness goals.</p>
            </div>
        </div>
    </div>

    <div class="right-side">
        <div class="form-box">
            <div class="logo-mobile">
                <h1>SUPERGYM</h1>
            </div>

            <h2>Welcome Back</h2>
            <p class="subtitle">Login to book your gym sessions</p>

            <?php if(isset($_GET['error'])): ?>
                <div class="error-msg">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['success'])): ?>
                <div class="success-msg">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <form action="login_process.php" method="POST">
                <?php if(isset($_SESSION['login_redirect'])): ?>
                    <input type="hidden" name="redirect" value="<?php echo $_SESSION['login_redirect']; ?>">
                <?php endif; ?>
                <?php if(isset($_SESSION['booking_slot_id'])): ?>
                    <input type="hidden" name="slot_id" value="<?php echo $_SESSION['booking_slot_id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Email Address</label>
                    <div class="input-wrapper">
                        <input type="email" name="email" placeholder="your@email.com" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <span class="toggle-password" onclick="togglePassword('password', this)">Show</span>
                    </div>
                    <div class="password-hint">
                        Password must contain: Uppercase, Lowercase, Number, Special character, Min 8 characters
                    </div>
                </div>
                <div class="text-right" style="text-align: right; margin-bottom: 16px;">
                    <a href="forgot_password.php" style="color: #d6ff00; text-decoration: none; font-size: 14px;">Forgot Password?</a>
                </div>
                <button type="submit" name="login" class="btn-login">LOGIN</button>
            </form>

            <div class="register-link">
                Don't have an account? <a href="register.php">Register Now</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>