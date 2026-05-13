<?php
session_start();
require_once 'include/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['reset_request'])) {

    $email = trim($_POST['email']);

    if (empty($email)) {
        header("Location: forgot_password.php?error=Please enter your email");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: forgot_password.php?error=Please enter a valid email address");
        exit();
    }

    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));

        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $update = $pdo->prepare("
            UPDATE users
            SET reset_token = ?, reset_expires = ?
            WHERE email = ?
        ");
        $update->execute([$token, $expires, $email]);

        $reset_link = "http://localhost/gymsystem/reset_password.php?token=" . $token;

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'shuminnn0322@gmail.com';
            $mail->Password = 'lmojijqsomblcdbd';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('shuminnn0322@gmail.com', 'SuperGym');

            $mail->addAddress($email, $user['name']);

            $mail->isHTML(true);

            $mail->Subject = 'SuperGym Password Reset';

            $mail->Body = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        .container { padding: 20px; background-color: #f4f4f4; }
                        .header { background-color: #d6ff00; padding: 10px; text-align: center; }
                        .content { padding: 20px; background-color: #fff; color: #333; }
                        .footer { font-size: 12px; color: #666; text-align: center; margin-top: 20px; }
                        .password-requirements { background-color: #f0f0f0; padding: 10px; margin: 15px 0; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>💪 SUPERGYM</h2>
                        </div>
                        <div class='content'>
                            <p>Hello <strong>{$user['name']}</strong>,</p>
                            <p>We received a request to reset your password. Click the button below to reset it:</p>
                            <p><a href='$reset_link' style='background-color: #d6ff00; color: #000; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
                            <p>Or copy this link: <a href='$reset_link'>$reset_link</a></p>
                            
                            <div class='password-requirements'>
                                <strong>🔐 Password Requirements:</strong><br>
                                Your new password must contain:<br>
                                • Uppercase letter (A-Z)<br>
                                • Lowercase letter (a-z)<br>
                                • Number (0-9)<br>
                                • Special character (!@#$%^&*)<br>
                                • Minimum 8 characters
                            </div>
                            
                            <p>This link <strong>expires in 1 hour</strong>.</p>
                            <p>If you didn't request this, please ignore this email.</p>
                        </div>
                        <div class='footer'>
                            <p>© 2024 SuperGym Booking System. All Rights Reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            $mail->AltBody = "SuperGym Password Reset\n\n"
                . "Hello {$user['name']},\n\n"
                . "Click this link to reset your password:\n"
                . "$reset_link\n\n"
                . "Password Requirements:\n"
                . "- Uppercase letter (A-Z)\n"
                . "- Lowercase letter (a-z)\n"
                . "- Number (0-9)\n"
                . "- Special character (!@#$%^&*)\n"
                . "- Minimum 8 characters\n\n"
                . "This link expires in 1 hour.\n\n"
                . "If you didn't request this, please ignore this email.";

            $mail->send();

            header("Location: forgot_password.php?success=Reset link sent to your email!");
            exit();

        } catch (Exception $e) {
            header("Location: forgot_password.php?error=Mailer Error: " . urlencode($mail->ErrorInfo));
            exit();
        }

    } else {
        header("Location: forgot_password.php?success=If the email exists, a reset link has been sent.");
        exit();
    }

} else {
    header("Location: forgot_password.php");
    exit();
}
?>