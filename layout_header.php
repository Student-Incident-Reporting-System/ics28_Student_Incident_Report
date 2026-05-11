<?php
// ============================================================
// Shared Layout Header — included by all authenticated pages
// ============================================================
require_once __DIR__ . '/auth.php';
requireLogin();
$user = currentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?> — Student Safety System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 250px;
            --primary: #1a3a5c;
            --primary-light: #2d6a9f;
            --accent: #e74c3c;
        }
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }

        /* Sidebar */
        #sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: linear-gradient(180deg, var(--primary) 0%, #0f2540 100%);
            position: fixed;
            top: 0; left: 0;
            z-index: 1000;
            transition: transform 0.3s ease;
        }
        #sidebar .sidebar-brand {
            padding: 1.25rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: #fff;
        }
        #sidebar .sidebar-brand .brand-icon { font-size: 1.8rem; color: #5dade2; }
        #sidebar .nav-link {
            color: rgba(255,255,255,0.75);
            padding: 0.65rem 1.25rem;
            border-radius: 8px;
            margin: 2px 8px;
            transition: all 0.2s;
            font-size: 0.9rem;
        }
        #sidebar .nav-link:hover,
        #sidebar .nav-link.active {
            background: rgba(255,255,255,0.15);
            color: #fff;
        }
        #sidebar .nav-link i { width: 20px; margin-right: 8px; }
        #sidebar .nav-section {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255,255,255,0.4);
            padding: 1rem 1.25rem 0.25rem;
        }

        /* Main content */
        #main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        .topbar {
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            padding: 0.75rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .page-content { padding: 1.5rem; }

        /* Cards */
        .stat-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.12); }
        .stat-icon {
            width: 52px; height: 52px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
        }

        /* Badges */
        .badge-open      { background: #fff3cd; color: #856404; }
        .badge-review    { background: #cff4fc; color: #055160; }
        .badge-resolved  { background: #d1e7dd; color: #0a3622; }
        .badge-closed    { background: #e2e3e5; color: #41464b; }
        .badge-low       { background: #d1e7dd; color: #0a3622; }
        .badge-medium    { background: #fff3cd; color: #856404; }
        .badge-high      { background: #ffe5d0; color: #7d3c00; }
        .badge-critical  { background: #f8d7da; color: #842029; }

        /* Table */
        .table-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            overflow: hidden;
        }
        .table-card .card-header {
            background: #fff;
            border-bottom: 1px solid #f0f0f0;
            padding: 1rem 1.25rem;
        }

        /* Responsive sidebar */
        @media (max-width: 768px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.show { transform: translateX(0); }
            #main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<nav id="sidebar">
    <div class="sidebar-brand d-flex align-items-center gap-2">
        <span class="brand-icon"><i class="bi bi-shield-check"></i></span>
        <div>
            <div class="fw-bold" style="font-size:0.95rem;">SafeSchool</div>
            <div style="font-size:0.72rem; opacity:0.6;">Incident Reporting</div>
        </div>
    </div>

    <div class="nav-section">Main</div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
    </ul>

    <div class="nav-section">Management</div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="incidents.php" class="nav-link <?= $currentPage === 'incidents' ? 'active' : '' ?>">
                <i class="bi bi-exclamation-triangle"></i> Incidents
            </a>
        </li>
        <li class="nav-item">
            <a href="students.php" class="nav-link <?= $currentPage === 'students' ? 'active' : '' ?>">
                <i class="bi bi-people"></i> Students
            </a>
        </li>
        <?php if ($user['role'] === 'admin'): ?>
        <li class="nav-item">
            <a href="users.php" class="nav-link <?= $currentPage === 'users' ? 'active' : '' ?>">
                <i class="bi bi-person-gear"></i> Users
            </a>
        </li>
        <?php endif; ?>
    </ul>

    <div class="nav-section">System</div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="activity_logs.php" class="nav-link <?= $currentPage === 'activity_logs' ? 'active' : '' ?>">
                <i class="bi bi-journal-text"></i> Activity Logs
            </a>
        </li>
        <li class="nav-item">
            <a href="logout.php" class="nav-link text-danger-emphasis">
                <i class="bi bi-box-arrow-left"></i> Logout
            </a>
        </li>
    </ul>
</nav>

<!-- Main Content -->
<div id="main-content">
    <!-- Topbar -->
    <div class="topbar d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm btn-outline-secondary d-md-none" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>
            <h6 class="mb-0 fw-semibold text-muted"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h6>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : 'primary' ?> rounded-pill">
                <?= ucfirst($user['role']) ?>
            </span>
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                     style="width:34px;height:34px;font-size:0.85rem;font-weight:600;">
                    <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                </div>
                <span class="d-none d-sm-inline small fw-semibold"><?= htmlspecialchars($user['full_name']) ?></span>
            </div>
        </div>
    </div>

    <div class="page-content">
