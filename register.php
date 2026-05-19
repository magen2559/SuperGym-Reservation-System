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
    <title>SuperGym - Register</title>
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
            background-image: url('https://images.unsplash.com/photo-1571902943202-507ec2618e8f?q=80&w=2070&auto=format');
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
            font-size: 40px;
            font-weight: bold;
            font-style: italic;
            color: #d6ff00;
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
        .form-group input::placeholder {
            color: #6b7280;
            font-size: 14px;
        }
        .password-requirements {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 5px;
        }
        .password-requirements ul {
            margin-top: 5px;
            padding-left: 20px;
            list-style: none;
        }
        .password-requirements li {
            margin: 3px 0;
        }
        .requirement-met {
            color: #86efac;
        }
        .requirement-unmet {
            color: #fca5a5;
        }
        .btn-register {
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
        .btn-register:hover {
            background-color: #c0e800;
        }
        .login-link {
            text-align: center;
            color: #9ca3af;
            margin-top: 24px;
        }
        .login-link a {
            color: #d6ff00;
            text-decoration: none;
        }
        .login-link a:hover {
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
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            
            const hasUpper = /[A-Z]/.test(password);
            const hasLower = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
            const isLongEnough = password.length >= 8;
            
            const upperEl = document.getElementById('req-upper');
            const lowerEl = document.getElementById('req-lower');
            const numberEl = document.getElementById('req-number');
            const specialEl = document.getElementById('req-special');
            const lengthEl = document.getElementById('req-length');
            
            if (hasUpper) {
                upperEl.innerHTML = '✓ At least 1 Uppercase letter (A-Z)';
                upperEl.className = 'requirement-met';
            } else {
                upperEl.innerHTML = '✗ At least 1 Uppercase letter (A-Z)';
                upperEl.className = 'requirement-unmet';
            }
            
            if (hasLower) {
                lowerEl.innerHTML = '✓ At least 1 Lowercase letter (a-z)';
                lowerEl.className = 'requirement-met';
            } else {
                lowerEl.innerHTML = '✗ At least 1 Lowercase letter (a-z)';
                lowerEl.className = 'requirement-unmet';
            }
            
            if (hasNumber) {
                numberEl.innerHTML = '✓ At least 1 Number (0-9)';
                numberEl.className = 'requirement-met';
            } else {
                numberEl.innerHTML = '✗ At least 1 Number (0-9)';
                numberEl.className = 'requirement-unmet';
            }
            
            if (hasSpecial) {
                specialEl.innerHTML = '✓ At least 1 Special character (!@#$%^&*)';
                specialEl.className = 'requirement-met';
            } else {
                specialEl.innerHTML = '✗ At least 1 Special character (!@#$%^&*)';
                specialEl.className = 'requirement-unmet';
            }
            
            if (isLongEnough) {
                lengthEl.innerHTML = '✓ At least 8 characters long';
                lengthEl.className = 'requirement-met';
            } else {
                lengthEl.innerHTML = '✗ At least 8 characters long';
                lengthEl.className = 'requirement-unmet';
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
                <p class="mt-4">JOIN THE ELITE.</p>
            </div>
            <div>
                <p>Become a member today and start your fitness journey with us.</p>
            </div>
        </div>
    </div>

    <div class="right-side">
        <div class="form-box">
            <div class="logo-mobile">
                <h1>SUPERGYM</h1>
            </div>

            <h2>Create Account</h2>
            <p class="subtitle">Join SuperGym today</p>

            <?php if(isset($_GET['error'])): ?>
                <div class="error-msg">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <form action="register_process.php" method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" placeholder="Enter your full name" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="your@email.com" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" id="password" placeholder="Create a password" onkeyup="checkPasswordStrength()" required>
                    <div class="password-requirements">
                        Password must contain:
                        <ul>
                            <li id="req-upper" class="requirement-unmet">✗ At least 1 Uppercase letter (A-Z)</li>
                            <li id="req-lower" class="requirement-unmet">✗ At least 1 Lowercase letter (a-z)</li>
                            <li id="req-number" class="requirement-unmet">✗ At least 1 Number (0-9)</li>
                            <li id="req-special" class="requirement-unmet">✗ At least 1 Special character (!@#$%^&*)</li>
                            <li id="req-length" class="requirement-unmet">✗ At least 8 characters long</li>
                        </ul>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="Confirm your password" required>
                </div>
                <button type="submit" name="register" class="btn-register">REGISTER</button>
            </form>

            <div class="login-link">
                Already have an account? <a href="login.php">Login Here</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>