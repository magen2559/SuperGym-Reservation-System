<?php
session_start();
require_once 'include/db.php';

if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['login'])) {
    
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    

    if (empty($email) || empty($password)) {
        echo "<script>
            alert('Please enter both email and password.');
            window.location.href = 'login.php';
        </script>";
        exit();
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        
        if ($user['role'] == 'member') {
            echo "<script>
                alert('Login successful! Welcome " . $user['name'] . "');
                window.location.href = 'member_dashboard.php';
            </script>";
        } elseif ($user['role'] == 'trainer') {
            echo "<script>
                alert('Login successful! Welcome Coach " . $user['name'] . "');
                window.location.href = 'trainer_dashboard.php';
            </script>";
        } elseif ($user['role'] == 'staff') {
            echo "<script>
                alert('Login successful! Welcome Staff " . $user['name'] . "');
                window.location.href = 'staff_dashboard.php';
            </script>";
        } else {
            echo "<script>
                alert('Invalid role. Please contact administrator.');
                window.location.href = 'login.php';
            </script>";
        }
        exit();
    } else {
        echo "<script>
            alert('Invalid email or password. Please try again.');
            window.location.href = 'login.php';
        </script>";
        exit();
    }
    
} else {
    header("Location: login.php");
    exit();
}
?>