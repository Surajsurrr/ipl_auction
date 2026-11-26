<?php
// Session management
if (session_status() == PHP_SESSION_NONE) {
    if (!ini_get('session.save_path') || !is_writable(ini_get('session.save_path'))) {
        @session_save_path('/tmp');
    }
    @ini_set('session.cookie_httponly', 1);
    @ini_set('session.use_only_cookies', 1);
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get current user
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'] ?? $_SESSION['username']
        ];
    }
    return null;
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        $prefix = (strpos($_SERVER['REQUEST_URI'] ?? '', '/ipl_auction') !== false) ? '/ipl_auction' : '';
        header('Location: ' . $prefix . '/auth/login.php');
        exit();
    }
}

// Set flash message
function setFlashMessage($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

// Get and clear flash message
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'];
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}
?>
