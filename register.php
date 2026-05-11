<?php
// ============================================================
// Registration Page — Create a new staff account
// New accounts are assigned the 'staff' role by default.
// Admins can promote users to 'admin' via the Users page.
// ============================================================
require_once 'auth.php';
require_once 'db.php';

// Already logged in? Go to dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$errors  = [];
$success = false;
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['full_name']        = trim($_POST['full_name'] ?? '');
    $formData['username']         = trim($_POST['username'] ?? '');
    $formData['email']            = trim($_POST['email'] ?? '');
    $password                     = $_POST['password'] ?? '';
    $passwordConfirm              = $_POST['password_confirm'] ?? '';

    // --- Validation ---
    if (empty($formData['full_name'])) {
        $errors['full_name'] = 'Full name is required.';
    }

    if (empty($formData['username'])) {
        $errors['username'] = 'Username is required.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $formData['username'])) {
        $errors['username'] = 'Username must be 3–30 characters (letters, numbers, underscores only).';
    }

    if (!empty($formData['email']) && !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Password must contain at least one letter and one number.';
    }

    if ($password !== $passwordConfirm) {
        $errors['password_confirm'] = 'Passwords do not match.';
    }

    // Check username uniqueness
    if (empty($errors['username'])) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param('s', $formData['username']);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors['username'] = 'That username is already taken.';
        }
        $stmt->close();
    }

    // Check email uniqueness (only if provided)
    if (empty($errors['email']) && !empty($formData['email'])) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param('s', $formData['email']);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors['email'] = 'An account with that email already exists.';
        }
        $stmt->close();
    }

    // --- Insert if no errors ---
    if (empty($errors)) {
        $db   = getDB();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'staff'; // New registrations are always staff

        $stmt = $db->prepare(
            "INSERT INTO users (username, password, full_name, role, email) VALUES (?,?,?,?,?)"
        );
        $stmt->bind_param('sssss',
            $formData['username'], $hash, $formData['full_name'], $role, $formData['email']
        );

        if ($stmt->execute()) {
            $newId = $stmt->insert_id;
            logActivity($newId, 'REGISTER', 'users', $newId, "New account registered: {$formData['username']}");
            $success = true;
            $formData = []; // Clear form
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
    <title>Create Account — Student Safety System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a3a5c 0%, #2d6a9f 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .register-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
        }
        .register-header {
            background: linear-gradient(135deg, #1a3a5c, #2d6a9f);
            color: #fff;
            padding: 1.75rem 2rem;
            text-align: center;
        }
        .register-header .shield-icon { font-size: 2.5rem; color: #5dade2; }
        .register-body { padding: 2rem; }
        .form-control:focus, .form-select:focus {
            border-color: #2d6a9f;
            box-shadow: 0 0 0 0.2rem rgba(45,106,159,0.25);
        }
        .btn-register {
            background: linear-gradient(135deg, #1a3a5c, #2d6a9f);
            border: none;
            color: #fff;
            padding: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            transition: opacity 0.2s;
        }
        .btn-register:hover { opacity: 0.9; color: #fff; }
        .is-invalid ~ .invalid-feedback { display: block; }
        .password-strength { height: 4px; border-radius: 2px; transition: all 0.3s; }
    </style>
</head>
<body>
<div class="register-card">
    <div class="register-header">
        <div class="shield-icon mb-1"><i class="bi bi-person-plus"></i></div>
        <h4 class="mb-1 fw-bold">Create Account</h4>
        <p class="mb-0 opacity-75 small">Student Safety &amp; Incident Reporting System</p>
    </div>

    <div class="register-body">

        <?php if ($success): ?>
        <!-- Success State -->
        <div class="text-center py-3">
            <div class="mb-3" style="font-size:3.5rem; color:#27ae60;">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <h5 class="fw-bold mb-2">Account Created!</h5>
            <p class="text-muted mb-4">
                Your staff account has been created successfully.
                You can now sign in with your credentials.
            </p>
            <a href="index.php" class="btn btn-register w-100">
                <i class="bi bi-box-arrow-in-right me-2"></i>Go to Login
            </a>
        </div>

        <?php else: ?>
        <!-- Registration Form -->
        <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?= htmlspecialchars($errors['general']) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="register.php" novalidate id="registerForm">
            <!-- Full Name -->
            <div class="mb-3">
                <label for="full_name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
                           id="full_name" name="full_name"
                           value="<?= htmlspecialchars($formData['full_name'] ?? '') ?>"
                           placeholder="e.g. Jane Doe" required autofocus>
                    <?php if (isset($errors['full_name'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['full_name']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Username -->
            <div class="mb-3">
                <label for="username" class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-at"></i></span>
                    <input type="text" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                           id="username" name="username"
                           value="<?= htmlspecialchars($formData['username'] ?? '') ?>"
                           placeholder="3–30 chars, letters/numbers/underscore" required>
                    <?php if (isset($errors['username'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['username']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Email -->
            <div class="mb-3">
                <label for="email" class="form-label fw-semibold">Email <span class="text-muted small">(optional)</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                           id="email" name="email"
                           value="<?= htmlspecialchars($formData['email'] ?? '') ?>"
                           placeholder="you@school.edu">
                    <?php if (isset($errors['email'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Password -->
            <div class="mb-1">
                <label for="password" class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                           id="password" name="password"
                           placeholder="Min. 8 chars with letters and numbers" required
                           oninput="checkStrength(this.value)">
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('password','eyeIcon1')">
                        <i class="bi bi-eye" id="eyeIcon1"></i>
                    </button>
                    <?php if (isset($errors['password'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['password']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Password strength bar -->
            <div class="mb-3">
                <div class="bg-light rounded" style="height:4px;">
                    <div id="strengthBar" class="password-strength" style="width:0%; background:#e74c3c;"></div>
                </div>
                <small id="strengthLabel" class="text-muted"></small>
            </div>

            <!-- Confirm Password -->
            <div class="mb-4">
                <label for="password_confirm" class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>"
                           id="password_confirm" name="password_confirm"
                           placeholder="Re-enter your password" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('password_confirm','eyeIcon2')">
                        <i class="bi bi-eye" id="eyeIcon2"></i>
                    </button>
                    <?php if (isset($errors['password_confirm'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['password_confirm']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Role note -->
            <div class="alert alert-info d-flex gap-2 align-items-start py-2 mb-4" style="font-size:0.85rem;">
                <i class="bi bi-info-circle-fill mt-1 flex-shrink-0"></i>
                <span>New accounts are created with <strong>Staff</strong> role. An admin can upgrade your role after registration.</span>
            </div>

            <button type="submit" class="btn btn-register w-100">
                <i class="bi bi-person-check me-2"></i>Create Account
            </button>
        </form>

        <p class="text-center mt-3 mb-0 small text-muted">
            Already have an account?
            <a href="index.php" class="text-decoration-none fw-semibold">Sign in</a>
        </p>
        <?php endif; ?>

    </div>
</div>

<script>
function togglePwd(fieldId, iconId) {
    const field = document.getElementById(fieldId);
    const icon  = document.getElementById(iconId);
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        field.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

function checkStrength(val) {
    const bar   = document.getElementById('strengthBar');
    const label = document.getElementById('strengthLabel');
    let score = 0;
    if (val.length >= 8)                          score++;
    if (/[A-Z]/.test(val))                        score++;
    if (/[0-9]/.test(val))                        score++;
    if (/[^A-Za-z0-9]/.test(val))                score++;

    const levels = [
        { pct: '0%',   color: '#e74c3c', text: '' },
        { pct: '25%',  color: '#e74c3c', text: 'Weak' },
        { pct: '50%',  color: '#f39c12', text: 'Fair' },
        { pct: '75%',  color: '#3498db', text: 'Good' },
        { pct: '100%', color: '#27ae60', text: 'Strong' },
    ];
    const lvl = val.length === 0 ? levels[0] : levels[score];
    bar.style.width     = lvl.pct;
    bar.style.background = lvl.color;
    label.textContent   = lvl.text;
    label.style.color   = lvl.color;
}
</script>
</body>
</html>
