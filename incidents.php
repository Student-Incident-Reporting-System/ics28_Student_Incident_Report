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


// ── DELETE ────
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

// ── CREATE / UPDATE ────
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

// ── Edit prefill ───────
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

// ── Dropdowns ──────
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