<?php
session_start();
require_once 'include/db.php';

if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['reset_request'])) {
    
    $email = trim($_POST['email']);
    
    if(empty($email)) {
        header("Location: forgot_password.php?error=Please enter your email");
        exit();
    }
    
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
        $update->execute([$token, $expires, $email]);
        
        $reset_link = "http://localhost/gymsystem/reset_password.php?token=" . $token;
        
        echo "<script>
            alert('Password reset link: " . $reset_link . "\\n\\n[Note: In production, this would be sent to your email.]');
            window.location.href = 'login.php';
        </script>";
    } else {
        header("Location: forgot_password.php?error=Email not found in our system");
        exit();
    }
} else {
    header("Location: forgot_password.php");
    exit();
}
?>