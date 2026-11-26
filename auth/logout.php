<?php
require_once __DIR__ . '/../config/session.php';
setcookie('ipl_auth', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
$_SESSION = [];
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}
header('Location: ../index.php');
exit();
?>
