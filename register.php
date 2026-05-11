<?php
// Register Page — create a new staff account

require_once 'auth.php';
require_once 'db.php';

if (isLoggedIn()) { header('Location: dashboard.php'); exit(); }

$errors   = [];
$success  = false;
$f        = [];   // form repopulation

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f['full_name'] = trim($_POST['full_name']   ?? '');
    $f['username']  = trim($_POST['username']    ?? '');
    $f['email']     = trim($_POST['email']       ?? '');
    $password       = $_POST['password']         ?? '';
    $confirm        = $_POST['password_confirm'] ?? '';

    if (!$f['full_name'])  $errors['full_name'] = 'Full name is required.';
    if (!$f['username'])   $errors['username']  = 'Username is required.';
    elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $f['username']))
        $errors['username'] = 'Username: 3–30 chars, letters/numbers/underscore only.';

    if ($f['email'] && !filter_var($f['email'], FILTER_VALIDATE_EMAIL))
        $errors['email'] = 'Enter a valid email address.';

    if (strlen($password) < 8)
        $errors['password'] = 'Password must be at least 8 characters.';
    elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password))
        $errors['password'] = 'Password must contain at least one letter and one number.';

    if ($password !== $confirm)
        $errors['confirm'] = 'Passwords do not match.';

    // Uniqueness checks
    if (empty($errors['username'])) {
        $db = getDB();
        $s  = $db->prepare("SELECT id FROM users WHERE username = ?");
        $s->bind_param('s', $f['username']); $s->execute(); $s->store_result();
        if ($s->num_rows) $errors['username'] = 'Username already taken.';
        $s->close();
    }
    if (empty($errors['email']) && $f['email']) {
        $db = getDB();
        $s  = $db->prepare("SELECT id FROM users WHERE email = ?");
        $s->bind_param('s', $f['email']); $s->execute(); $s->store_result();
        if ($s->num_rows) $errors['email'] = 'Email already registered.';
        $s->close();
    }

    if (empty($errors)) {
        $db   = getDB();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'staff';
        $stmt = $db->prepare("INSERT INTO users (username,password,full_name,role,email) VALUES (?,?,?,?,?)");
        $stmt->bind_param('sssss', $f['username'], $hash, $f['full_name'], $role, $f['email']);
        if ($stmt->execute()) {
            logActivity($stmt->insert_id, 'REGISTER', 'users', $stmt->insert_id, "Registered: {$f['username']}");
            $success = true; $f = [];
        } else {
            $errors['general'] = 'Registration failed. Please try again.';
        }
        $stmt->close();
    }
}
?>