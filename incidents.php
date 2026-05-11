<?php

// Incidents — Full CRUD
// SQL joins:
//   INNER JOIN incidents ↔ students   — student name/grade
//   INNER JOIN incidents ↔ categories — category & severity
//   INNER JOIN incidents ↔ users      — reporter name
//   RIGHT JOIN demo query shown in comments below

$pageTitle = 'Incidents';
require_once 'db.php';
require_once 'layout.php';

$db   = getDB();
$user = currentUser();
$msg  = ''; $msgType = 'success';

// ── DELETE ─────────────────
if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    $id   = (int)$_GET['delete'];
    $stmt = $db->prepare("SELECT incident_code FROM incidents WHERE id=?");
    $stmt->bind_param('i', $id); $stmt->execute();
    $row  = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if ($row) {
        $del = $db->prepare("DELETE FROM incidents WHERE id=?");
        $del->bind_param('i', $id); $del->execute(); $del->close();
        logActivity($user['id'],'DELETE','incidents',$id,"Deleted {$row['incident_code']}");
        $msg = "Incident {$row['incident_code']} deleted.";
    }
}

// ── CREATE / UPDATE ────────────
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $editId      = isset($_POST['edit_id']) && ctype_digit($_POST['edit_id']) ? (int)$_POST['edit_id'] : null;
    $studentId   = (int)($_POST['student_id']  ?? 0);
    $categoryId  = (int)($_POST['category_id'] ?? 0);
    $title       = trim($_POST['title']        ?? '');
    $desc        = trim($_POST['description']  ?? '');
    $location    = trim($_POST['location']     ?? '');
    $date        = $_POST['incident_date']     ?? '';
    $time        = $_POST['incident_time']     ?: null;
    $status      = $_POST['status']            ?? 'open';
    $action      = trim($_POST['action_taken'] ?? '');

    if ($studentId && $categoryId && $title && $desc && $date) {
        if ($editId) {
            $stmt = $db->prepare("UPDATE incidents SET student_id=?,category_id=?,title=?,description=?,location=?,incident_date=?,incident_time=?,status=?,action_taken=? WHERE id=?");
            $stmt->bind_param('iisssssssi',$studentId,$categoryId,$title,$desc,$location,$date,$time,$status,$action,$editId);
            $stmt->execute(); $stmt->close();
            logActivity($user['id'],'UPDATE','incidents',$editId,"Updated incident #$editId");
            $msg = 'Incident updated.';
        } else {
            $code = 'INC-'.date('Y').'-'.str_pad(rand(1,9999),4,'0',STR_PAD_LEFT);
            $stmt = $db->prepare("INSERT INTO incidents (incident_code,student_id,category_id,reported_by,title,description,location,incident_date,incident_time,status,action_taken) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('siiisssssss',$code,$studentId,$categoryId,$user['id'],$title,$desc,$location,$date,$time,$status,$action);
            $stmt->execute();
            logActivity($user['id'],'CREATE','incidents',$stmt->insert_id,"Created $code");
            $stmt->close();
            $msg = "Incident $code created.";
        }
    } else { $msg='Fill in all required fields.'; $msgType='danger'; }
}

// ── Edit prefill ──────────
$edit = null;
if (isset($_GET['edit']) && ctype_digit($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $s = $db->prepare("SELECT * FROM incidents WHERE id=?");
    $s->bind_param('i', $editId); $s->execute();
    $edit = $s->get_result()->fetch_assoc(); $s->close();
}

// ── Fetch all incidents (INNER JOINs) ───────
$rows = $db->query("
    SELECT i.id, i.incident_code, i.title, i.incident_date, i.status,
           i.location, i.action_taken,
           CONCAT(s.first_name,' ',s.last_name) student_name,
           s.student_code, s.grade,
           ic.name category, ic.severity_level,
           u.full_name reported_by
    FROM incidents i
    INNER JOIN students s             ON i.student_id  = s.id    -- INNER JOIN: student info
    INNER JOIN incident_categories ic ON i.category_id = ic.id   -- INNER JOIN: category info
    INNER JOIN users u                ON i.reported_by = u.id    -- INNER JOIN: reporter info
    ORDER BY i.incident_date DESC, i.created_at DESC
");

// ── Dropdowns ────────────
$studentOpts  = $db->query("SELECT id,student_code,first_name,last_name,grade FROM students ORDER BY last_name");
$categoryOpts = $db->query("SELECT id,name,severity_level FROM incident_categories ORDER BY name");
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
        <i class="bi bi-<?= $edit?'pencil-square':'plus-circle' ?> me-2 text-primary"></i>
        <?= $edit ? 'Edit Incident' : 'Report New Incident' ?>
    </div>
    <div class="p-3">
        <form method="POST">
            <?php if ($edit): ?><input type="hidden" name="edit_id" value="<?= $edit['id'] ?>"><?php endif; ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Student <span class="text-danger">*</span></label>
                    <select name="student_id" class="form-select" required>
                        <option value="">— Select —</option>
                        <?php while ($s = $studentOpts->fetch_assoc()): ?>
                        <option value="<?= $s['id'] ?>" <?= ($edit && $edit['student_id']==$s['id'])?'selected':'' ?>>
                            <?= htmlspecialchars("{$s['student_code']} — {$s['first_name']} {$s['last_name']} (Gr.{$s['grade']})") ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" required>
                        <option value="">— Select —</option>
                        <?php while ($c = $categoryOpts->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>" <?= ($edit && $edit['category_id']==$c['id'])?'selected':'' ?>>
                            <?= htmlspecialchars("{$c['name']} ({$c['severity_level']})") ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['open','under_review','resolved','closed'] as $st): ?>
                        <option value="<?= $st ?>" <?= ($edit && $edit['status']===$st)?'selected':'' ?>>
                            <?= ucfirst(str_replace('_',' ',$st)) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required
                           value="<?= htmlspecialchars($edit['title'] ?? '') ?>" placeholder="Brief incident title">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Location</label>
                    <input type="text" name="location" class="form-control"
                           value="<?= htmlspecialchars($edit['location'] ?? '') ?>" placeholder="e.g. Cafeteria">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                    <input type="date" name="incident_date" class="form-control" required
                           value="<?= htmlspecialchars($edit['incident_date'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Time</label>
                    <input type="time" name="incident_time" class="form-control"
                           value="<?= htmlspecialchars($edit['incident_time'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Action Taken</label>
                    <input type="text" name="action_taken" class="form-control"
                           value="<?= htmlspecialchars($edit['action_taken'] ?? '') ?>"
                           placeholder="Steps taken to address the incident">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                    <textarea name="description" class="form-control" rows="3" required
                              placeholder="Detailed description..."><?= htmlspecialchars($edit['description'] ?? '') ?></textarea>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-<?= $edit?'save':'plus-lg' ?> me-1"></i>
                        <?= $edit ? 'Update' : 'Submit Incident' ?>
                    </button>
                    <?php if ($edit): ?>
                    <a href="incidents.php" class="btn btn-outline-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="kcard">
    <div class="p-3 border-bottom fw-semibold">
        <i class="bi bi-table me-2 text-primary"></i>All Incidents
        <small class="text-muted ms-1">(INNER JOIN: students · categories · users)</small>
    </div>
    <div class="table-responsive p-2">
        <table class="table table-hover align-middle" id="tblIncidents">
            <thead class="table-light">
                <tr>
                    <th>Code</th><th>Student</th><th>Grade</th><th>Category</th>
                    <th>Severity</th><th>Location</th><th>Date</th><th>Status</th>
                    <th>Reporter</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $rows->fetch_assoc()): ?>
                <tr>
                    <td><code><?= htmlspecialchars($row['incident_code']) ?></code></td>
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($row['student_name']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($row['student_code']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($row['grade']) ?></td>
                    <td><?= htmlspecialchars($row['category']) ?></td>
                    <td><span class="badge b-<?= $row['severity_level'] ?> rounded-pill px-2"><?= ucfirst($row['severity_level']) ?></span></td>
                    <td><?= htmlspecialchars($row['location'] ?? '—') ?></td>
                    <td><?= date('M d, Y', strtotime($row['incident_date'])) ?></td>
                    <td><span class="badge b-<?= $row['status'] ?> rounded-pill px-2"><?= ucfirst(str_replace('_',' ',$row['status'])) ?></span></td>
                    <td><?= htmlspecialchars($row['reported_by']) ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                            <button class="btn btn-sm btn-outline-danger" title="Delete"
                                    onclick="confirmDel(<?= $row['id'] ?>,'<?= htmlspecialchars($row['incident_code']) ?>')">
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
            <div class="modal-body">Delete incident <strong id="delCode"></strong>? This cannot be undone.</div>
            <div class="modal-footer border-0">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a id="delBtn" href="#" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Delete</a>
            </div>
        </div>
    </div>
</div>

<?php
$extraJS = '
<script>
$(()=>{ $("#tblIncidents").DataTable({pageLength:10,order:[[6,"desc"]],columnDefs:[{orderable:false,targets:9}]}); });
function confirmDel(id,code){
    document.getElementById("delCode").textContent=code;
    document.getElementById("delBtn").href="incidents.php?delete="+id;
    new bootstrap.Modal(document.getElementById("delModal")).show();
}
</script>';
require_once 'layout_end.php';
?>
