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

<!-- RIGHT JOIN info panel -->
<?php $unusedRows = []; while ($r = $unusedCats->fetch_assoc()) $unusedRows[] = $r; ?>
<?php if ($unusedRows): ?>
<div class="alert alert-info d-flex gap-2 align-items-start mb-4" style="font-size:.87rem;">
    <i class="bi bi-info-circle-fill mt-1 flex-shrink-0"></i>
    <div>
        <strong>RIGHT JOIN insight:</strong> The following categories have no incidents recorded yet:
        <?php foreach ($unusedRows as $r): ?>
        <span class="badge b-<?= $r['severity_level'] ?> ms-1"><?= htmlspecialchars($r['name']) ?></span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Form -->
<div class="kcard mb-4">
    <div class="p-3 border-bottom fw-semibold">
        <i class="bi bi-<?= $edit?'pencil-square':'person-plus' ?> me-2 text-primary"></i>
        <?= $edit ? 'Edit User' : 'Add New User' ?>
    </div>
    <div class="p-3">
        <form method="POST">
            <?php if ($edit): ?><input type="hidden" name="edit_id" value="<?= $edit['id'] ?>"><?php endif; ?>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control" required
                           value="<?= htmlspecialchars($edit['username'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control" required
                           value="<?= htmlspecialchars($edit['full_name'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($edit['email'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Role</label>
                    <select name="role" class="form-select">
                        <option value="staff" <?= ($edit && $edit['role']==='staff')?'selected':'' ?>>Staff</option>
                        <option value="admin" <?= ($edit && $edit['role']==='admin')?'selected':'' ?>>Admin</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        Password <?= $edit ? '<small class="text-muted fw-normal">(blank = keep current)</small>' : '<span class="text-danger">*</span>' ?>
                    </label>
                    <input type="password" name="password" class="form-control"
                           <?= !$edit?'required':'' ?>
                           placeholder="<?= $edit?'Leave blank to keep current':'Enter password' ?>">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-<?= $edit?'save':'plus-lg' ?> me-1"></i>
                        <?= $edit ? 'Update User' : 'Create User' ?>
                    </button>
                    <?php if ($edit): ?>
                    <a href="users.php" class="btn btn-outline-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="kcard">
    <div class="p-3 border-bottom fw-semibold">
        <i class="bi bi-people me-2 text-primary"></i>System Users
        <small class="text-muted ms-1">(LEFT JOIN: incidents reported · activity logs)</small>
    </div>
    <div class="table-responsive p-2">
        <table class="table table-hover align-middle" id="tblUsers">
            <thead class="table-light">
                <tr>
                    <th>Username</th><th>Full Name</th><th>Email</th><th>Role</th>
                    <th>Reports</th><th>Activity</th><th>Joined</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $rows->fetch_assoc()): ?>
                <tr>
                    <td><code><?= htmlspecialchars($row['username']) ?></code></td>
                    <td class="fw-semibold"><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['email'] ?? '—') ?></td>
                    <td><span class="badge rounded-pill <?= $row['role']==='admin'?'bg-danger':'bg-primary' ?>"><?= ucfirst($row['role']) ?></span></td>
                    <td><?= $row['incidents_reported'] ?></td>
                    <td><?= $row['activity_count'] ?></td>
                    <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                            <?php if ($row['id'] !== $user['id']): ?>
                            <button class="btn btn-sm btn-outline-danger" title="Delete"
                                    onclick="confirmDel(<?= $row['id'] ?>,'<?= htmlspecialchars($row['username']) ?>')">
                                <i class="bi bi-trash"></i>
                            </button>
                            <?php else: ?>
                            <span class="btn btn-sm btn-outline-secondary disabled"><i class="bi bi-lock"></i></span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Delete modal -->
<div class="modal fade" id="delModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Confirm Delete</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">Delete user <strong id="delCode"></strong>? This cannot be undone.</div>
            <div class="modal-footer border-0">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a id="delBtn" href="#" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>
