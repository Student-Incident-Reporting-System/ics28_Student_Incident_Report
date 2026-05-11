<?php
// Shared layout — included at the top of every auth page.
// Sets $pageTitle before including this file.

require_once __DIR__ . '/auth.php';
requireLogin();
$user        = currentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>