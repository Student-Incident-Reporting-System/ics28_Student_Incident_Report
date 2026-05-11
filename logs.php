<?php
// Activity Logs — read-only audit trail
// SQL joins:
//   INNER JOIN logs ↔ users     — user name for every log entry
//   LEFT JOIN  logs ↔ incidents — show incident code when applicable

$pageTitle = 'Activity Logs';
require_once 'db.php';
require_once 'layout.php';

$rows = $db->query("
    SELECT al.id, al.action, al.target_table, al.target_id,
           al.details, al.ip_address, al.created_at,
           u.full_name, u.username, u.role,
           i.incident_code
    FROM activity_logs al
    INNER JOIN users u    ON al.user_id    = u.id                           -- INNER JOIN: user info
    LEFT JOIN  incidents i ON al.target_table = 'incidents'
                          AND al.target_id = i.id                           -- LEFT JOIN: optional incident link
    ORDER BY al.created_at DESC
    LIMIT 300
");

$colors = ['LOGIN'=>'success','LOGOUT'=>'secondary','CREATE'=>'primary',
           'UPDATE'=>'warning','DELETE'=>'danger','REGISTER'=>'info'];
?>

<div class="kcard">
    <div class="p-3 border-bottom fw-semibold">
        <i class="bi bi-journal-text me-2 text-primary"></i>System Activity Logs
        <small class="text-muted ms-1">(INNER JOIN: users · LEFT JOIN: incidents)</small>
    </div>
    <div class="table-responsive p-2">
        <table class="table table-hover align-middle table-sm" id="tblLogs">
            <thead class="table-light">
                <tr>
                    <th>#</th><th>User</th><th>Role</th><th>Action</th>
                    <th>Target</th><th>Details</th><th>IP</th><th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $rows->fetch_assoc()): ?>
                <tr>
                    <td class="text-muted small"><?= $row['id'] ?></td>
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($row['full_name']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($row['username']) ?></small>
                    </td>
                    <td><span class="badge rounded-pill <?= $row['role']==='admin'?'bg-danger':'bg-primary' ?>"><?= ucfirst($row['role']) ?></span></td>
                    <td><span class="badge bg-<?= $colors[$row['action']] ?? 'secondary' ?>"><?= htmlspecialchars($row['action']) ?></span></td>
                    <td>
                        <?php if ($row['target_table']): ?>
                            <code><?= htmlspecialchars($row['target_table']) ?></code>
                            <?php if ($row['incident_code']): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($row['incident_code']) ?></small>
                            <?php elseif ($row['target_id']): ?>
                                <br><small class="text-muted">ID: <?= $row['target_id'] ?></small>
                            <?php endif; ?>
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['details'] ?? '—') ?></td>
                    <td><code><?= htmlspecialchars($row['ip_address'] ?? '—') ?></code></td>
                    <td class="text-nowrap"><?= date('M d, Y H:i:s', strtotime($row['created_at'])) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$extraJS = '
<script>
$(()=>{ $("#tblLogs").DataTable({pageLength:25,order:[[7,"desc"]],columnDefs:[{orderable:false,targets:0}]}); });
</script>';
require_once 'layout_end.php';
?>



