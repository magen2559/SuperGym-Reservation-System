<?php
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Wed, 11 Jan 1984 05:00:00 GMT");

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$allowed_roles = ['member', 'trainer', 'staff'];
if (!in_array($_SESSION['user_role'], $allowed_roles)) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>