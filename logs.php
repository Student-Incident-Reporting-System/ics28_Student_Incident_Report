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



