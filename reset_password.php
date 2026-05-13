<?php
session_start();
require_once 'include/db.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if ($token) {
    $stmt = $pdo->prepare("
        SELECT id, email FROM users 
        WHERE reset_token = ? AND reset_expires > NOW()
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $error = "Invalid or expired reset link. Please request a new one.";
    }
}

if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['reset_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $token = $_POST['token'];
    
    if ($new_password != $confirm_password) {
        $error = "Passwords do not match.";
    } 
    elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
    }
    else {
        $has_upper = preg_match('/[A-Z]/', $new_password);
        $has_lower = preg_match('/[a-z]/', $new_password);
        $has_number = preg_match('/[0-9]/', $new_password);
        $has_special = preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_password);
        
        if (!$has_upper || !$has_lower || !$has_number || !$has_special) {
            $error = "Password must contain at least ONE uppercase letter, ONE lowercase letter, ONE number, and ONE special character (!@#$%^&*).";
        } else {
            $stmt = $pdo->prepare("
                SELECT id FROM users 
                WHERE reset_token = ? AND reset_expires > NOW()
            ");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            
            if ($user) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $update = $pdo->prepare("
                    UPDATE users 
                    SET password = ?, reset_token = NULL, reset_expires = NULL 
                    WHERE id = ?
                ");
                $update->execute([$hashed, $user['id']]);
                $success = "Password reset successfully! Please login with your new password.";
            } else {
                $error = "Invalid or expired reset link.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperGym - Reset Password</title>
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
            max-width: 450px;
            border: 1px solid #333;
        }
        h1 {
            color: #d6ff00;
            margin-bottom: 20px;
        }
        input {
            width: 100%;
            padding: 12px;
            background-color: #2a2a2a;
            border: 1px solid #3a3a3a;
            border-radius: 8px;
            color: #fff;
            margin-bottom: 20px;
        }
        input:focus {
            outline: none;
            border-color: #d6ff00;
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
            font-size: 16px;
        }
        button:hover {
            background-color: #c0e800;
        }
        .error {
            background-color: rgba(220, 38, 38, 0.3);
            border: 1px solid #ef4444;
            color: #fca5a5;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .success {
            background-color: rgba(34, 197, 94, 0.3);
            border: 1px solid #22c55e;
            color: #86efac;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .password-requirements {
            background-color: #2a2a2a;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
        }
        .password-requirements ul {
            margin-top: 10px;
            padding-left: 20px;
        }
        .password-requirements li {
            margin: 5px 0;
        }
        .requirement-met {
            color: #86efac;
        }
        .requirement-unmet {
            color: #fca5a5;
        }
        .back-link {
            margin-top: 20px;
            text-align: center;
        }
        .back-link a {
            color: #d6ff00;
            text-decoration: none;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
    <script>
        function checkPasswordStrength() {
            const password = document.getElementById('new_password').value;
            
            const hasUpper = /[A-Z]/.test(password);
            const hasLower = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
            const isLongEnough = password.length >= 8;
            
            document.getElementById('req-upper').innerHTML = hasUpper ? '✓ At least 1 Uppercase letter (A-Z)' : '✗ At least 1 Uppercase letter (A-Z)';
            document.getElementById('req-lower').innerHTML = hasLower ? '✓ At least 1 Lowercase letter (a-z)' : '✗ At least 1 Lowercase letter (a-z)';
            document.getElementById('req-number').innerHTML = hasNumber ? '✓ At least 1 Number (0-9)' : '✗ At least 1 Number (0-9)';
            document.getElementById('req-special').innerHTML = hasSpecial ? '✓ At least 1 Special character (!@#$%^&*)' : '✗ At least 1 Special character (!@#$%^&*)';
            document.getElementById('req-length').innerHTML = isLongEnough ? '✓ At least 8 characters long' : '✗ At least 8 characters long';
            
            document.getElementById('req-upper').style.color = hasUpper ? '#86efac' : '#fca5a5';
            document.getElementById('req-lower').style.color = hasLower ? '#86efac' : '#fca5a5';
            document.getElementById('req-number').style.color = hasNumber ? '#86efac' : '#fca5a5';
            document.getElementById('req-special').style.color = hasSpecial ? '#86efac' : '#fca5a5';
            document.getElementById('req-length').style.color = isLongEnough ? '#86efac' : '#fca5a5';
        }
    </script>
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
            <div class="password-requirements">
                <strong>Password must contain:</strong>
                <ul>
                    <li id="req-upper">✗ At least 1 Uppercase letter (A-Z)</li>
                    <li id="req-lower">✗ At least 1 Lowercase letter (a-z)</li>
                    <li id="req-number">✗ At least 1 Number (0-9)</li>
                    <li id="req-special">✗ At least 1 Special character (!@#$%^&*)</li>
                    <li id="req-length">✗ At least 8 characters long</li>
                </ul>
            </div>
            
            <form action="reset_password.php" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <input type="password" id="new_password" name="new_password" placeholder="New password" onkeyup="checkPasswordStrength()" required>
                <input type="password" name="confirm_password" placeholder="Confirm new password" required>
                <button type="submit" name="reset_password">Reset Password</button>
            </form>
            <div class="back-link"><a href="forgot_password.php">← Back to Forgot Password</a></div>
        <?php elseif(!$token): ?>
            <div class="error">No reset token provided.</div>
            <div class="back-link"><a href="forgot_password.php">Request new reset link →</a></div>
        <?php endif; ?>
    </div>
</body>
</html>