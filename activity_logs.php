<?php
// ============================================================
// Activity Logs — Read-only audit trail
// SQL Joins:
//   INNER JOIN activity_logs ↔ users: get user name for each log entry
//   LEFT JOIN activity_logs ↔ incidents (via target_id): get incident code when applicable
// ============================================================
$pageTitle = 'Activity Logs';
require_once 'db.php';
require_once 'layout_header.php';

$db = getDB();

// ---- Fetch logs with INNER JOIN (users) + LEFT JOIN (incidents for context) ----
$logs = $db->query("
    SELECT
        al.id,
        al.action,
        al.target_table,
        al.target_id,
        al.details,
        al.ip_address,
        al.created_at,
        u.full_name,
        u.username,
        u.role,
        -- LEFT JOIN: show incident code if the log relates to an incident
        i.incident_code
    FROM activity_logs al
    INNER JOIN users u    ON al.user_id   = u.id                          -- INNER JOIN: get user info
    LEFT JOIN incidents i ON al.target_table = 'incidents' AND al.target_id = i.id  -- LEFT JOIN: optional incident link
    ORDER BY al.created_at DESC
    LIMIT 200
");

// Action badge colors
$actionColors = [
    'LOGIN'  => 'success',
    'LOGOUT' => 'secondary',
    'CREATE' => 'primary',
    'UPDATE' => 'warning',
    'DELETE' => 'danger',
];
?>

<div class="table-card">
    <div class="card-header fw-semibold">
        <i class="bi bi-journal-text me-2 text-primary"></i>System Activity Logs
        <small class="text-muted ms-2">(INNER JOIN: users | LEFT JOIN: incidents)</small>
    </div>
    <div class="table-responsive p-2">
        <table class="table table-hover align-middle table-sm" id="logsTable">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Action</th>
                    <th>Target</th>
                    <th>Details</th>
                    <th>IP Address</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($log = $logs->fetch_assoc()): ?>
                <tr>
                    <td class="text-muted small"><?= $log['id'] ?></td>
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($log['full_name']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($log['username']) ?></small>
                    </td>
                    <td>
                        <span class="badge <?= $log['role'] === 'admin' ? 'bg-danger' : 'bg-primary' ?> rounded-pill">
                            <?= ucfirst($log['role']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-<?= $actionColors[$log['action']] ?? 'secondary' ?>">
                            <?= htmlspecialchars($log['action']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($log['target_table']): ?>
                            <code><?= htmlspecialchars($log['target_table']) ?></code>
                            <?php if ($log['incident_code']): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($log['incident_code']) ?></small>
                            <?php elseif ($log['target_id']): ?>
                                <br><small class="text-muted">ID: <?= $log['target_id'] ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($log['details'] ?? '—') ?></td>
                    <td><code><?= htmlspecialchars($log['ip_address'] ?? '—') ?></code></td>
                    <td class="text-nowrap">
                        <?= date('M d, Y H:i:s', strtotime($log['created_at'])) ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$extraScripts = '
<script>
$(document).ready(function() {
    $("#logsTable").DataTable({
        pageLength: 25,
        order: [[7, "desc"]],
        columnDefs: [{ orderable: false, targets: 0 }]
    });
});
</script>';
require_once 'layout_footer.php';
?>
