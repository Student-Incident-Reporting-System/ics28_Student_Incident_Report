<?php
// ============================================================
// Incidents — Full CRUD with SQL Join display
// SQL Joins:
//   INNER JOIN incidents ↔ students: get student name/grade
//   INNER JOIN incidents ↔ incident_categories: get category & severity
//   INNER JOIN incidents ↔ users: get reporter name
// ============================================================
$pageTitle = 'Incidents';
require_once 'db.php';
require_once 'layout_header.php';

$db  = getDB();
$user = currentUser();
$msg = '';
$msgType = 'success';

// ---- DELETE ----
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("SELECT incident_code FROM incidents WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        $del = $db->prepare("DELETE FROM incidents WHERE id = ?");
        $del->bind_param('i', $id);
        $del->execute();
        $del->close();
        logActivity($user['id'], 'DELETE', 'incidents', $id, "Deleted incident {$row['incident_code']}");
        $msg = "Incident {$row['incident_code']} deleted successfully.";
    }
}

// ---- CREATE / UPDATE ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $editId       = isset($_POST['edit_id']) && is_numeric($_POST['edit_id']) ? (int)$_POST['edit_id'] : null;
    $studentId    = (int)($_POST['student_id'] ?? 0);
    $categoryId   = (int)($_POST['category_id'] ?? 0);
    $title        = trim($_POST['title'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $location     = trim($_POST['location'] ?? '');
    $incidentDate = $_POST['incident_date'] ?? '';
    $incidentTime = $_POST['incident_time'] ?? null;
    $status       = $_POST['status'] ?? 'open';
    $actionTaken  = trim($_POST['action_taken'] ?? '');

    if ($studentId && $categoryId && $title && $description && $incidentDate) {
        if ($editId) {
            // UPDATE
            $stmt = $db->prepare("
                UPDATE incidents SET
                    student_id=?, category_id=?, title=?, description=?,
                    location=?, incident_date=?, incident_time=?,
                    status=?, action_taken=?
                WHERE id=?
            ");
            $stmt->bind_param('iisssssssi',
                $studentId, $categoryId, $title, $description,
                $location, $incidentDate, $incidentTime,
                $status, $actionTaken, $editId
            );
            $stmt->execute();
            $stmt->close();
            logActivity($user['id'], 'UPDATE', 'incidents', $editId, "Updated incident ID $editId");
            $msg = 'Incident updated successfully.';
        } else {
            // CREATE — generate unique code
            $code = 'INC-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $stmt = $db->prepare("
                INSERT INTO incidents
                    (incident_code, student_id, category_id, reported_by, title, description,
                     location, incident_date, incident_time, status, action_taken)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->bind_param('siiisssssss',
                $code, $studentId, $categoryId, $user['id'],
                $title, $description, $location,
                $incidentDate, $incidentTime, $status, $actionTaken
            );
            $stmt->execute();
            $newId = $stmt->insert_id;
            $stmt->close();
            logActivity($user['id'], 'CREATE', 'incidents', $newId, "Created incident $code");
            $msg = "Incident $code created successfully.";
        }
    } else {
        $msg = 'Please fill in all required fields.';
        $msgType = 'danger';
    }
}

// ---- Fetch for edit form ----
$editData = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM incidents WHERE id = ?");
    $stmt->bind_param('i', (int)$_GET['edit']);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ---- Fetch all incidents with INNER JOINs ----
$incidents = $db->query("
    SELECT
        i.id,
        i.incident_code,
        i.title,
        i.incident_date,
        i.status,
        i.location,
        i.action_taken,
        CONCAT(s.first_name, ' ', s.last_name) AS student_name,
        s.student_id AS student_code,
        s.grade,
        ic.name AS category,
        ic.severity_level,
        u.full_name AS reported_by
    FROM incidents i
    INNER JOIN students s             ON i.student_id  = s.id    -- INNER JOIN: student details
    INNER JOIN incident_categories ic ON i.category_id = ic.id   -- INNER JOIN: category & severity
    INNER JOIN users u                ON i.reported_by = u.id    -- INNER JOIN: reporter name
    ORDER BY i.incident_date DESC, i.created_at DESC
");

// ---- Dropdowns ----
$students   = $db->query("SELECT id, student_id, first_name, last_name, grade FROM students ORDER BY last_name");
$categories = $db->query("SELECT id, name, severity_level FROM incident_categories ORDER BY name");
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
        <i class="bi bi-<?= $editData ? 'pencil-square' : 'plus-circle' ?> me-2 text-primary"></i>
        <?= $editData ? 'Edit Incident' : 'Report New Incident' ?>
    </div>
    <div class="card-body">
        <form method="POST" action="incidents.php">
            <?php if ($editData): ?>
                <input type="hidden" name="edit_id" value="<?= $editData['id'] ?>">
            <?php endif; ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Student <span class="text-danger">*</span></label>
                    <select name="student_id" class="form-select" required>
                        <option value="">— Select Student —</option>
                        <?php while ($s = $students->fetch_assoc()): ?>
                        <option value="<?= $s['id'] ?>"
                            <?= ($editData && $editData['student_id'] == $s['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars("{$s['student_id']} — {$s['first_name']} {$s['last_name']} (Gr.{$s['grade']})") ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" required>
                        <option value="">— Select Category —</option>
                        <?php while ($c = $categories->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>"
                            <?= ($editData && $editData['category_id'] == $c['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars("{$c['name']} ({$c['severity_level']})") ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['open','under_review','resolved','closed'] as $st): ?>
                        <option value="<?= $st ?>" <?= ($editData && $editData['status'] === $st) ? 'selected' : '' ?>>
                            <?= ucfirst(str_replace('_',' ',$st)) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required
                           value="<?= htmlspecialchars($editData['title'] ?? '') ?>"
                           placeholder="Brief incident title">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Location</label>
                    <input type="text" name="location" class="form-control"
                           value="<?= htmlspecialchars($editData['location'] ?? '') ?>"
                           placeholder="e.g. Cafeteria">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                    <input type="date" name="incident_date" class="form-control" required
                           value="<?= htmlspecialchars($editData['incident_date'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Time</label>
                    <input type="time" name="incident_time" class="form-control"
                           value="<?= htmlspecialchars($editData['incident_time'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Action Taken</label>
                    <input type="text" name="action_taken" class="form-control"
                           value="<?= htmlspecialchars($editData['action_taken'] ?? '') ?>"
                           placeholder="Steps taken to address the incident">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                    <textarea name="description" class="form-control" rows="3" required
                              placeholder="Detailed description of the incident..."><?= htmlspecialchars($editData['description'] ?? '') ?></textarea>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-<?= $editData ? 'save' : 'plus-lg' ?> me-1"></i>
                        <?= $editData ? 'Update Incident' : 'Submit Incident' ?>
                    </button>
                    <?php if ($editData): ?>
                    <a href="incidents.php" class="btn btn-outline-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Incidents Table -->
<div class="table-card">
    <div class="card-header fw-semibold">
        <i class="bi bi-table me-2 text-primary"></i>All Incidents
        <small class="text-muted ms-2">(INNER JOIN: students, categories, users)</small>
    </div>
    <div class="table-responsive p-2">
        <table class="table table-hover align-middle" id="incidentsTable">
            <thead class="table-light">
                <tr>
                    <th>Code</th>
                    <th>Student</th>
                    <th>Grade</th>
                    <th>Category</th>
                    <th>Severity</th>
                    <th>Location</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Reported By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($inc = $incidents->fetch_assoc()): ?>
                <tr>
                    <td><code><?= htmlspecialchars($inc['incident_code']) ?></code></td>
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($inc['student_name']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($inc['student_code']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($inc['grade']) ?></td>
                    <td><?= htmlspecialchars($inc['category']) ?></td>
                    <td>
                        <span class="badge badge-<?= $inc['severity_level'] ?> rounded-pill px-2">
                            <?= ucfirst($inc['severity_level']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($inc['location'] ?? '—') ?></td>
                    <td><?= date('M d, Y', strtotime($inc['incident_date'])) ?></td>
                    <td>
                        <span class="badge badge-<?= str_replace('_','-',$inc['status']) ?> rounded-pill px-2">
                            <?= ucfirst(str_replace('_',' ',$inc['status'])) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($inc['reported_by']) ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="incidents.php?edit=<?= $inc['id'] ?>"
                               class="btn btn-sm btn-outline-primary" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button class="btn btn-sm btn-outline-danger"
                                    onclick="confirmDelete(<?= $inc['id'] ?>, '<?= htmlspecialchars($inc['incident_code']) ?>')"
                                    title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete incident <strong id="deleteCode"></strong>?
                This action cannot be undone.
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a id="deleteConfirmBtn" href="#" class="btn btn-danger">
                    <i class="bi bi-trash me-1"></i>Delete
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$extraScripts = '
<script>
$(document).ready(function() {
    $("#incidentsTable").DataTable({
        pageLength: 10,
        order: [[6, "desc"]],
        columnDefs: [{ orderable: false, targets: 9 }]
    });
});
function confirmDelete(id, code) {
    document.getElementById("deleteCode").textContent = code;
    document.getElementById("deleteConfirmBtn").href = "incidents.php?delete=" + id;
    new bootstrap.Modal(document.getElementById("deleteModal")).show();
}
</script>';
require_once 'layout_footer.php';
?>
