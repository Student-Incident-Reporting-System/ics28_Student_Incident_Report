<?php

// Users — Admin-only CRUD
// SQL joins:
//   LEFT JOIN users ↔ incidents      — count reports per user
//   LEFT JOIN users ↔ activity_logs  — count actions per user
//   RIGHT JOIN demo: categories with no incidents (read-only info panel)

$pageTitle = 'User Management';
require_once 'db.php';
require_once 'auth.php';
requireAdmin();
require_once 'layout.php';

$db   = getDB();
$user = currentUser();
$msg  = ''; $msgType = 'success';