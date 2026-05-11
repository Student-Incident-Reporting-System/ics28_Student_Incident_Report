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

?>