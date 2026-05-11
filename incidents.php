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
