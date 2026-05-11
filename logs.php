<?php
// Activity Logs — read-only audit trail
// SQL joins:
//   INNER JOIN logs ↔ users     — user name for every log entry
//   LEFT JOIN  logs ↔ incidents — show incident code when applicable

$pageTitle = 'Activity Logs';
require_once 'db.php';
require_once 'layout.php';
