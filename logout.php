<?php
// ============================================================
// Logout — Destroy session and redirect to login
// ============================================================
require_once 'auth.php';
require_once 'db.php';

if (isLoggedIn()) {
    logActivity($_SESSION['user_id'], 'LOGOUT', null, null, 'User logged out');
}

// Clear all session data
$_SESSION = [];

// Destroy the session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();
header('Location: index.php?logged_out=1');
exit();
?>
