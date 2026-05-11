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

// ── CREATE / UPDATE ─────
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $editId   = isset($_POST['edit_id']) && ctype_digit($_POST['edit_id']) ? (int)$_POST['edit_id'] : null;
    $username = trim($_POST['username']  ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email']     ?? '');
    $role     = $_POST['role']           ?? 'staff';
    $password = $_POST['password']       ?? '';

    if ($username && $fullName) {
        if ($editId) {
            if ($password) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET username=?,full_name=?,email=?,role=?,password=? WHERE id=?");
                $stmt->bind_param('sssssi',$username,$fullName,$email,$role,$hash,$editId);
            } else {
                $stmt = $db->prepare("UPDATE users SET username=?,full_name=?,email=?,role=? WHERE id=?");
                $stmt->bind_param('ssssi',$username,$fullName,$email,$role,$editId);
            }
            $stmt->execute(); $stmt->close();
            logActivity($user['id'],'UPDATE','users',$editId,"Updated $username");
            $msg = 'User updated.';
        } else {
            if (!$password) { $msg='Password required for new users.'; $msgType='danger'; }
            else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username,password,full_name,role,email) VALUES (?,?,?,?,?)");
                $stmt->bind_param('sssss',$username,$hash,$fullName,$role,$email);
                if ($stmt->execute()) {
                    logActivity($user['id'],'CREATE','users',$stmt->insert_id,"Created $username");
                    $msg = "User '$username' created.";
                } else { $msg='Error: Username already exists.'; $msgType='danger'; }
                $stmt->close();
            }
        }
    } else { $msg='Username and full name are required.'; $msgType='danger'; }
}

// ── Edit prefill ─────
if (isset($_GET['edit']) && ctype_digit($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $s = $db->prepare("SELECT id,username,full_name,email,role FROM users WHERE id=?");
    $s->bind_param('i', $editId); $s->execute();
    $edit = $s->get_result()->fetch_assoc(); $s->close();
}

// ── Fetch all users with stats (LEFT JOINs) ──────────────────
$rows = $db->query("
    SELECT u.id, u.username, u.full_name, u.email, u.role, u.created_at,
           COUNT(DISTINCT i.id)  incidents_reported,   -- LEFT JOIN: users with 0 reports still appear
           COUNT(DISTINCT al.id) activity_count         -- LEFT JOIN: users with no logs still appear
    FROM users u
    LEFT JOIN incidents i      ON i.reported_by = u.id  -- LEFT JOIN: count incidents reported
    LEFT JOIN activity_logs al ON al.user_id    = u.id  -- LEFT JOIN: count activity entries
    GROUP BY u.id
    ORDER BY u.role DESC, u.full_name
");

// ── RIGHT JOIN demo: categories that have NO incidents ────────
// RIGHT JOIN returns all categories; WHERE i.id IS NULL filters to unused ones
$unusedCats = $db->query("
    SELECT ic.name, ic.severity_level
    FROM incidents i
    RIGHT JOIN incident_categories ic ON i.category_id = ic.id  -- RIGHT JOIN: all categories
    WHERE i.id IS NULL                                           -- keep only those with no incidents
    ORDER BY ic.name
");
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?> alert-dismissible fade show d-flex align-items-center gap-2">
    <i class="bi bi-<?= $msgType==='success'?'check-circle':'exclamation-circle' ?>"></i>
    <?= htmlspecialchars($msg) ?>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

