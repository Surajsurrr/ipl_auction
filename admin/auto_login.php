<?php
session_start();
// Temporary auto-login for testing only — removes need to POST credentials
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_username'] = 'admin';
header('Location: dashboard.php');
exit();
?>