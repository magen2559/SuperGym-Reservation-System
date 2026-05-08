<?php
require_once 'include/session_check.php';
require_once 'include/db.php';

$role = $_SESSION['user_role'];

if ($role == 'member') {
    header("Location: member_dashboard.php");
    exit();
} elseif ($role == 'trainer') {
    header("Location: trainer_dashboard.php");
    exit();
} elseif ($role == 'staff') {
    header("Location: staff_dashboard.php");
    exit();
} else {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>