<?php
session_start();
require_once 'include/db.php'; 

if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['register'])) {
    
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        echo "<script>
            alert('Please fill in all fields.');
            window.location.href = 'register.php';
        </script>";
        exit();
    }
    
    if (strlen($password) < 6) {
        echo "<script>
            alert('Password must be at least 6 characters long.');
            window.location.href = 'register.php';
        </script>";
        exit();
    }
    
    if ($password !== $confirm_password) {
        echo "<script>
            alert('Passwords do not match. Please try again.');
            window.location.href = 'register.php';
        </script>";
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>
            alert('Please enter a valid email address.');
            window.location.href = 'register.php';
        </script>";
        exit();
    }
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo "<script>
            alert('Email already registered. Please use a different email or login.');
            window.location.href = 'register.php';
        </script>";
        exit();
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'member')");
    
    if ($stmt->execute([$name, $email, $hashed_password])) {
        echo "<script>
            alert('Registration successful! You can now login with your credentials.');
            window.location.href = 'login.php';
        </script>";
        exit();
    } else {
        echo "<script>
            alert('Registration failed. Please try again later.');
            window.location.href = 'register.php';
        </script>";
        exit();
    }
    
} else {
    header("Location: register.php");
    exit();
}
?>