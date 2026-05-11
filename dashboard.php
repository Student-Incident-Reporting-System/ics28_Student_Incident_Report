<?php
// ============================================================
// Dashboard
// SQL joins used:
//   INNER JOIN  incidents ↔ students, categories, users
//   LEFT JOIN   students  ↔ incidents  (include students with 0 incidents)
//   LEFT JOIN   categories ↔ incidents (include categories with 0 incidents)
// ============================================================
$pageTitle = 'Dashboard';
require_once 'db.php';
require_once 'layout.php';

$db = getDB();

// ── Stat counts ───
$totalStudents  = $db->query("SELECT COUNT(*) c FROM students")->fetch_assoc()['c'];
$totalIncidents = $db->query("SELECT COUNT(*) c FROM incidents")->fetch_assoc()['c'];
$openIncidents  = $db->query("SELECT COUNT(*) c FROM incidents WHERE status='open'")->fetch_assoc()['c'];
$resolved       = $db->query("SELECT COUNT(*) c FROM incidents WHERE status='resolved'")->fetch_assoc()['c'];

// ── Chart 1: doughnut — by status ──
$r = $db->query("SELECT status, COUNT(*) cnt FROM incidents GROUP BY status");
$statusLabels = []; $statusData = [];
while ($row = $r->fetch_assoc()) {
    $statusLabels[] = ucfirst(str_replace('_',' ',$row['status']));
    $statusData[]   = (int)$row['cnt'];
}

// ── Chart 2: bar — by category (LEFT JOIN) ───
// LEFT JOIN so categories with 0 incidents still appear
$r = $db->query("
    SELECT ic.name, COUNT(i.id) cnt
    FROM incident_categories ic
    LEFT JOIN incidents i ON i.category_id = ic.id
    GROUP BY ic.id ORDER BY cnt DESC
");
$catLabels = []; $catData = [];
while ($row = $r->fetch_assoc()) { $catLabels[] = $row['name']; $catData[] = (int)$row['cnt']; }

// ── Recent incidents table (INNER JOIN) ──────
// INNER JOIN: only incidents that have a matching student, category, and reporter
$recent = $db->query("
    SELECT i.id, i.incident_code, i.title, i.incident_date, i.status,
           CONCAT(s.first_name,' ',s.last_name) student_name, s.grade,
           ic.name category, ic.severity_level,
           u.full_name reported_by
    FROM incidents i
    INNER JOIN students s             ON i.student_id  = s.id    -- student details
    INNER JOIN incident_categories ic ON i.category_id = ic.id   -- category & severity
    INNER JOIN users u                ON i.reported_by = u.id    -- reporter name
    ORDER BY i.created_at DESC LIMIT 8
");

// ── Top students by incident count (LEFT JOIN) ──────
// LEFT JOIN: include students who have never had an incident
$topStudents = $db->query("
    SELECT s.student_code,
           CONCAT(s.first_name,' ',s.last_name) name,
           s.grade,
           COUNT(i.id) cnt
    FROM students s
    LEFT JOIN incidents i ON i.student_id = s.id
    GROUP BY s.id ORDER BY cnt DESC LIMIT 5
");

// ── Recent activity (INNER JOIN) ───────
$logs = $db->query("
    SELECT al.action, al.details, al.created_at, u.full_name
    FROM activity_logs al
    INNER JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC LIMIT 6
");
?>

<!-- Stat cards -->
<div class="row g-3 mb-4">
    <?php
    $stats = [
        ['icon'=>'bi-people-fill',           'bg'=>'#e8f4fd', 'color'=>'text-primary',  'val'=>$totalStudents,  'label'=>'Total Students'],
        ['icon'=>'bi-exclamation-triangle-fill','bg'=>'#fef9e7','color'=>'text-warning', 'val'=>$totalIncidents, 'label'=>'Total Incidents'],
        ['icon'=>'bi-fire',                  'bg'=>'#fdecea', 'color'=>'text-danger',   'val'=>$openIncidents,  'label'=>'Open Incidents'],
        ['icon'=>'bi-check-circle-fill',     'bg'=>'#eafaf1', 'color'=>'text-success',  'val'=>$resolved,       'label'=>'Resolved'],
    ];
    foreach ($stats as $s): ?>
    <div class="col-6 col-lg-3">
        <div class="kcard p-3 d-flex align-items-center gap-3">
            <div class="stat-icon" style="background:<?= $s['bg'] ?>">
                <i class="bi <?= $s['icon'] ?> <?= $s['color'] ?>"></i>
            </div>
            <div>
                <div class="fs-3 fw-bold lh-1"><?= $s['val'] ?></div>
                <div class="text-muted small mt-1"><?= $s['label'] ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="kcard p-3 h-100">
            <div class="fw-semibold mb-3">Incidents by Status</div>
            <canvas id="chartStatus"></canvas>
        </div>
    </div>
    <div class="col-md-6">
        <div class="kcard p-3 h-100">
            <div class="fw-semibold mb-3">Incidents by Category</div>
            <canvas id="chartCat"></canvas>
        </div>
    </div>
</div>

<!-- Recent incidents -->
<div class="kcard mb-4">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Incidents</span>
        <a href="incidents.php" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tblRecent">
            <thead class="table-light">
                <tr>
                    <th>Code</th><th>Student</th><th>Grade</th>
                    <th>Category</th><th>Severity</th><th>Date</th>
                    <th>Status</th><th>Reported By</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $recent->fetch_assoc()): ?>
                <tr>
                    <td><code><?= htmlspecialchars($row['incident_code']) ?></code></td>
                    <td class="fw-semibold"><?= htmlspecialchars($row['student_name']) ?></td>
                    <td><?= htmlspecialchars($row['grade']) ?></td>
                    <td><?= htmlspecialchars($row['category']) ?></td>
                    <td><span class="badge b-<?= $row['severity_level'] ?> rounded-pill px-2"><?= ucfirst($row['severity_level']) ?></span></td>
                    <td><?= date('M d, Y', strtotime($row['incident_date'])) ?></td>
                    <td><span class="badge b-<?= $row['status'] ?> rounded-pill px-2"><?= ucfirst(str_replace('_',' ',$row['status'])) ?></span></td>
                    <td><?= htmlspecialchars($row['reported_by']) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>


<!-- Bottom row -->
<div class="row g-3">
    <div class="col-md-6">
        <div class="kcard h-100">
            <div class="p-3 border-bottom fw-semibold">
                <i class="bi bi-person-exclamation me-2 text-warning"></i>Students — Incident Count
                <small class="text-muted ms-1">(LEFT JOIN)</small>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>ID</th><th>Name</th><th>Grade</th><th>Incidents</th></tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $topStudents->fetch_assoc()): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($row['student_code']) ?></code></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['grade']) ?></td>
                            <td><span class="badge <?= $row['cnt']>1?'bg-danger':'bg-secondary' ?>"><?= $row['cnt'] ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="kcard h-100">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-semibold"><i class="bi bi-activity me-2 text-success"></i>Recent Activity</span>
                <a href="logs.php" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <ul class="list-group list-group-flush">
            <?php while ($row = $logs->fetch_assoc()): ?>
                <li class="list-group-item d-flex justify-content-between align-items-start py-2">
                    <div>
                        <span class="badge bg-secondary me-1"><?= htmlspecialchars($row['action']) ?></span>
                        <small><?= htmlspecialchars($row['details'] ?? '—') ?></small>
                        <div class="text-muted" style="font-size:.75rem"><?= htmlspecialchars($row['full_name']) ?></div>
                    </div>
                    <small class="text-muted text-nowrap ms-2"><?= date('M d, H:i', strtotime($row['created_at'])) ?></small>
                </li>
            <?php endwhile; ?>
            </ul>
        </div>
    </div>
</div>

<?php
$extraJS = '
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
// Doughnut — status
new Chart(document.getElementById("chartStatus"),{
    type:"doughnut",
    data:{
        labels:'.json_encode($statusLabels).',
        datasets:[{data:'.json_encode($statusData).',
            backgroundColor:["#e74c3c","#f39c12","#27ae60","#95a5a6"],
            borderWidth:2,borderColor:"#fff"}]
    },
    options:{plugins:{legend:{position:"bottom"}},cutout:"65%"}
});
// Bar — category
new Chart(document.getElementById("chartCat"),{
    type:"bar",
    data:{
        labels:'.json_encode($catLabels).',
        datasets:[{label:"Incidents",data:'.json_encode($catData).',
            backgroundColor:"rgba(231,76,60,.75)",borderRadius:5}]
    },
    options:{indexAxis:"y",plugins:{legend:{display:false}},scales:{x:{beginAtZero:true,ticks:{stepSize:1}}}}
});
// DataTable
$(()=>{ $("#tblRecent").DataTable({pageLength:5,lengthMenu:[5,10],order:[[5,"desc"]]}); });
</script>';
require_once 'layout_end.php';
?>
