<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SuperGym - Forgot Password</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background-color: #111;
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background-color: #1a1a1a;
            padding: 40px;
            border-radius: 10px;
            width: 100%;
            max-width: 400px;
        }
        h1 { color: #d6ff00; margin-bottom: 20px; }
        input {
            width: 100%;
            padding: 12px;
            background-color: #2a2a2a;
            border: 1px solid #3a3a3a;
            border-radius: 8px;
            color: #fff;
            margin-bottom: 20px;
        }
        button {
            width: 100%;
            background-color: #d6ff00;
            color: #000;
            font-weight: bold;
            font-size: 16px;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .back-link { margin-top: 20px; text-align: center; }
        .back-link a { color: #d6ff00; text-decoration: none; }
        .message { padding: 10px; border-radius: 8px; margin-bottom: 20px; }
        .error { background-color: rgba(220,38,38,0.3); border: 1px solid #ef4444; color: #fca5a5; }
        .success { background-color: rgba(34,197,94,0.3); border: 1px solid #22c55e; color: #86efac; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Reset Password</h1>
        <p style="margin-bottom: 20px;">Enter your email address and we'll send you a link to reset your password.</p>

        <?php if(isset($_GET['error'])): ?>
            <div class="message error"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <?php if(isset($_GET['success'])): ?>
            <div class="message success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>

        <form action="forgot_password_process.php" method="POST">
            <input type="email" name="email" placeholder="Enter your email" required>
            <button type="submit" name="reset_request">Send Reset Link</button>
        </form>

        <div class="back-link">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>
</body>
</html>