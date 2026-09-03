<?php
/**
 * Faculty Management System (FMS)
 * Standard HTML Header & Modern Top Navigation
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$currentUser = getCurrentUser();
$pageTitle = $pageTitle ?? 'Dashboard';
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape($pageTitle); ?> &bull; <?php echo escape(APP_NAME); ?></title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="/fms/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/print.css">
</head>
<body>
<div class="app-wrapper">
    <!-- Sidebar inclusion based on user role -->
    <?php 
    if ($currentUser['role'] === 'admin') {
        include __DIR__ . '/admin-sidebar.php';
    } else {
        include __DIR__ . '/faculty-sidebar.php';
    }
    ?>

    <!-- Main Wrapper -->
    <div class="main-wrapper">
        <!-- Top Navbar matching screenshot -->
        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="btn-sidebar-toggle" id="sidebarToggle" aria-label="Toggle navigation">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
                <h1 class="page-title"><?php echo escape($pageTitle); ?></h1>
            </div>

            <div class="topbar-right">
                <!-- Search Input -->
                <div class="topbar-search">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input type="text" placeholder="Search here..." id="globalSearchInput">
                </div>

                <!-- Notification Bell with Red Badge "5" -->
                <div class="notification-btn" title="5 Unread Notifications">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                    <span class="notification-badge">5</span>
                </div>

                <!-- Developer Credits Pill -->
                <div style="display: none; @media (min-width: 900px) { display: flex; } align-items: center; gap: 8px; background: rgba(99, 102, 241, 0.08); border: 1px solid rgba(99, 102, 241, 0.2); padding: 5px 12px; border-radius: 9999px; font-size: 11.5px; color: #4338CA;">
                    <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: #10B981;"></span>
                    <span>Built by <strong>Saniya Momin (124)</strong> &amp; <strong>Tasmiya Shaikh (123)</strong> &bull; Mentor: <strong>Prof. Mahwish Momin</strong></span>
                </div>

                <!-- User Profile Pill with AI Bot Avatar -->
                <a href="<?php echo BASE_URL; ?><?php echo ($currentUser['role'] === 'admin' ? 'admin/dashboard.php' : 'faculty/profile.php'); ?>" class="user-profile-badge">
                    <img 
                        src="<?php echo escape(getSafeAvatar($currentUser['photo'] ?? '', $currentUser['full_name'])); ?>" 
                        alt="<?php echo escape($currentUser['full_name']); ?>" 
                        class="user-profile-avatar"
                    >
                    <div class="user-profile-info">
                        <span class="user-profile-name"><?php echo escape($currentUser['full_name'] ?: 'Administrator'); ?></span>
                        <span class="user-profile-role"><?php echo ($currentUser['role'] === 'admin' ? 'Super Admin' : 'Faculty Member'); ?></span>
                    </div>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #94A3B8; margin-left: 2px;">
                        <polyline points="6 9 12 15 18 9"/>
                    </svg>
                </a>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="content-container">
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo escape($flash['type']); ?>" style="padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; display: flex; align-items: center; gap: 10px; background: #DCFCE7; color: #15803D; border: 1px solid #BBF7D0;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    <span><?php echo escape($flash['message']); ?></span>
                </div>
            <?php endif; ?>
