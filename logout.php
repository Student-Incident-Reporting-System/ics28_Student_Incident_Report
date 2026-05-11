<?php

// Logout — Destroy session and redirect to login

require_once 'auth.php';
require_once 'db.php';

if (isLoggedIn()) {
    logActivity($_SESSION['user_id'], 'LOGOUT', null, null, 'Logged out');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
header('Location: index.php');
exit();

?>
