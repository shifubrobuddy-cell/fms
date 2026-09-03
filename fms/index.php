<?php
/**
 * Faculty Management System (FMS)
 * Institutional Introduction Portal & Gateway - Dark Theme Edition
 * Developed by: Saniya Momin (Roll No: 124) & Tasmiya Shaikh (Roll No: 123)
 * Project Mentor: Assistant Professor Mahwish Momin
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

$currentUser = null;
if (isLoggedIn()) {
    $currentUser = [
        'name' => $_SESSION['full_name'] ?? $_SESSION['username'],
        'role' => $_SESSION['user_role'] ?? 'faculty',
        'dashboard' => ($_SESSION['user_role'] === 'admin') ? 'admin/dashboard.php' : 'faculty/dashboard.php',
        'photo' => $_SESSION['photo'] ?? getAIBotAvatarUrl($_SESSION['username'] ?? 'User')
    ];
}

// Handle 1-click direct login request from landing page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['direct_login'])) {
    $targetUser = trim($_POST['direct_login']);
    $res = attemptLogin($targetUser, 'password123');
    if ($res['success']) {
        if ($res['role'] === 'admin') {
            redirect('admin/dashboard.php');
        } else {
            redirect('faculty/dashboard.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape(APP_NAME); ?> &bull; Institutional Academic Portal</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="/fms/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --dark-bg: #090D16;
            --dark-surface: #111827;
            --dark-surface-hover: #162032;
            --dark-card: #0F172A;
            --dark-border: rgba(255, 255, 255, 0.08);
            --dark-border-glow: rgba(99, 102, 241, 0.3);
            --text-primary: #F8FAFC;
            --text-secondary: #94A3B8;
            --text-muted: #64748B;
            --accent-indigo: #6366F1;
            --accent-cyan: #06B6D4;
            --accent-emerald: #10B981;
            --accent-rose: #F43F5E;
        }

        body {
            background-color: var(--dark-bg);
            color: var(--text-primary);
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-image: 
                radial-gradient(at 15% 15%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 85% 25%, rgba(6, 182, 212, 0.12) 0px, transparent 50%),
                radial-gradient(at 50% 80%, rgba(139, 92, 246, 0.1) 0px, transparent 60%);
            background-attachment: fixed;
        }

        /* Top Navbar */
        .dark-navbar {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--dark-border);
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .dark-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #FFFFFF;
        }
        .dark-logo {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, #6366F1, #4F46E5);
            color: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        /* Project Credits Top Tag */
        .credits-pill-top {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(99, 102, 241, 0.12);
            border: 1px solid rgba(99, 102, 241, 0.3);
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 12px;
            color: #C7D2FE;
        }
        .credits-pill-top strong {
            color: #FFFFFF;
        }

        /* Hero Section */
        .dark-hero {
            max-width: 1100px;
            margin: 40px auto 30px;
            padding: 0 20px;
            text-align: center;
        }
        .hero-badge-dark {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(99, 102, 241, 0.15);
            color: #A5B4FC;
            font-size: 12px;
            font-weight: 700;
            padding: 6px 16px;
            border-radius: 9999px;
            margin-bottom: 20px;
            border: 1px solid rgba(99, 102, 241, 0.35);
            box-shadow: 0 0 16px rgba(99, 102, 241, 0.2);
            letter-spacing: 0.05em;
        }
        .hero-title-dark {
            font-size: 42px;
            font-weight: 800;
            line-height: 1.2;
            color: #FFFFFF;
            letter-spacing: -0.025em;
            margin: 0 0 16px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.5);
        }
        .hero-title-gradient {
            background: linear-gradient(135deg, #FFFFFF 30%, #A5B4FC 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-desc-dark {
            font-size: 16.5px;
            color: var(--text-secondary);
            max-width: 780px;
            margin: 0 auto 28px;
            line-height: 1.6;
        }

        /* Project Attribution Banner */
        .project-attribution-banner {
            max-width: 860px;
            margin: 0 auto 36px;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(99, 102, 241, 0.25);
            border-radius: 14px;
            padding: 14px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            box-shadow: 0 8px 24px -6px rgba(0, 0, 0, 0.4);
        }
        .att-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }
        .att-role {
            color: var(--text-muted);
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .att-name {
            color: #FFFFFF;
            font-weight: 700;
        }
        .att-roll {
            background: rgba(99, 102, 241, 0.25);
            color: #E0E7FF;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            border: 1px solid rgba(99, 102, 241, 0.35);
        }

        /* Direct CTAs */
        .hero-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }
        .btn-action-primary {
            background: linear-gradient(135deg, #6366F1, #4F46E5);
            color: #FFFFFF;
            padding: 12px 26px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14.5px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            box-shadow: 0 4px 16px rgba(99, 102, 241, 0.4);
            transition: all 0.2s ease;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        .btn-action-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 22px rgba(99, 102, 241, 0.6);
            color: #FFFFFF;
        }
        .btn-action-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: #E2E8F0;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14.5px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            border: 1px solid var(--dark-border);
            transition: all 0.2s ease;
        }
        .btn-action-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
            color: #FFFFFF;
        }

        /* Auto-approval Banner */
        .notice-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.25);
            color: #6EE7B7;
            font-size: 13px;
            padding: 8px 18px;
            border-radius: 10px;
            margin-bottom: 40px;
        }

        /* Portal Cards Grid */
        .portal-grid-dark {
            max-width: 1120px;
            margin: 0 auto 50px;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .dark-portal-card {
            background: rgba(17, 24, 39, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid var(--dark-border);
            border-radius: 14px;
            padding: 22px;
            display: flex;
            flex-direction: column;
            position: relative;
            transition: all 0.2s ease;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        .dark-portal-card:hover {
            transform: translateY(-4px);
            border-color: var(--dark-border-glow);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.5), 0 0 20px rgba(99, 102, 241, 0.15);
        }

        .portal-header-row {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 14px;
        }
        .ai-bot-avatar {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: #1E293B;
            border: 1.5px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }
        .ai-bot-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .portal-tag {
            font-size: 10.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 3px 8px;
            border-radius: 5px;
            display: inline-block;
            margin-bottom: 4px;
        }
        .tag-admin { background: rgba(244, 63, 94, 0.15); color: #FDA4AF; border: 1px solid rgba(244, 63, 94, 0.3); }
        .tag-cs { background: rgba(99, 102, 241, 0.15); color: #C7D2FE; border: 1px solid rgba(99, 102, 241, 0.3); }
        .tag-it { background: rgba(6, 182, 212, 0.15); color: #A5F3FC; border: 1px solid rgba(6, 182, 212, 0.3); }
        .tag-ece { background: rgba(168, 85, 247, 0.15); color: #E9D5FF; border: 1px solid rgba(168, 85, 247, 0.3); }
        .tag-guide { background: rgba(16, 185, 129, 0.15); color: #6EE7B7; border: 1px solid rgba(16, 185, 129, 0.3); }

        .portal-card-name {
            font-size: 18px;
            font-weight: 700;
            color: #FFFFFF;
            margin: 0;
        }
        .portal-card-desc {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.5;
            margin: 0 0 16px;
            flex-grow: 1;
        }

        .portal-login-box {
            background: rgba(0, 0, 0, 0.25);
            border: 1px dashed rgba(255, 255, 255, 0.12);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 12px;
            color: #94A3B8;
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .portal-login-box code {
            color: #E2E8F0;
            background: rgba(255, 255, 255, 0.08);
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 700;
        }

        .btn-enter-portal {
            width: 100%;
            height: 40px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.15s ease;
            color: #FFFFFF;
        }
        .btn-admin { background: linear-gradient(135deg, #E11D48, #BE123C); box-shadow: 0 3px 12px rgba(225, 29, 72, 0.35); }
        .btn-admin:hover { background: #9F1239; }
        .btn-affan { background: linear-gradient(135deg, #4F46E5, #3730A3); box-shadow: 0 3px 12px rgba(79, 70, 229, 0.35); }
        .btn-affan:hover { background: #312E81; }
        .btn-shagufta { background: linear-gradient(135deg, #0284C7, #0369A1); box-shadow: 0 3px 12px rgba(2, 132, 199, 0.35); }
        .btn-shagufta:hover { background: #075985; }
        .btn-ummul { background: linear-gradient(135deg, #0D9488, #0F766E); box-shadow: 0 3px 12px rgba(13, 148, 136, 0.35); }
        .btn-ummul:hover { background: #115E59; }
        .btn-sofia { background: linear-gradient(135deg, #7C3AED, #6D28D9); box-shadow: 0 3px 12px rgba(124, 58, 237, 0.35); }
        .btn-sofia:hover { background: #5B21B6; }
        .btn-mahwish { background: linear-gradient(135deg, #059669, #047857); box-shadow: 0 3px 12px rgba(5, 150, 105, 0.35); }
        .btn-mahwish:hover { background: #065F46; }

        /* Feature Section */
        .dark-features-wrap {
            max-width: 1120px;
            margin: 20px auto 60px;
            padding: 0 20px;
        }
        .features-grid-dark {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }
        .feature-card-dark {
            background: rgba(17, 24, 39, 0.6);
            border: 1px solid var(--dark-border);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.2s ease;
        }
        .feature-card-dark:hover {
            border-color: rgba(99, 102, 241, 0.25);
            background: rgba(17, 24, 39, 0.85);
        }
        .feature-icon-dark {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            color: #FFFFFF;
        }

        /* Footer */
        .dark-footer {
            background: #0B0F19;
            border-top: 1px solid var(--dark-border);
            padding: 30px 24px;
            color: var(--text-muted);
            font-size: 13px;
        }
        .dark-footer a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.15s ease;
        }
        .dark-footer a:hover {
            color: #A5B4FC;
        }

        @media (max-width: 960px) {
            .portal-grid-dark { grid-template-columns: repeat(2, 1fr); }
            .features-grid-dark { grid-template-columns: 1fr; }
            .hero-title-dark { font-size: 32px; }
            .project-attribution-banner { flex-direction: column; align-items: flex-start; }
        }
        @media (max-width: 640px) {
            .portal-grid-dark { grid-template-columns: 1fr; }
            .dark-navbar { flex-direction: column; gap: 10px; }
        }
    </style>
</head>
<body>

    <!-- Top Navigation Header -->
    <header class="dark-navbar">
        <a href="<?php echo BASE_URL; ?>index.php" class="dark-brand">
            <div class="dark-logo">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                    <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                </svg>
            </div>
            <div>
                <div style="font-size: 15px; font-weight: 800; letter-spacing: -0.01em; color: #FFFFFF;">Faculty Management System</div>
                <div style="font-size: 11px; color: #94A3B8; font-weight: 500;">Academic Governance &amp; Faculty Operations</div>
            </div>
        </a>

        <!-- Credits Badge in Navbar -->
        <div class="credits-pill-top">
            <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: #10B981;"></span>
            <span>By <strong>Saniya Momin (124)</strong> &amp; <strong>Tasmiya Shaikh (123)</strong> &bull; Mentor: <strong>Prof. Mahwish Momin</strong></span>
        </div>

        <div style="display: flex; align-items: center; gap: 10px;">
            <?php if ($currentUser): ?>
                <div style="display: flex; align-items: center; gap: 10px; background: rgba(99, 102, 241, 0.15); border: 1px solid rgba(99, 102, 241, 0.3); padding: 5px 12px; border-radius: 8px;">
                    <span style="font-size: 12.5px; color: #C7D2FE;">Signed in as <strong style="color:#FFF;"><?php echo escape($currentUser['name']); ?></strong></span>
                    <a href="<?php echo BASE_URL . $currentUser['dashboard']; ?>" style="font-size: 12.5px; font-weight: 700; color: #818CF8; text-decoration: underline;">Dashboard &rarr;</a>
                    <a href="<?php echo BASE_URL; ?>logout.php" style="font-size: 11.5px; color: #F87171; font-weight: 600; margin-left: 4px;">Sign Out</a>
                </div>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>login.php" style="color: #CBD5E1; font-size: 13.5px; font-weight: 600; padding: 7px 14px; border-radius: 8px; text-decoration: none;">
                    Sign In
                </a>
                <a href="<?php echo BASE_URL; ?>register.php" style="background: linear-gradient(135deg, #6366F1, #4F46E5); color: #FFFFFF; font-size: 13.5px; font-weight: 700; padding: 7px 16px; border-radius: 8px; text-decoration: none; box-shadow: 0 2px 10px rgba(99, 102, 241, 0.4);">
                    Register Account
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Content -->
    <main style="flex: 1;">
        <!-- Hero Section -->
        <section class="dark-hero">
            <div class="hero-badge-dark">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                ACADEMIC YEAR 2025–2026 &bull; INSTITUTIONAL PLATFORM
            </div>

            <h1 class="hero-title-dark">
                Centralized <span class="hero-title-gradient">Faculty Management</span> Portal
            </h1>

            <p class="hero-desc-dark">
                A unified, high-performance platform designed for academic institutions to manage 
                faculty profiles, synchronize weekly lecture timetables, record daily attendance, 
                and process departmental leave applications with instant auto-approval.
            </p>

            <!-- Project Authors & Academic Guide Card -->
            <div class="project-attribution-banner">
                <div class="att-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6366F1" stroke-width="2.2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <div>
                        <div class="att-role">Lead Developer</div>
                        <div class="att-name">Saniya Momin <span class="att-roll">Roll No: 124</span></div>
                    </div>
                </div>

                <div class="att-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#06B6D4" stroke-width="2.2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <div>
                        <div class="att-role">Co-Developer</div>
                        <div class="att-name">Tasmiya Shaikh <span class="att-roll">Roll No: 123</span></div>
                    </div>
                </div>

                <div class="att-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2.2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <div>
                        <div class="att-role">Project Mentor &amp; Guide</div>
                        <div class="att-name" style="color: #6EE7B7;">Assistant Professor Mahwish Momin</div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="hero-actions">
                <a href="<?php echo BASE_URL; ?>login.php" class="btn-action-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/>
                    </svg>
                    Sign In to Portal
                </a>
                <a href="<?php echo BASE_URL; ?>register.php" class="btn-action-secondary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7.5" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/>
                    </svg>
                    Create Account
                </a>
            </div>

            <div class="notice-pill">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                <span><strong>Instant Access:</strong> All usernames below are configured with 1-click access. You can also sign in with any custom username!</span>
            </div>
        </section>

        <!-- 6 Institutional Portals with AI Bot Avatars & Named Faculty -->
        <section class="portal-grid-dark">
            
            <!-- 1. Administrator -->
            <div class="dark-portal-card" style="border-top: 3px solid #E11D48;">
                <div class="portal-header-row">
                    <div class="ai-bot-avatar" style="border-color: rgba(225, 29, 72, 0.4);">
                        <img src="<?php echo escape(getAIBotAvatarUrl('Administrator')); ?>" alt="Admin AI Bot">
                    </div>
                    <div>
                        <span class="portal-tag tag-admin">System Admin</span>
                        <h3 class="portal-card-name">Administrator</h3>
                    </div>
                </div>
                <p class="portal-card-desc">
                    Executive campus control: manage faculty roster, oversee timetable allocations, inspect punch attendance, and approve leaves.
                </p>
                <div class="portal-login-box">
                    <span>User: <code>admin</code></span>
                    <span>Pass: <code>password123</code></span>
                </div>
                <form action="index.php" method="POST" style="margin-top: auto;">
                    <input type="hidden" name="direct_login" value="admin">
                    <button type="submit" class="btn-enter-portal btn-admin">
                        Login as Admin &rarr;
                    </button>
                </form>
            </div>

            <!-- 2. Affan Sir -->
            <div class="dark-portal-card" style="border-top: 3px solid #6366F1;">
                <div class="portal-header-row">
                    <div class="ai-bot-avatar" style="border-color: rgba(99, 102, 241, 0.4);">
                        <img src="<?php echo escape(getAIBotAvatarUrl('AffanSir')); ?>" alt="Affan Sir AI Bot">
                    </div>
                    <div>
                        <span class="portal-tag tag-cs">Computer Science</span>
                        <h3 class="portal-card-name">Affan Sir</h3>
                    </div>
                </div>
                <p class="portal-card-desc">
                    Head of Department &amp; Professor. Access faculty timetable matrix, submit official leave slips, and check teaching allocations.
                </p>
                <div class="portal-login-box">
                    <span>User: <code>affan</code></span>
                    <span>Pass: <code>password123</code></span>
                </div>
                <form action="index.php" method="POST" style="margin-top: auto;">
                    <input type="hidden" name="direct_login" value="affan">
                    <button type="submit" class="btn-enter-portal btn-affan">
                        Login as Affan Sir &rarr;
                    </button>
                </form>
            </div>

            <!-- 3. Shagufta -->
            <div class="dark-portal-card" style="border-top: 3px solid #0284C7;">
                <div class="portal-header-row">
                    <div class="ai-bot-avatar" style="border-color: rgba(2, 132, 199, 0.4);">
                        <img src="<?php echo escape(getAIBotAvatarUrl('Shagufta')); ?>" alt="Shagufta AI Bot">
                    </div>
                    <div>
                        <span class="portal-tag tag-it">Information Tech</span>
                        <h3 class="portal-card-name">Shagufta</h3>
                    </div>
                </div>
                <p class="portal-card-desc">
                    Associate Professor in IT. Check classroom lecture schedules, verify daily attendance status, and manage subject syllabus tracking.
                </p>
                <div class="portal-login-box">
                    <span>User: <code>shagufta</code></span>
                    <span>Pass: <code>password123</code></span>
                </div>
                <form action="index.php" method="POST" style="margin-top: auto;">
                    <input type="hidden" name="direct_login" value="shagufta">
                    <button type="submit" class="btn-enter-portal btn-shagufta">
                        Login as Shagufta &rarr;
                    </button>
                </form>
            </div>

            <!-- 4. Ummul -->
            <div class="dark-portal-card" style="border-top: 3px solid #0D9488;">
                <div class="portal-header-row">
                    <div class="ai-bot-avatar" style="border-color: rgba(13, 148, 136, 0.4);">
                        <img src="<?php echo escape(getAIBotAvatarUrl('Ummul')); ?>" alt="Ummul AI Bot">
                    </div>
                    <div>
                        <span class="portal-tag tag-cs">CS &amp; Artificial Intelligence</span>
                        <h3 class="portal-card-name">Ummul</h3>
                    </div>
                </div>
                <p class="portal-card-desc">
                    Assistant Professor in Computer Science &amp; AI. Review lab slots, monitor student coursework, and request duty leaves with auto-approval.
                </p>
                <div class="portal-login-box">
                    <span>User: <code>ummul</code></span>
                    <span>Pass: <code>password123</code></span>
                </div>
                <form action="index.php" method="POST" style="margin-top: auto;">
                    <input type="hidden" name="direct_login" value="ummul">
                    <button type="submit" class="btn-enter-portal btn-ummul">
                        Login as Ummul &rarr;
                    </button>
                </form>
            </div>

            <!-- 5. Sofia -->
            <div class="dark-portal-card" style="border-top: 3px solid #8B5CF6;">
                <div class="portal-header-row">
                    <div class="ai-bot-avatar" style="border-color: rgba(139, 92, 246, 0.4);">
                        <img src="<?php echo escape(getAIBotAvatarUrl('Sofia')); ?>" alt="Sofia AI Bot">
                    </div>
                    <div>
                        <span class="portal-tag tag-ece">Electronics &amp; Embedded</span>
                        <h3 class="portal-card-name">Sofia</h3>
                    </div>
                </div>
                <p class="portal-card-desc">
                    Assistant Professor in Electronics Engineering. Track weekly academic hours, view allocated subjects, and update profile credentials.
                </p>
                <div class="portal-login-box">
                    <span>User: <code>sofia</code></span>
                    <span>Pass: <code>password123</code></span>
                </div>
                <form action="index.php" method="POST" style="margin-top: auto;">
                    <input type="hidden" name="direct_login" value="sofia">
                    <button type="submit" class="btn-enter-portal btn-sofia">
                        Login as Sofia &rarr;
                    </button>
                </form>
            </div>

            <!-- 6. Assistant Professor Mahwish Momin -->
            <div class="dark-portal-card" style="border-top: 3px solid #10B981;">
                <div class="portal-header-row">
                    <div class="ai-bot-avatar" style="border-color: rgba(16, 185, 129, 0.4);">
                        <img src="<?php echo escape(getAIBotAvatarUrl('MahwishMomin')); ?>" alt="Prof. Mahwish Momin AI Bot">
                    </div>
                    <div>
                        <span class="portal-tag tag-guide">Project Mentor &amp; Guide</span>
                        <h3 class="portal-card-name">Mahwish Momin</h3>
                    </div>
                </div>
                <p class="portal-card-desc">
                    Assistant Professor &amp; Project Mentor. Supervising FMS development by Saniya Momin &amp; Tasmiya Shaikh with academic evaluation.
                </p>
                <div class="portal-login-box">
                    <span>User: <code>mahwish</code></span>
                    <span>Pass: <code>password123</code></span>
                </div>
                <form action="index.php" method="POST" style="margin-top: auto;">
                    <input type="hidden" name="direct_login" value="mahwish">
                    <button type="submit" class="btn-enter-portal btn-mahwish">
                        Login as Prof. Mahwish &rarr;
                    </button>
                </form>
            </div>

        </section>

        <!-- Core Academic Modules -->
        <section class="dark-features-wrap">
            <div style="text-align: center; margin-bottom: 28px;">
                <h2 style="font-size: 24px; font-weight: 800; color: #FFFFFF; margin-bottom: 6px;">Institutional Capabilities</h2>
                <p style="font-size: 14px; color: var(--text-secondary); margin: 0;">Comprehensive operational modules supporting academic excellence</p>
            </div>

            <div class="features-grid-dark">
                <div class="feature-card-dark">
                    <div class="feature-icon-dark" style="background: linear-gradient(135deg, #6366F1, #4F46E5);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
                    </div>
                    <h4 style="font-size: 15px; font-weight: 700; color: #FFFFFF; margin: 0 0 6px;">Faculty Directory (32 Roster)</h4>
                    <p style="font-size: 13px; color: var(--text-secondary); line-height: 1.5; margin: 0;">
                        Detailed records featuring designations, departments, employee IDs, qualifications, joining dates, and AI bot avatars.
                    </p>
                </div>

                <div class="feature-card-dark">
                    <div class="feature-icon-dark" style="background: linear-gradient(135deg, #0284C7, #0369A1);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <h4 style="font-size: 15px; font-weight: 700; color: #FFFFFF; margin: 0 0 6px;">6-Day Timetable Matrix</h4>
                    <p style="font-size: 13px; color: var(--text-secondary); line-height: 1.5; margin: 0;">
                        Interactive schedules from Monday to Saturday, 09:00 AM to 04:00 PM with classroom tags, subjects, and break slots.
                    </p>
                </div>

                <div class="feature-card-dark">
                    <div class="feature-icon-dark" style="background: linear-gradient(135deg, #10B981, #059669);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <h4 style="font-size: 15px; font-weight: 700; color: #FFFFFF; margin: 0 0 6px;">Live Attendance &amp; Punch</h4>
                    <p style="font-size: 13px; color: var(--text-secondary); line-height: 1.5; margin: 0;">
                        Real-time daily presence tracking with check-in timestamps, status breakdown, and automated daily rosters.
                    </p>
                </div>

                <div class="feature-card-dark">
                    <div class="feature-icon-dark" style="background: linear-gradient(135deg, #F59E0B, #D97706);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                    <h4 style="font-size: 15px; font-weight: 700; color: #FFFFFF; margin: 0 0 6px;">Instant Leave Approval</h4>
                    <p style="font-size: 13px; color: var(--text-secondary); line-height: 1.5; margin: 0;">
                        Full workflow for Casual, Sick, and Earned leaves with zero token errors and instant auto-sanctioning.
                    </p>
                </div>

                <div class="feature-card-dark">
                    <div class="feature-icon-dark" style="background: linear-gradient(135deg, #8B5CF6, #7C3AED);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    </div>
                    <h4 style="font-size: 15px; font-weight: 700; color: #FFFFFF; margin: 0 0 6px;">6 Academic Departments</h4>
                    <p style="font-size: 13px; color: var(--text-secondary); line-height: 1.5; margin: 0;">
                        Organized hierarchy spanning Computer Science, Information Tech, Management, Commerce, Mathematics, and Electronics.
                    </p>
                </div>

                <div class="feature-card-dark">
                    <div class="feature-icon-dark" style="background: linear-gradient(135deg, #EC4899, #DB2777);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </div>
                    <h4 style="font-size: 15px; font-weight: 700; color: #FFFFFF; margin: 0 0 6px;">Vercel-Ready &amp; Zero Errors</h4>
                    <p style="font-size: 13px; color: var(--text-secondary); line-height: 1.5; margin: 0;">
                        Clean architecture, verified database pipelines, robust session handling, and fully prepared for one-click deployment.
                    </p>
                </div>
            </div>
        </section>
    </main>

    <!-- Dark Footer with Prominent Project Credits -->
    <footer class="dark-footer">
        <div style="max-width: 1120px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <div style="font-weight: 700; color: #FFFFFF; margin-bottom: 4px;">Faculty Management System (FMS)</div>
                <div style="font-size: 12px; color: #64748B;">Academic Portal &bull; Academic Year 2025–2026</div>
            </div>

            <!-- Student Developer & Guide Attribution -->
            <div style="text-align: right;">
                <div style="font-size: 12.5px; color: #E2E8F0; margin-bottom: 2px;">
                    Project By: <strong style="color: #818CF8;">Saniya Momin (Roll No: 124)</strong> &amp; <strong style="color: #38BDF8;">Tasmiya Shaikh (Roll No: 123)</strong>
                </div>
                <div style="font-size: 12px; color: #94A3B8;">
                    Project Mentor: <strong style="color: #34D399;">Assistant Professor Mahwish Momin</strong>
                </div>
            </div>
        </div>

        <div style="max-width: 1120px; margin: 20px auto 0; padding-top: 16px; border-top: 1px solid rgba(255, 255, 255, 0.05); display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #64748B;">
            <div>&copy; <?php echo date('Y'); ?> Faculty Management System. All academic rights reserved.</div>
            <div style="display: flex; gap: 16px;">
                <a href="<?php echo BASE_URL; ?>login.php">Sign In</a>
                <a href="<?php echo BASE_URL; ?>register.php">Register</a>
                <a href="<?php echo BASE_URL; ?>admin/dashboard.php">Admin Portal</a>
                <a href="<?php echo BASE_URL; ?>faculty/dashboard.php">Faculty Portal</a>
            </div>
        </div>
    </footer>

</body>
</html>
