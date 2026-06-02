<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    
    if (empty($name) || empty($email)) {
        $error = "Name and email cannot be empty.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        if ($stmt->execute([$name, $email, $user_id])) {
            $_SESSION['user_name'] = $name;
            $success = "Profile updated successfully!";
        } else {
            $error = "Failed to update profile.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!password_verify($old_password, $user['password'])) {
        $error = "Old password is incorrect.";
    } 
    elseif (strlen($new_password) < 8) {
        $error = "New password must be at least 8 characters long.";
    }
    elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    }
    else {
        $has_upper = preg_match('/[A-Z]/', $new_password);
        $has_lower = preg_match('/[a-z]/', $new_password);
        $has_number = preg_match('/[0-9]/', $new_password);
        $has_special = preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_password);
        
        if (!$has_upper || !$has_lower || !$has_number || !$has_special) {
            $error = "Password must contain at least ONE uppercase letter, ONE lowercase letter, ONE number, and ONE special character (!@#$%^&*).";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed_password, $user_id])) {
                $success = "Password changed successfully!";
            } else {
                $error = "Failed to change password.";
            }
        }
    }
}

$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperGym - My Account</title>
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
            color: #fff !important;
            font-weight: bold;
            text-transform: uppercase;
        }
        .nav-link:hover {
            color: #d6ff00 !important;
        }
        .btn-primary-custom {
            background-color: #d6ff00;
            color: #000;
            font-weight: bold;
            border: none;
            padding: 10px 25px;
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
            background-color: transparent;
        }
        .btn-outline-custom:hover {
            background-color: #d6ff00;
            color: #000;
        }
        .welcome-text {
            color: #ddd;
            font-size: 14px;
            margin-left: 20px;
            padding-left: 20px;
            border-left: 1px solid #555;
        }
        .profile-card {
            background-color: #1a1a1a;
            border: 1px solid #333;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .form-control {
            background-color: #2a2a2a;
            border: 1px solid #333;
            color: #fff;
        }
        .form-control:focus {
            background-color: #2a2a2a;
            border-color: #d6ff00;
            color: #fff;
            box-shadow: none;
        }
        .form-label {
            color: #ddd;
        }
        .password-requirements {
            background-color: #2a2a2a;
            padding: 10px 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 12px;
        }
        .password-requirements ul {
            margin-top: 5px;
            padding-left: 20px;
            margin-bottom: 0;
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
        footer {
            background-color: #0a0a0a;
            padding: 40px;
            text-align: center;
            border-top: 1px solid #222;
            margin-top: 50px;
        }
        h1 {
            color: #fff;
        }
        .text-muted {
            color: #aaa !important;
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
        
        function togglePasswordInfo() {
            const passwordField = document.getElementById('new_password');
            const infoBox = document.getElementById('passwordInfo');
            if (passwordField.value.length > 0) {
                infoBox.style.display = 'block';
                checkPasswordStrength();
            } else {
                infoBox.style.display = 'none';
            }
        }
    </script>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <div class="d-flex align-items-center">
            <a class="navbar-brand" href="index.php">SUPERGYM</a>
            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        </div>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon bg-white"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if($_SESSION['user_role'] == 'member'): ?>
                    <li class="nav-item"><a class="nav-link" href="member_dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="book_gym.php">Book Gym</a></li>
                    <li class="nav-item"><a class="nav-link" href="book_trainer.php">Book Trainer</a></li>
                    <li class="nav-item"><a class="nav-link" href="my_bookings.php">My Bookings</a></li>
                    <li class="nav-item"><a class="nav-link" href="booking_history.php">Booking History</a></li>
                <?php elseif($_SESSION['user_role'] == 'trainer'): ?>
                    <li class="nav-item"><a class="nav-link" href="trainer_dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="trainer_schedule.php">My Schedule</a></li>
                    <li class="nav-item"><a class="nav-link" href="trainer_history.php">History</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="staff_dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_users.php">Users</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_trainers.php">Trainers</a></li>
                    <li class="nav-item"><a class="nav-link" href="equipment.php">Equipment</a></li>
                    <li class="nav-item"><a class="nav-link" href="gym_capacity.php">Gym Capacity</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="profile.php" style="color: #d6ff00 !important;">My Account</a></li>
            </ul>
            <div class="ms-4">
                <a href="logout.php" class="btn btn-outline-custom">Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <h1 class="mb-4">My Account</h1>
            
            <?php if($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="profile-card">
                <h3 class="mb-4">Profile Information</h3>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary-custom">Update Profile</button>
                </form>
            </div>

            <div class="profile-card">
                <h3 class="mb-4">Change Password</h3>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="old_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" id="new_password" class="form-control" 
                               onfocus="togglePasswordInfo()" onkeyup="togglePasswordInfo()" required>
                        <div id="passwordInfo" class="password-requirements" style="display: none;">
                            <strong>Password must contain:</strong>
                            <ul>
                                <li id="req-upper">✗ At least 1 Uppercase letter (A-Z)</li>
                                <li id="req-lower">✗ At least 1 Lowercase letter (a-z)</li>
                                <li id="req-number">✗ At least 1 Number (0-9)</li>
                                <li id="req-special">✗ At least 1 Special character (!@#$%^&*)</li>
                                <li id="req-length">✗ At least 8 characters long</li>
                            </ul>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary-custom">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>