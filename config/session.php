<?php
// Session management with serverless persistent authentication
if (session_status() == PHP_SESSION_NONE) {
    if (!ini_get('session.save_path') || !is_writable(ini_get('session.save_path'))) {
        @session_save_path('/tmp');
    }
    @ini_set('session.cookie_httponly', 1);
    @ini_set('session.use_only_cookies', 1);
    @ini_set('session.cookie_lifetime', 86400 * 30); // 30 days
    @ini_set('session.gc_maxlifetime', 86400 * 30);
    session_start();
}

define('AUTH_SECRET_KEY', 'ipl_auction_secure_salt_2026_vercel_persistent');

/**
 * Sign an auth payload
 */
function signAuthToken($userId, $username, $fullName = '') {
    $data = $userId . '|' . $username . '|' . $fullName;
    $hash = hash_hmac('sha256', $data, AUTH_SECRET_KEY);
    return base64_encode($data . '|' . $hash);
}

/**
 * Verify and decode a signed auth payload
 */
function verifyAuthToken($token) {
    if (empty($token)) return null;
    $decoded = base64_decode($token, true);
    if (!$decoded) return null;
    $parts = explode('|', $decoded);
    if (count($parts) < 4) return null;
    $userId = $parts[0];
    $username = $parts[1];
    $fullName = $parts[2];
    $signature = $parts[3];
    $expectedHash = hash_hmac('sha256', $userId . '|' . $username . '|' . $fullName, AUTH_SECRET_KEY);
    if (hash_equals($expectedHash, $signature)) {
        return [
            'user_id' => intval($userId),
            'username' => $username,
            'full_name' => $fullName
        ];
    }
    return null;
}

/**
 * Re-hydrate session from signed cookie if session file was lost across ephemeral serverless containers
 */
function restoreSessionFromCookie() {
    if (!empty($_SESSION['user_id'])) {
        return;
    }
    if (!empty($_COOKIE['ipl_auth'])) {
        $userData = verifyAuthToken($_COOKIE['ipl_auth']);
        if ($userData) {
            $_SESSION['user_id'] = $userData['user_id'];
            $_SESSION['username'] = $userData['username'];
            $_SESSION['full_name'] = $userData['full_name'];
        }
    }
}

// Automatically restore on every request
restoreSessionFromCookie();

// Check if user is logged in
function isLoggedIn() {
    restoreSessionFromCookie();
    return !empty($_SESSION['user_id']);
}

// Get current user
function getCurrentUser() {
    restoreSessionFromCookie();
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
