<?php
// Students — Full CRUD
// SQL joins:
//   LEFT JOIN  students ↔ incidents — count per student
//              (LEFT JOIN keeps students with 0 incidents)
$pageTitle = 'Students';
require_once 'db.php';
require_once 'layout.php';

$db   = getDB();
$user = currentUser();
$msg  = ''; $msgType = 'success';

// ── DELETE ─────
if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    $id   = (int)$_GET['delete'];
    $stmt = $db->prepare("SELECT student_code,first_name,last_name FROM students WHERE id=?");
    $stmt->bind_param('i',$id); $stmt->execute();
    $row  = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if ($row) {
        $del = $db->prepare("DELETE FROM students WHERE id=?");
        $del->bind_param('i',$id); $del->execute(); $del->close();
        logActivity($user['id'],'DELETE','students',$id,"Deleted {$row['student_code']}");
        $msg = "Student {$row['student_code']} deleted.";
    }
}

// ── CREATE / UPDATE ────
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $editId  = isset($_POST['edit_id']) && ctype_digit($_POST['edit_id']) ? (int)$_POST['edit_id'] : null;
    $code    = trim($_POST['student_code'] ?? '');
    $first   = trim($_POST['first_name']   ?? '');
    $last    = trim($_POST['last_name']    ?? '');
    $grade   = trim($_POST['grade']        ?? '');
    $section = trim($_POST['section']      ?? '');
    $dob     = $_POST['date_of_birth']     ?: null;
    $gname   = trim($_POST['guardian_name']    ?? '');
    $gphone  = trim($_POST['guardian_contact'] ?? '');
    $addr    = trim($_POST['address']          ?? '');

    if ($code && $first && $last && $grade) {
        if ($editId) {
            $stmt = $db->prepare("UPDATE students SET student_code=?,first_name=?,last_name=?,grade=?,section=?,date_of_birth=?,guardian_name=?,guardian_contact=?,address=? WHERE id=?");
            $stmt->bind_param('sssssssssi',$code,$first,$last,$grade,$section,$dob,$gname,$gphone,$addr,$editId);
            $stmt->execute(); $stmt->close();
            logActivity($user['id'],'UPDATE','students',$editId,"Updated $code");
            $msg = 'Student updated.';
        } else {
            $stmt = $db->prepare("INSERT INTO students (student_code,first_name,last_name,grade,section,date_of_birth,guardian_name,guardian_contact,address) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('sssssssss',$code,$first,$last,$grade,$section,$dob,$gname,$gphone,$addr);
            if ($stmt->execute()) {
                logActivity($user['id'],'CREATE','students',$stmt->insert_id,"Created $code");
                $msg = "Student $code added.";
            } else { $msg='Error: Student code already exists.'; $msgType='danger'; }
            $stmt->close();
        }
    } else { $msg='Fill in all required fields.'; $msgType='danger'; }
}

// ── Edit prefill ──────
$edit = null;
if (isset($_GET['edit']) && ctype_digit($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $s = $db->prepare("SELECT * FROM students WHERE id=?");
    $s->bind_param('i', $editId); $s->execute();
    $edit = $s->get_result()->fetch_assoc(); $s->close();
}

// ── Fetch all students with incident counts (LEFT JOIN) ───────
$rows = $db->query("
    SELECT s.id, s.student_code,
           CONCAT(s.first_name,' ',s.last_name) full_name,
           s.grade, s.section, s.guardian_name, s.guardian_contact,
           COUNT(i.id) total_incidents,
           SUM(i.status='open') open_incidents
    FROM students s
    LEFT JOIN incidents i ON i.student_id = s.id   -- LEFT JOIN: include students with 0 incidents
    GROUP BY s.id
    ORDER BY s.last_name, s.first_name
");
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?> alert-dismissible fade show d-flex align-items-center gap-2">
    <i class="bi bi-<?= $msgType==='success'?'check-circle':'exclamation-circle' ?>"></i>
    <?= htmlspecialchars($msg) ?>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Form -->
<div class="kcard mb-4">
    <div class="p-3 border-bottom fw-semibold">
        <i class="bi bi-<?= $edit?'pencil-square':'person-plus' ?> me-2 text-primary"></i>
        <?= $edit ? 'Edit Student' : 'Add New Student' ?>
    </div>
    <div class="p-3">
        <form method="POST">
            <?php if ($edit): ?><input type="hidden" name="edit_id" value="<?= $edit['id'] ?>"><?php endif; ?>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Student Code <span class="text-danger">*</span></label>
                    <input type="text" name="student_code" class="form-control" required
                           value="<?= htmlspecialchars($edit['student_code'] ?? '') ?>" placeholder="STU-009">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control" required
                           value="<?= htmlspecialchars($edit['first_name'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control" required
                           value="<?= htmlspecialchars($edit['last_name'] ?? '') ?>">
                </div>
                <div class="col-md-1">
                    <label class="form-label fw-semibold">Grade <span class="text-danger">*</span></label>
                    <input type="text" name="grade" class="form-control" required
                           value="<?= htmlspecialchars($edit['grade'] ?? '') ?>" placeholder="10">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Section</label>
                    <input type="text" name="section" class="form-control"
                           value="<?= htmlspecialchars($edit['section'] ?? '') ?>" placeholder="A">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control"
                           value="<?= htmlspecialchars($edit['date_of_birth'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Guardian Name</label>
                    <input type="text" name="guardian_name" class="form-control"
                           value="<?= htmlspecialchars($edit['guardian_name'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Guardian Contact</label>
                    <input type="text" name="guardian_contact" class="form-control"
                           value="<?= htmlspecialchars($edit['guardian_contact'] ?? '') ?>">
                </div>
                <div class="col-md-2"></div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Address</label>
                    <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($edit['address'] ?? '') ?></textarea>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-<?= $edit?'save':'plus-lg' ?> me-1"></i>
                        <?= $edit ? 'Update Student' : 'Add Student' ?>
                    </button>
                    <?php if ($edit): ?>
                    <a href="students.php" class="btn btn-outline-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="kcard">
    <div class="p-3 border-bottom fw-semibold">
        <i class="bi bi-people me-2 text-primary"></i>All Students
        <small class="text-muted ms-1">(LEFT JOIN with incidents)</small>
    </div>
    <div class="table-responsive p-2">
        <table class="table table-hover align-middle" id="tblStudents">
            <thead class="table-light">
                <tr>
                    <th>Code</th><th>Name</th><th>Grade</th><th>Section</th>
                    <th>Guardian</th><th>Contact</th><th>Total</th><th>Open</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $rows->fetch_assoc()): ?>
                <tr>
                    <td><code><?= htmlspecialchars($row['student_code']) ?></code></td>
                    <td class="fw-semibold"><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['grade']) ?></td>
                    <td><?= htmlspecialchars($row['section'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($row['guardian_name'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($row['guardian_contact'] ?? '—') ?></td>
                    <td><span class="badge <?= $row['total_incidents']>0?'bg-warning text-dark':'bg-light text-muted border' ?>"><?= $row['total_incidents'] ?></span></td>
                    <td>
                        <?php if ($row['open_incidents'] > 0): ?>
                        <span class="badge bg-danger"><?= $row['open_incidents'] ?></span>
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                            <button class="btn btn-sm btn-outline-danger" title="Delete"
                                    onclick="confirmDel(<?= $row['id'] ?>,'<?= htmlspecialchars($row['student_code']) ?>')">
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

<!-- Delete modal -->
<div class="modal fade" id="delModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Confirm Delete</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Delete student <strong id="delCode"></strong>?
                <div class="alert alert-warning mt-2 mb-0 small">
                    <i class="bi bi-exclamation-triangle me-1"></i>All linked incidents will also be deleted.
                </div>
            </div>
            <div class="modal-footer border-0">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a id="delBtn" href="#" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<?php
$extraJS = '
<script>
$(()=>{ $("#tblStudents").DataTable({pageLength:10,order:[[1,"asc"]],columnDefs:[{orderable:false,targets:8}]}); });
function confirmDel(id,code){
    document.getElementById("delCode").textContent=code;
    document.getElementById("delBtn").href="students.php?delete="+id;
    new bootstrap.Modal(document.getElementById("delModal")).show();
}
</script>';
require_once 'layout_end.php';
?>
