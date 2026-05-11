<?php
// ============================================================
// Users — Admin-only CRUD
// SQL Joins:
//   LEFT JOIN users ↔ incidents (reported_by): count reports per user
//   LEFT JOIN users ↔ activity_logs: count actions per user
// ============================================================
$pageTitle = 'User Management';
require_once 'db.php';
require_once 'auth.php';
requireAdmin();   // Only admins can manage users
require_once 'layout_header.php';

$db   = getDB();
$user = currentUser();
$msg  = '';
$msgType = 'success';

// ---- DELETE ----
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id === $user['id']) {
        $msg = 'You cannot delete your own account.';
        $msgType = 'danger';
    } else {
        $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $del = $db->prepare("DELETE FROM users WHERE id = ?");
            $del->bind_param('i', $id);
            $del->execute();
            $del->close();
            logActivity($user['id'], 'DELETE', 'users', $id, "Deleted user {$row['username']}");
            $msg = "User '{$row['username']}' deleted.";
        }
    }
}

// ---- CREATE / UPDATE ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $editId   = isset($_POST['edit_id']) && is_numeric($_POST['edit_id']) ? (int)$_POST['edit_id'] : null;
    $username = trim($_POST['username'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $role     = $_POST['role'] ?? 'staff';
    $password = $_POST['password'] ?? '';

    if ($username && $fullName) {
        if ($editId) {
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET username=?, full_name=?, email=?, role=?, password=? WHERE id=?");
                $stmt->bind_param('sssssi', $username, $fullName, $email, $role, $hash, $editId);
            } else {
                $stmt = $db->prepare("UPDATE users SET username=?, full_name=?, email=?, role=? WHERE id=?");
                $stmt->bind_param('ssssi', $username, $fullName, $email, $role, $editId);
            }
            $stmt->execute();
            $stmt->close();
            logActivity($user['id'], 'UPDATE', 'users', $editId, "Updated user $username");
            $msg = 'User updated successfully.';
        } else {
            if (empty($password)) {
                $msg = 'Password is required for new users.';
                $msgType = 'danger';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, password, full_name, role, email) VALUES (?,?,?,?,?)");
                $stmt->bind_param('sssss', $username, $hash, $fullName, $role, $email);
                if ($stmt->execute()) {
                    $newId = $stmt->insert_id;
                    logActivity($user['id'], 'CREATE', 'users', $newId, "Created user $username");
                    $msg = "User '$username' created successfully.";
                } else {
                    $msg = 'Error: Username already exists.';
                    $msgType = 'danger';
                }
                $stmt->close();
            }
        }
    } else {
        $msg = 'Username and full name are required.';
        $msgType = 'danger';
    }
}

// ---- Fetch for edit ----
$editData = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $db->prepare("SELECT id, username, full_name, email, role FROM users WHERE id = ?");
    $stmt->bind_param('i', (int)$_GET['edit']);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ---- Fetch all users with stats (LEFT JOINs) ----
$users = $db->query("
    SELECT
        u.id,
        u.username,
        u.full_name,
        u.email,
        u.role,
        u.created_at,
        COUNT(DISTINCT i.id)  AS incidents_reported,  -- LEFT JOIN: count incidents reported
        COUNT(DISTINCT al.id) AS activity_count        -- LEFT JOIN: count activity log entries
    FROM users u
    LEFT JOIN incidents i      ON i.reported_by = u.id  -- LEFT JOIN: users who haven't reported anything still appear
    LEFT JOIN activity_logs al ON al.user_id    = u.id  -- LEFT JOIN: users with no logs still appear
    GROUP BY u.id
    ORDER BY u.role DESC, u.full_name
");
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?> alert-dismissible fade show" role="alert">
    <i class="bi bi-<?= $msgType === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
    <?= htmlspecialchars($msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Add / Edit Form -->
<div class="card stat-card mb-4">
    <div class="card-header fw-semibold">
        <i class="bi bi-<?= $editData ? 'pencil-square' : 'person-plus' ?> me-2 text-primary"></i>
        <?= $editData ? 'Edit User' : 'Add New User' ?>
    </div>
    <div class="card-body">
        <form method="POST" action="users.php">
            <?php if ($editData): ?>
                <input type="hidden" name="edit_id" value="<?= $editData['id'] ?>">
            <?php endif; ?>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control" required
                           value="<?= htmlspecialchars($editData['username'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control" required
                           value="<?= htmlspecialchars($editData['full_name'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($editData['email'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Role</label>
                    <select name="role" class="form-select">
                        <option value="staff" <?= ($editData && $editData['role'] === 'staff') ? 'selected' : '' ?>>Staff</option>
                        <option value="admin" <?= ($editData && $editData['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        Password <?= $editData ? '<small class="text-muted">(leave blank to keep current)</small>' : '<span class="text-danger">*</span>' ?>
                    </label>
                    <input type="password" name="password" class="form-control"
                           <?= !$editData ? 'required' : '' ?>
                           placeholder="<?= $editData ? 'Leave blank to keep current' : 'Enter password' ?>">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-<?= $editData ? 'save' : 'plus-lg' ?> me-1"></i>
                        <?= $editData ? 'Update User' : 'Create User' ?>
                    </button>
                    <?php if ($editData): ?>
                    <a href="users.php" class="btn btn-outline-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="table-card">
    <div class="card-header fw-semibold">
        <i class="bi bi-people me-2 text-primary"></i>System Users
        <small class="text-muted ms-2">(LEFT JOIN: incidents reported + activity logs)</small>
    </div>
    <div class="table-responsive p-2">
        <table class="table table-hover align-middle" id="usersTable">
            <thead class="table-light">
                <tr>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Incidents Reported</th>
                    <th>Activity Count</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($u = $users->fetch_assoc()): ?>
                <tr>
                    <td><code><?= htmlspecialchars($u['username']) ?></code></td>
                    <td class="fw-semibold"><?= htmlspecialchars($u['full_name']) ?></td>
                    <td><?= htmlspecialchars($u['email'] ?? '—') ?></td>
                    <td>
                        <span class="badge <?= $u['role'] === 'admin' ? 'bg-danger' : 'bg-primary' ?> rounded-pill">
                            <?= ucfirst($u['role']) ?>
                        </span>
                    </td>
                    <td><?= $u['incidents_reported'] ?></td>
                    <td><?= $u['activity_count'] ?></td>
                    <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="users.php?edit=<?= $u['id'] ?>"
                               class="btn btn-sm btn-outline-primary" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if ($u['id'] !== $user['id']): ?>
                            <button class="btn btn-sm btn-outline-danger"
                                    onclick="confirmDelete(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')"
                                    title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                            <?php else: ?>
                            <span class="btn btn-sm btn-outline-secondary disabled" title="Cannot delete yourself">
                                <i class="bi bi-lock"></i>
                            </span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Delete user <strong id="deleteCode"></strong>? This cannot be undone.
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a id="deleteConfirmBtn" href="#" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<?php
$extraScripts = '
<script>
$(document).ready(function() {
    $("#usersTable").DataTable({
        pageLength: 10,
        order: [[3, "desc"]],
        columnDefs: [{ orderable: false, targets: 7 }]
    });
});
function confirmDelete(id, username) {
    document.getElementById("deleteCode").textContent = username;
    document.getElementById("deleteConfirmBtn").href = "users.php?delete=" + id;
    new bootstrap.Modal(document.getElementById("deleteModal")).show();
}
</script>';
require_once 'layout_footer.php';
?>
