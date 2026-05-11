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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — Student Safety System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style> 
               body {
            min-height: 100vh;
            background: linear-gradient(135deg,#1a3a5c 0%,#2d6a9f 100%);
            display: flex; align-items: center; justify-content: center;
            padding: 2rem 1rem;
        }
        .card-wrap {
            width: 100%; max-width: 480px;
            background: #fff; border-radius: 18px;
            box-shadow: 0 24px 64px rgba(0,0,0,.35);
        }
        .card-top {
            background: linear-gradient(135deg,#1a3a5c,#2d6a9f);
            border-radius: 18px 18px 0 0;
            padding: 2rem; text-align: center; color: #fff;
        }
        .card-top .icon { font-size: 2.6rem; color: #7ec8e3; }
        .card-body-inner { padding: 2rem; }
        .form-control:focus { border-color:#2d6a9f; box-shadow:0 0 0 .2rem rgba(45,106,159,.25); }
        .btn-primary-custom {
            background: linear-gradient(135deg,#1a3a5c,#2d6a9f);
            border: none; color: #fff; font-weight: 600;
            padding: .75rem; border-radius: 8px; width: 100%;
            font-size: 1rem; transition: opacity .2s;
        }
        .btn-primary-custom:hover { opacity: .88; color: #fff; }
        .btn-register {
            background: linear-gradient(135deg,#1a3a5c,#2d6a9f);
            border: none; color: #fff; font-weight: 600;
            padding: .75rem; border-radius: 8px;
            font-size: 1rem; transition: opacity .2s;
        }
        .btn-register:hover { opacity: .88; color: #fff; }
        .strength-bar-bg { height: 5px; background: #e9ecef; border-radius: 3px; }
        .strength-bar    { height: 5px; border-radius: 3px; transition: width .3s, background .3s; }
        .divider { border-top: 1px solid #e9ecef; margin: 1.2rem 0; }
    </style>
</head>
<body>
    <div class="card-wrap">
    <div class="card-top">
        <div class="icon"><i class="bi bi-person-plus"></i></div>
        <h4 class="fw-bold mt-1 mb-1">Create Account</h4>
        <p class="mb-0 opacity-75 small">Student Safety &amp; Incident Reporting System</p>
    </div>
    <div class="card-body-inner">

    <?php if ($success): ?>
        <div class="text-center py-3">
            <div style="font-size:3.5rem;color:#27ae60;"><i class="bi bi-check-circle-fill"></i></div>
            <h5 class="fw-bold mt-3 mb-2">Account Created!</h5>
            <p class="text-muted mb-4">Your staff account is ready. Sign in to get started.</p>
            <a href="index.php" class="btn btn-register w-100 text-decoration-none py-2">
                <i class="bi bi-box-arrow-in-right me-2"></i>Go to Login
            </a>
        </div>
    <?php else: ?>

        <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($errors['general']) ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>
            
            <!-- Full Name Ni nga part -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="full_name"
                           class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($f['full_name'] ?? '') ?>"
                           placeholder="e.g. Jane Doe" autofocus>
                    <?php if (isset($errors['full_name'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['full_name']) ?></div>
                    <?php endif; ?>
                </div>
            </div>


    <?php endif; ?>
    </div>
</div>
<script>

</script>
</body>
</html>