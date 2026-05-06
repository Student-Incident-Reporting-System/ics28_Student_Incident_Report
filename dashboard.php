<?php
// ============================================================
// Dashboard — Overview with charts and summary tables
// SQL Joins used:
//   INNER JOIN: incidents ↔ students, categories, users
//   LEFT JOIN:  students with their incident count (includes students with 0 incidents)
// ============================================================
$pageTitle = 'Dashboard';
require_once 'db.php';
require_once 'layout_header.php';

$db = getDB();

// --- Stat Counts ---
$totalStudents  = $db->query("SELECT COUNT(*) AS c FROM students")->fetch_assoc()['c'];
$totalIncidents = $db->query("SELECT COUNT(*) AS c FROM incidents")->fetch_assoc()['c'];
$openIncidents  = $db->query("SELECT COUNT(*) AS c FROM incidents WHERE status = 'open'")->fetch_assoc()['c'];
$resolvedCount  = $db->query("SELECT COUNT(*) AS c FROM incidents WHERE status = 'resolved'")->fetch_assoc()['c'];

// --- Incidents by Status (for doughnut chart) ---
$statusData = $db->query("SELECT status, COUNT(*) AS cnt FROM incidents GROUP BY status");
$statusLabels = []; $statusCounts = [];
while ($row = $statusData->fetch_assoc()) {
    $statusLabels[] = ucfirst(str_replace('_', ' ', $row['status']));
    $statusCounts[] = (int)$row['cnt'];
}

// --- Incidents by Category (for bar chart) ---
$catData = $db->query("
    SELECT ic.name, COUNT(i.id) AS cnt
    FROM incident_categories ic
    -- LEFT JOIN: include categories even if they have no incidents
    LEFT JOIN incidents i ON i.category_id = ic.id
    GROUP BY ic.id, ic.name
    ORDER BY cnt DESC
");
$catLabels = []; $catCounts = [];
while ($row = $catData->fetch_assoc()) {
    $catLabels[] = $row['name'];
    $catCounts[] = (int)$row['cnt'];
}

// --- Monthly Incidents (last 6 months, for line chart) ---
$monthData = $db->query("
    SELECT DATE_FORMAT(incident_date, '%b %Y') AS month,
           YEAR(incident_date) AS yr,
           MONTH(incident_date) AS mo,
           COUNT(*) AS cnt
    FROM incidents
    WHERE incident_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY yr, mo
    ORDER BY yr, mo
");
$monthLabels = []; $monthCounts = [];
while ($row = $monthData->fetch_assoc()) {
    $monthLabels[] = $row['month'];
    $monthCounts[] = (int)$row['cnt'];
}

// --- Recent Incidents (INNER JOIN: incidents + students + categories + users) ---
// INNER JOIN ensures we only get incidents that have matching student, category, and reporter
$recentIncidents = $db->query("
    SELECT
        i.incident_code,
        i.title,
        i.incident_date,
        i.status,
        CONCAT(s.first_name, ' ', s.last_name) AS student_name,
        s.grade,
        ic.name AS category,
        ic.severity_level,
        u.full_name AS reported_by
    FROM incidents i
    INNER JOIN students s         ON i.student_id  = s.id       -- get student details
    INNER JOIN incident_categories ic ON i.category_id = ic.id  -- get category info
    INNER JOIN users u            ON i.reported_by = u.id       -- get reporter name
    ORDER BY i.created_at DESC
    LIMIT 8
");

// --- Top Students with Most Incidents (LEFT JOIN: all students, even those with 0) ---
$topStudents = $db->query("
    SELECT
        s.student_id,
        CONCAT(s.first_name, ' ', s.last_name) AS student_name,
        s.grade,
        COUNT(i.id) AS incident_count
    FROM students s
    LEFT JOIN incidents i ON i.student_id = s.id  -- LEFT JOIN: include students with no incidents
    GROUP BY s.id
    ORDER BY incident_count DESC
    LIMIT 5
");

// --- Recent Activity Logs (INNER JOIN: logs + users) ---
$recentLogs = $db->query("
    SELECT al.action, al.details, al.created_at, u.full_name
    FROM activity_logs al
    INNER JOIN users u ON al.user_id = u.id  -- INNER JOIN: only logs with valid users
    ORDER BY al.created_at DESC
    LIMIT 6
");
?>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#e8f4fd;">
                    <i class="bi bi-people-fill text-primary" style="font-size:1.5rem;"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold"><?= $totalStudents ?></div>
                    <div class="text-muted small">Total Students</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#fef9e7;">
                    <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size:1.5rem;"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold"><?= $totalIncidents ?></div>
                    <div class="text-muted small">Total Incidents</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#fdecea;">
                    <i class="bi bi-fire text-danger" style="font-size:1.5rem;"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold"><?= $openIncidents ?></div>
                    <div class="text-muted small">Open Incidents</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#eafaf1;">
                    <i class="bi bi-check-circle-fill text-success" style="font-size:1.5rem;"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold"><?= $resolvedCount ?></div>
                    <div class="text-muted small">Resolved</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card h-100">
            <div class="card-header fw-semibold">Incidents by Status</div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="statusChart" height="220"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card h-100">
            <div class="card-header fw-semibold">Monthly Trend</div>
            <div class="card-body">
                <canvas id="monthChart" height="220"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card h-100">
            <div class="card-header fw-semibold">Incidents by Category</div>
            <div class="card-body">
                <canvas id="catChart" height="220"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Incidents Table -->
<div class="table-card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Incidents</span>
        <a href="incidents.php" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle" id="recentTable">
            <thead class="table-light">
                <tr>
                    <th>Code</th>
                    <th>Student</th>
                    <th>Grade</th>
                    <th>Category</th>
                    <th>Severity</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Reported By</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($inc = $recentIncidents->fetch_assoc()): ?>
                <tr>
                    <td><code><?= htmlspecialchars($inc['incident_code']) ?></code></td>
                    <td class="fw-semibold"><?= htmlspecialchars($inc['student_name']) ?></td>
                    <td><?= htmlspecialchars($inc['grade']) ?></td>
                    <td><?= htmlspecialchars($inc['category']) ?></td>
                    <td>
                        <span class="badge badge-<?= $inc['severity_level'] ?> rounded-pill px-2">
                            <?= ucfirst($inc['severity_level']) ?>
                        </span>
                    </td>
                    <td><?= date('M d, Y', strtotime($inc['incident_date'])) ?></td>
                    <td>
                        <span class="badge badge-<?= str_replace('_','-',$inc['status']) ?> rounded-pill px-2">
                            <?= ucfirst(str_replace('_', ' ', $inc['status'])) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($inc['reported_by']) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Bottom Row: Top Students + Activity Log -->
<div class="row g-3">
    <div class="col-md-6">
        <div class="table-card">
            <div class="card-header fw-semibold">
                <i class="bi bi-person-exclamation me-2 text-warning"></i>Students with Most Incidents
                <small class="text-muted ms-2">(LEFT JOIN — includes students with 0)</small>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr><th>Student ID</th><th>Name</th><th>Grade</th><th>Incidents</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($s = $topStudents->fetch_assoc()): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($s['student_id']) ?></code></td>
                            <td><?= htmlspecialchars($s['student_name']) ?></td>
                            <td><?= htmlspecialchars($s['grade']) ?></td>
                            <td>
                                <span class="badge <?= $s['incident_count'] > 1 ? 'bg-danger' : 'bg-secondary' ?>">
                                    <?= $s['incident_count'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="table-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold"><i class="bi bi-activity me-2 text-success"></i>Recent Activity</span>
                <a href="activity_logs.php" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <ul class="list-group list-group-flush">
                <?php while ($log = $recentLogs->fetch_assoc()): ?>
                <li class="list-group-item d-flex justify-content-between align-items-start py-2">
                    <div>
                        <span class="badge bg-secondary me-2"><?= htmlspecialchars($log['action']) ?></span>
                        <small><?= htmlspecialchars($log['details'] ?? '—') ?></small>
                        <div class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($log['full_name']) ?></div>
                    </div>
                    <small class="text-muted text-nowrap ms-2">
                        <?= date('M d, H:i', strtotime($log['created_at'])) ?>
                    </small>
                </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>
</div>

<?php
$extraScripts = '
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
// Doughnut — Incidents by Status
new Chart(document.getElementById("statusChart"), {
    type: "doughnut",
    data: {
        labels: ' . json_encode($statusLabels) . ',
        datasets: [{
            data: ' . json_encode($statusCounts) . ',
            backgroundColor: ["#e74c3c","#f39c12","#27ae60","#95a5a6"],
            borderWidth: 2, borderColor: "#fff"
        }]
    },
    options: { plugins: { legend: { position: "bottom" } }, cutout: "65%" }
});

// Line — Monthly Trend
new Chart(document.getElementById("monthChart"), {
    type: "line",
    data: {
        labels: ' . json_encode($monthLabels) . ',
        datasets: [{
            label: "Incidents",
            data: ' . json_encode($monthCounts) . ',
            borderColor: "#2d6a9f",
            backgroundColor: "rgba(45,106,159,0.1)",
            tension: 0.4, fill: true, pointRadius: 5
        }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

// Bar — By Category
new Chart(document.getElementById("catChart"), {
    type: "bar",
    data: {
        labels: ' . json_encode($catLabels) . ',
        datasets: [{
            label: "Incidents",
            data: ' . json_encode($catCounts) . ',
            backgroundColor: "rgba(231,76,60,0.75)",
            borderRadius: 6
        }]
    },
    options: {
        indexAxis: "y",
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});

// DataTable for recent incidents
$(document).ready(function() {
    $("#recentTable").DataTable({
        pageLength: 5,
        lengthMenu: [5, 10],
        order: [[5, "desc"]]
    });
});
</script>';
require_once 'layout_footer.php';
?>
