<?php
require_once 'auth.php';
require_once 'db.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $db = getDB();
        // Use prepared statement to prevent SQL injection
        $stmt = $db->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];

            logActivity($user['id'], 'LOGIN', null, null, 'User logged in successfully');
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Student Incident Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1a3a5c 0%, #2d6a9f 100%);
            display: flex; align-items: center; justify-content: center;
            padding: 2rem 1rem;
        }
        .card-wrap {
            width: 100%; max-width: 430px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 24px 64px rgba(0,0,0,.35);
        }
        .card-top {
            background: linear-gradient(135deg,#1a3a5c,#2d6a9f);
            border-radius: 18px 18px 0 0;
            padding: 2.2rem 2rem 1.8rem;
            text-align: center; color: #fff;
        }
        .card-top .icon { font-size: 3rem; color: #7ec8e3; }
        .card-body-inner { padding: 2rem; }
        .form-control:focus { border-color:#2d6a9f; box-shadow:0 0 0 .2rem rgba(45,106,159,.25); }
        .btn-primary-custom {
            background: linear-gradient(135deg,#1a3a5c,#2d6a9f);
            border: none; color: #fff; font-weight: 600;
            padding: .75rem; border-radius: 8px; width: 100%;
            font-size: 1rem; transition: opacity .2s;
        }
        .btn-primary-custom:hover { opacity: .88; color: #fff; }
        .demo-box {
            background: #f0f7ff; border: 1px solid #bee3f8;
            border-radius: 8px; padding: .7rem 1rem; font-size: .84rem;
        }
        .divider { border-top: 1px solid #e9ecef; margin: 1.2rem 0; }
    </style>
</head>
<body>
<div class="card-wrap">
    <div class="card-top">
        <div class="icon"><i class="bi bi-shield-check"></i></div>
        <h4 class="fw-bold mt-1 mb-1">Student Safety System</h4>
        <p class="mb-0 opacity-75 small">Incident Reporting &amp; Management</p>
    </div>
    <div class="card-body-inner">

        <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 py-2">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="mb-3">
                <label class="form-label fw-semibold">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="username" class="form-control"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           placeholder="Enter username" required autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="pwd" class="form-control"
                           placeholder="Enter password" required>
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePwd()">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-primary-custom">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
        </form>

        <div class="demo-box mt-3">
            <strong><i class="bi bi-info-circle me-1"></i>Demo credentials</strong><br>
            Username: <code>admin</code> &nbsp;·&nbsp; Password: <code>password</code>
        </div>

        <div class="divider"></div>

        <p class="text-center mb-0 small">
            Don't have an account?
        </p>
        <a href="register.php" class="btn btn-outline-primary w-100 mt-2">
            <i class="bi bi-person-plus me-2"></i>Create an Account
        </a>

    </div>
    </div>

    // JavaScript to toggle password visibility
 <script>
function togglePwd() {
    const f = document.getElementById('pwd');
    const i = document.getElementById('eyeIcon');
    f.type = f.type === 'password' ? 'text' : 'password';
    i.className = f.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
</script>
</body>
</html>