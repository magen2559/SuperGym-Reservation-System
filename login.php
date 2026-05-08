<?php
session_start();
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
            padding-left: 15px;
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
        .form-group input {
            width: 100%;
            background-color: #2a2a2a;
            border: 1px solid #3a3a3a;
            border-radius: 8px;
            padding: 12px;
            color: #fff;
            font-size: 16px;
        }
        .form-group input:focus {
            outline: none;
            border-color: #d6ff00;
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
        .demo-box {
            margin-top: 32px;
            padding: 16px;
            background-color: #1a1a1a;
            border-radius: 8px;
            text-align: center;
        }
        .demo-box p {
            font-size: 12px;
            color: #6b7280;
            margin: 4px 0;
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
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="text-right" style="text-align: right; margin-bottom: 16px;">
                    <a href="forgot_password.php" style="color: #d6ff00; text-decoration: none; font-size: 14px;">Forgot Password?</a>
                </div>
                <button type="submit" name="login" class="btn-login">LOGIN</button>
            </form>

            <div class="register-link">
                Don't have an account? <a href="register.php">Register Now</a>
            </div>

            <div class="demo-box">
                <p>Demo Credentials:</p>
                <p>member@supergym.com / password</p>
                <p>trainer@supergym.com / password</p>
                <p>staff@supergym.com / password</p>
            </div>
        </div>
    </div>
</div>
</body>
</html>