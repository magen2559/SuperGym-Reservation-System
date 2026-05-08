<?php
session_start();
require_once 'include/db.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if($token) {
    $stmt = $pdo->prepare("SELECT id, email FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if(!$user) {
        $error = "Invalid or expired reset link. Please request a new one.";
    }
}

if($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['reset_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $token = $_POST['token'];
    
    if($new_password != $confirm_password) {
        $error = "Passwords do not match";
    } elseif(strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if($user) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
            $update->execute([$hashed, $user['id']]);
            $success = "Password reset successfully! Please login with your new password.";
        } else {
            $error = "Invalid or expired reset link";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SuperGym - Reset Password</title>
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
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .error { color: #fca5a5; margin-bottom: 20px; }
        .success { color: #86efac; margin-bottom: 20px; }
        .back-link { margin-top: 20px; text-align: center; }
        .back-link a { color: #d6ff00; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Reset Password</h1>
        
        <?php if($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="success"><?php echo $success; ?></div>
            <div class="back-link"><a href="login.php">Go to Login →</a></div>
        <?php elseif($token && !$error): ?>
            <form action="reset_password.php" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <input type="password" name="new_password" placeholder="New password" required>
                <input type="password" name="confirm_password" placeholder="Confirm new password" required>
                <button type="submit" name="reset_password">Reset Password</button>
            </form>
            <div class="back-link"><a href="login.php">← Back to Login</a></div>
        <?php elseif(!$token): ?>
            <div class="error">No reset token provided.</div>
            <div class="back-link"><a href="forgot_password.php">Request new reset link →</a></div>
        <?php endif; ?>
    </div>
</body>
</html>