<?php
// Shared layout — included at the top of every auth page.
// Sets $pageTitle before including this file.

require_once __DIR__ . '/auth.php';
requireLogin();
$user        = currentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?
<!DOCTYPE html>
<html lang="en">   
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?> — Student Safety System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
 <style>
         :root { --sb: 240px; --primary: #1a3a5c; --primary2: #2d6a9f; }
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }

        /* ── Sidebar ── */
        #sb {
            width: var(--sb); min-height: 100vh;
            background: linear-gradient(180deg, var(--primary) 0%, #0f2540 100%);
            position: fixed; top: 0; left: 0; z-index: 1000;
            transition: transform .3s;
            display: flex; flex-direction: column;
        }
        .sb-brand {
            padding: 1.2rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,.1);
            color: #fff;
        }
        .sb-brand .bi { font-size: 1.9rem; color: #7ec8e3; }
        .sb-section {
            font-size: .68rem; text-transform: uppercase;
            letter-spacing: 1px; color: rgba(255,255,255,.4);
            padding: .9rem 1.2rem .2rem;
        }
        #sb .nav-link {
            color: rgba(255,255,255,.72); padding: .6rem 1.1rem;
            border-radius: 8px; margin: 2px 8px;
            font-size: .88rem; transition: all .2s;
        }
        #sb .nav-link i { width: 20px; margin-right: 7px; }
        #sb .nav-link:hover, #sb .nav-link.active {
            background: rgba(255,255,255,.15); color: #fff;
        }
        #sb .nav-link.text-danger-soft { color: #f1948a; }
        #sb .nav-link.text-danger-soft:hover { background: rgba(231,76,60,.2); color: #f1948a; }

        /* ── Main ── */
        #main { margin-left: var(--sb); min-height: 100vh; transition: margin-left .3s; }
        .topbar {
            background: #fff; border-bottom: 1px solid #e9ecef;
            padding: .7rem 1.5rem; position: sticky; top: 0; z-index: 999;
        }
        .page-body { padding: 1.5rem; }

        /* ── Cards ── */
        .kcard {
            background: #fff; border: none; border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,.07);
        }
        .kcard:hover { box-shadow: 0 6px 20px rgba(0,0,0,.11); }
        .stat-icon {
            width: 50px; height: 50px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
        }

        /* ── Status / severity badges ── */
        .b-open        { background:#fff3cd; color:#856404; }
        .b-under_review{ background:#cff4fc; color:#055160; }
        .b-resolved    { background:#d1e7dd; color:#0a3622; }
        .b-closed      { background:#e2e3e5; color:#41464b; }
        .b-low         { background:#d1e7dd; color:#0a3622; }
        .b-medium      { background:#fff3cd; color:#856404; }
        .b-high        { background:#ffe5d0; color:#7d3c00; }
        .b-critical    { background:#f8d7da; color:#842029; }

        /* ── Responsive ── */
        @media(max-width:768px){
            #sb { transform: translateX(-100%); }
            #sb.open { transform: translateX(0); }
            #main { margin-left: 0; }
        }
 </style>
 </head>
<body>
 
<!-- Sidebar -->
<nav id="sb">
    <div class="sb-brand d-flex align-items-center gap-2">
        <i class="bi bi-shield-check"></i>
        <div>
            <div class="fw-bold" style="font-size:.93rem;">SafeSchool</div>
            <div style="font-size:.7rem;opacity:.55;">Incident Reporting</div>
        </div>
    </div>

    <div class="sb-section">Main</div>
    <ul class="nav flex-column">
        <li><a href="dashboard.php" class="nav-link <?= $currentPage==='dashboard'?'active':'' ?>">
            <i class="bi bi-speedometer2"></i>Dashboard</a></li>
    </ul>

    <div class="sb-section">Management</div>
    <ul class="nav flex-column">
        <li><a href="incidents.php" class="nav-link <?= $currentPage==='incidents'?'active':'' ?>">
            <i class="bi bi-exclamation-triangle"></i>Incidents</a></li>
        <li><a href="students.php" class="nav-link <?= $currentPage==='students'?'active':'' ?>">
            <i class="bi bi-people"></i>Students</a></li>
        <?php if ($user['role']==='admin'): ?>
        <li><a href="users.php" class="nav-link <?= $currentPage==='users'?'active':'' ?>">
            <i class="bi bi-person-gear"></i>Users</a></li>
        <?php endif; ?>
    </ul>

    <div class="sb-section">System</div>
    <ul class="nav flex-column">
        <li><a href="logs.php" class="nav-link <?= $currentPage==='logs'?'active':'' ?>">
            <i class="bi bi-journal-text"></i>Activity Logs</a></li>
        <li><a href="logout.php" class="nav-link text-danger-soft">
            <i class="bi bi-box-arrow-left"></i>Logout</a></li>
    </ul>
</nav>

     <-- Main nga Part -->
<div id="main">
    <div class="topbar d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm btn-outline-secondary d-md-none" onclick="document.getElementById('sb').classList.toggle('open')">
                <i class="bi bi-list"></i>
            </button>
            <span class="fw-semibold text-muted"><?= htmlspecialchars($pageTitle ?? '') ?></span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge rounded-pill bg-<?= $user['role']==='admin'?'danger':'primary' ?>">
                <?= ucfirst($user['role']) ?>
            </span>
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold"
                 style="width:34px;height:34px;font-size:.85rem;">
                <?= strtoupper(substr($user['full_name'],0,1)) ?>
            </div>
            <span class="d-none d-sm-inline small fw-semibold"><?= htmlspecialchars($user['full_name']) ?></span>
        </div>
    </div>
    <div class="page-body">   

