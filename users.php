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

// ── DELETE ────
if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id === $user['id']) {
        $msg = 'You cannot delete your own account.'; $msgType = 'danger';
    } else {
        $stmt = $db->prepare("SELECT username FROM users WHERE id=?");
        $stmt->bind_param('i',$id); $stmt->execute();
        $row  = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if ($row) {
            $del = $db->prepare("DELETE FROM users WHERE id=?");
            $del->bind_param('i',$id); $del->execute(); $del->close();
            logActivity($user['id'],'DELETE','users',$id,"Deleted {$row['username']}");
            $msg = "User '{$row['username']}' deleted.";
        }
    }
}