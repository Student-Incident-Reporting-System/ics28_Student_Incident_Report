<?php
// ============================================================
// Students — Full CRUD
// SQL Joins:
//   LEFT JOIN students ↔ incidents: count incidents per student
//     (LEFT JOIN so students with 0 incidents are still shown)
// ============================================================
$pageTitle = 'Students';
require_once 'db.php';
require_once 'layout_header.php';

$db   = getDB();
$user = currentUser();
$msg  = '';
$msgType = 'success';

// ---- DELETE ----
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("SELECT student_id, first_name, last_name FROM students WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        $del = $db->prepare("DELETE FROM students WHERE id = ?");
        $del->bind_param('i', $id);
        $del->execute();
        $del->close();
        logActivity($user['id'], 'DELETE', 'students', $id, "Deleted student {$row['student_id']}");
        $msg = "Student {$row['student_id']} ({$row['first_name']} {$row['last_name']}) deleted.";
    }
}

// ---- CREATE / UPDATE ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $editId    = isset($_POST['edit_id']) && is_numeric($_POST['edit_id']) ? (int)$_POST['edit_id'] : null;
    $studentId = trim($_POST['student_id_code'] ?? '');
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name'] ?? '');
    $grade     = trim($_POST['grade'] ?? '');
    $section   = trim($_POST['section'] ?? '');
    $dob       = $_POST['date_of_birth'] ?: null;
    $guardian  = trim($_POST['guardian_name'] ?? '');
    $contact   = trim($_POST['guardian_contact'] ?? '');
    $address   = trim($_POST['address'] ?? '');

    if ($studentId && $firstName && $lastName && $grade) {
        if ($editId) {
            $stmt = $db->prepare("
                UPDATE students SET
                    student_id=?, first_name=?, last_name=?, grade=?, section=?,
                    date_of_birth=?, guardian_name=?, guardian_contact=?, address=?
                WHERE id=?
            ");
            $stmt->bind_param('sssssssssi',
                $studentId, $firstName, $lastName, $grade, $section,
                $dob, $guardian, $contact, $address, $editId
            );
            $stmt->execute();
            $stmt->close();
            logActivity($user['id'], 'UPDATE', 'students', $editId, "Updated student $studentId");
            $msg = 'Student record updated successfully.';
        } else {
            $stmt = $db->prepare("
                INSERT INTO students
                    (student_id, first_name, last_name, grade, section,
                     date_of_birth, guardian_name, guardian_contact, address)
                VALUES (?,?,?,?,?,?,?,?,?)
            ");
            $stmt->bind_param('sssssssss',
                $studentId, $firstName, $lastName, $grade, $section,
                $dob, $guardian, $contact, $address
            );
            if ($stmt->execute()) {
                $newId = $stmt->insert_id;
                logActivity($user['id'], 'CREATE', 'students', $newId, "Created student $studentId");
                $msg = "Student $studentId added successfully.";
            } else {
                $msg = 'Error: Student ID already exists.';
                $msgType = 'danger';
            }
            $stmt->close();
        }
    } else {
        $msg = 'Please fill in all required fields.';
        $msgType = 'danger';
    }
}

// ---- Fetch for edit ----
$editData = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param('i', (int)$_GET['edit']);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ---- Fetch all students with incident count (LEFT JOIN) ----
$students = $db->query("
    SELECT
        s.id,
        s.student_id,
        s.first_name,
        s.last_name,
        s.grade,
        s.section,
        s.guardian_name,
        s.guardian_contact,
        s.date_of_birth,
        COUNT(i.id) AS incident_count,
        -- Count only open incidents using conditional aggregation
        SUM(CASE WHEN i.status = 'open' THEN 1 ELSE 0 END) AS open_incidents
    FROM students s
    LEFT JOIN incidents i ON i.student_id = s.id  -- LEFT JOIN: include students with 0 incidents
    GROUP BY s.id
    ORDER BY s.last_name, s.first_name
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
        <?= $editData ? 'Edit Student' : 'Add New Student' ?>
    </div>
    <div class="card-body">
        <form method="POST" action="students.php">
            <?php if ($editData): ?>
                <input type="hidden" name="edit_id" value="<?= $editData['id'] ?>">
            <?php endif; ?>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Student ID <span class="text-danger">*</span></label>
                    <input type="text" name="student_id_code" class="form-control" required
                           value="<?= htmlspecialchars($editData['student_id'] ?? '') ?>"
                           placeholder="e.g. STU-009">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control" required
                           value="<?= htmlspecialchars($editData['first_name'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control" required
                           value="<?= htmlspecialchars($editData['last_name'] ?? '') ?>">
                </div>
                <div class="col-md-1">
                    <label class="form-label fw-semibold">Grade <span class="text-danger">*</span></label>
                    <input type="text" name="grade" class="form-control" required
                           value="<?= htmlspecialchars($editData['grade'] ?? '') ?>" placeholder="10">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Section</label>
                    <input type="text" name="section" class="form-control"
                           value="<?= htmlspecialchars($editData['section'] ?? '') ?>" placeholder="A">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control"
                           value="<?= htmlspecialchars($editData['date_of_birth'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Guardian Name</label>
                    <input type="text" name="guardian_name" class="form-control"
                           value="<?= htmlspecialchars($editData['guardian_name'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Guardian Contact</label>
                    <input type="text" name="guardian_contact" class="form-control"
                           value="<?= htmlspecialchars($editData['guardian_contact'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <!-- spacer -->
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Address</label>
                    <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($editData['address'] ?? '') ?></textarea>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-<?= $editData ? 'save' : 'plus-lg' ?> me-1"></i>
                        <?= $editData ? 'Update Student' : 'Add Student' ?>
                    </button>
                    <?php if ($editData): ?>
                    <a href="students.php" class="btn btn-outline-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Students Table -->
<div class="table-card">
    <div class="card-header fw-semibold">
        <i class="bi bi-people me-2 text-primary"></i>All Students
        <small class="text-muted ms-2">(LEFT JOIN with incidents — includes students with 0 incidents)</small>
    </div>
    <div class="table-responsive p-2">
        <table class="table table-hover align-middle" id="studentsTable">
            <thead class="table-light">
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Grade</th>
                    <th>Section</th>
                    <th>Guardian</th>
                    <th>Contact</th>
                    <th>Total Incidents</th>
                    <th>Open</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($s = $students->fetch_assoc()): ?>
                <tr>
                    <td><code><?= htmlspecialchars($s['student_id']) ?></code></td>
                    <td class="fw-semibold"><?= htmlspecialchars("{$s['first_name']} {$s['last_name']}") ?></td>
                    <td><?= htmlspecialchars($s['grade']) ?></td>
                    <td><?= htmlspecialchars($s['section'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($s['guardian_name'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($s['guardian_contact'] ?? '—') ?></td>
                    <td>
                        <span class="badge <?= $s['incident_count'] > 0 ? 'bg-warning text-dark' : 'bg-light text-muted border' ?>">
                            <?= $s['incident_count'] ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($s['open_incidents'] > 0): ?>
                        <span class="badge bg-danger"><?= $s['open_incidents'] ?></span>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="students.php?edit=<?= $s['id'] ?>"
                               class="btn btn-sm btn-outline-primary" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="incidents.php?student=<?= $s['id'] ?>"
                               class="btn btn-sm btn-outline-warning" title="View Incidents">
                                <i class="bi bi-exclamation-triangle"></i>
                            </a>
                            <button class="btn btn-sm btn-outline-danger"
                                    onclick="confirmDelete(<?= $s['id'] ?>, '<?= htmlspecialchars($s['student_id']) ?>')"
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

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Delete student <strong id="deleteCode"></strong>?
                <div class="alert alert-warning mt-2 mb-0 small">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    All associated incidents will also be deleted (CASCADE).
                </div>
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
    $("#studentsTable").DataTable({
        pageLength: 10,
        order: [[1, "asc"]],
        columnDefs: [{ orderable: false, targets: 8 }]
    });
});
function confirmDelete(id, code) {
    document.getElementById("deleteCode").textContent = code;
    document.getElementById("deleteConfirmBtn").href = "students.php?delete=" + id;
    new bootstrap.Modal(document.getElementById("deleteModal")).show();
}
</script>';
require_once 'layout_footer.php';
?>
