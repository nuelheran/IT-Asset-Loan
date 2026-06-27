<?php
require_once __DIR__ . '/functions.php';
requireLogin();
$user = currentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?php echo isset($pageTitle) ? esc($pageTitle) . ' — ' : ''; ?>IT Asset Loan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>
<div class="mobile-topbar no-desktop">
    <button class="hamburger-btn" id="sidebarToggle" aria-label="Buka menu" aria-expanded="false">
        <span></span><span></span><span></span>
    </button>
    <div class="mobile-brand">
        <span class="mark mark-sm">AL</span>
        <span class="mobile-brand-title">Asset Loan</span>
    </div>
    <div class="avatar avatar-sm"><?php echo esc(strtoupper(substr($user['name'],0,1))); ?></div>
</div>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <button class="sidebar-close no-desktop" id="sidebarClose" aria-label="Tutup menu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
        <div class="sidebar-brand">
            <div class="flex-gap">
                <span class="mark">AL</span>
                <div>
                    <div class="title">Asset Loan</div>
                    <div class="subtitle">IT Inventory</div>
                </div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="<?php echo BASE_URL; ?>dashboard.php" class="<?php echo $currentPage=='dashboard.php'?'active':''; ?>" data-tooltip="Dashboard">
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg></span>
                <span class="nav-label">Dashboard</span>
            </a>
            <a href="<?php echo BASE_URL; ?>scan.php" class="<?php echo $currentPage=='scan.php'?'active':''; ?>" data-tooltip="Scan Aset">
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><line x1="7" y1="12" x2="17" y2="12"/></svg></span>
                <span class="nav-label">Scan Aset</span>
            </a>

            <?php if (isAdmin()): ?>
                <div class="nav-section-label">Manajemen</div>
                <a href="<?php echo BASE_URL; ?>assets_list.php" class="<?php echo in_array($currentPage,['assets_list.php','asset_form.php'])?'active':''; ?>" data-tooltip="Master Aset">
                    <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="13" rx="2"/><path d="M9 21h6"/><path d="M12 16v5"/></svg></span>
                    <span class="nav-label">Master Aset</span>
                </a>
                <a href="<?php echo BASE_URL; ?>categories.php" class="<?php echo $currentPage=='categories.php'?'active':''; ?>" data-tooltip="Kategori">
                    <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="8" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/><rect x="13" y="13" width="8" height="8" rx="1.5"/></svg></span>
                    <span class="nav-label">Kategori</span>
                </a>
                <a href="<?php echo BASE_URL; ?>users_list.php" class="<?php echo in_array($currentPage,['users_list.php','user_form.php'])?'active':''; ?>" data-tooltip="Pengguna">
                    <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
                    <span class="nav-label">Pengguna</span>
                </a>
                <div class="nav-section-label">Peminjaman</div>
                <a href="<?php echo BASE_URL; ?>loans_list.php" class="<?php echo ($currentPage=='loans_list.php' && ($_GET['status'] ?? '')==='')?'active':''; ?>" data-tooltip="Semua Peminjaman">
                    <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6"/><path d="M9 17h6"/></svg></span>
                    <span class="nav-label">Semua Peminjaman</span>
                </a>
                <a href="<?php echo BASE_URL; ?>loans_list.php?status=pending" class="<?php echo ($currentPage=='loans_list.php' && ($_GET['status'] ?? '')=='pending')?'active':''; ?>" data-tooltip="Perlu Persetujuan">
                    <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg></span>
                    <span class="nav-label">Perlu Persetujuan</span>
                    <?php
                    $pendingNavCount = getConnection()->query("SELECT COUNT(*) c FROM loans WHERE status='pending'")->fetch_assoc()['c'];
                    if ($pendingNavCount > 0): ?>
                        <span class="nav-badge"><?php echo $pendingNavCount; ?></span>
                    <?php endif; ?>
                </a>
            <?php else: ?>
                <div class="nav-section-label">Peminjaman</div>
                <a href="<?php echo BASE_URL; ?>loan_request.php" class="<?php echo $currentPage=='loan_request.php'?'active':''; ?>" data-tooltip="Ajukan Peminjaman">
                    <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg></span>
                    <span class="nav-label">Ajukan Peminjaman</span>
                </a>
                <a href="<?php echo BASE_URL; ?>my_loans.php" class="<?php echo $currentPage=='my_loans.php'?'active':''; ?>" data-tooltip="Peminjaman Saya">
                    <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6"/><path d="M9 17h6"/></svg></span>
                    <span class="nav-label">Peminjaman Saya</span>
                </a>
                <a href="<?php echo BASE_URL; ?>assets_list.php" class="<?php echo $currentPage=='assets_list.php'?'active':''; ?>" data-tooltip="Katalog Aset">
                    <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="13" rx="2"/><path d="M9 21h6"/><path d="M12 16v5"/></svg></span>
                    <span class="nav-label">Katalog Aset</span>
                </a>
            <?php endif; ?>

            <div class="nav-section-label">Lainnya</div>
            <a href="<?php echo BASE_URL; ?>profile.php" class="<?php echo $currentPage=='profile.php'?'active':''; ?>" data-tooltip="Profil Saya">
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></span>
                <span class="nav-label">Profil Saya</span>
            </a>
            <a href="<?php echo BASE_URL; ?>logout.php" data-tooltip="Keluar">
                <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg></span>
                <span class="nav-label">Keluar</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            &copy; <?php echo date('Y'); ?> IT Asset Loan System
        </div>
    </aside>

    <div class="main">
        <div class="topbar no-mobile">
            <div>
                <div class="page-eyebrow"><?php echo isAdmin() ? 'Panel Admin' : 'Panel Karyawan'; ?></div>
                <div class="page-title"><?php echo isset($pageTitle) ? esc($pageTitle) : 'Dashboard'; ?></div>
            </div>
            <div class="user-chip">
                <div>
                    <div style="font-weight:600;"><?php echo esc($user['name']); ?></div>
                    <div class="role-tag"><?php echo esc($user['department'] ?? ''); ?></div>
                </div>
                <div class="avatar"><?php echo esc(strtoupper(substr($user['name'],0,1))); ?></div>
            </div>
        </div>
        <div class="mobile-page-heading no-desktop">
            <div class="page-eyebrow"><?php echo isAdmin() ? 'Panel Admin' : 'Panel Karyawan'; ?></div>
            <div class="page-title"><?php echo isset($pageTitle) ? esc($pageTitle) : 'Dashboard'; ?></div>
        </div>
        <div class="content">
<?php
$flashSuccess = flash('success');
$flashError = flash('error');
if ($flashSuccess): ?>
            <div class="alert alert-success" role="status"><?php echo esc($flashSuccess); ?></div>
<?php endif;
if ($flashError): ?>
            <div class="alert alert-danger" role="alert"><?php echo esc($flashError); ?></div>
<?php endif; ?>
