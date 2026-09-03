<?php
/**
 * Faculty Management System (FMS)
 * User Login Page (Admin & Faculty)
 * Professional Academic Aesthetic with Rich Color Accents
 * Universal Instant Approval: Whatever credentials entered are approved!
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

// If user requests logout
if (isset($_GET['logout'])) {
    logoutUser();
}

// Redirect if already logged in unless switching
if (isLoggedIn() && !isset($_GET['switch'])) {
    if (($_SESSION['user_role'] ?? 'admin') === 'admin') {
        redirect('admin/dashboard.php');
    } else {
        redirect('faculty/dashboard.php');
    }
}

$username = '';

// Handle quick login URL parameter e.g. login.php?quick=admin
if (isset($_GET['quick'])) {
    $target = trim($_GET['quick']);
    $result = attemptLogin($target ?: 'admin', 'password123');
    if ($result['role'] === 'admin') {
        redirect('admin/dashboard.php');
    } else {
        redirect('faculty/dashboard.php');
    }
}

// Handle POST request: ALWAYS APPROVE!
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1-Click Instant Login Button handler
    if (isset($_POST['instant_login'])) {
        $target = trim($_POST['instant_login']);
        $result = attemptLogin($target ?: 'admin', 'password123');
        if ($result['role'] === 'admin') {
            redirect('admin/dashboard.php');
        } else {
            redirect('faculty/dashboard.php');
        }
    }

    $rawUser = trim($_POST['username'] ?? '');
    $rawPass = $_POST['password'] ?? '';
    
    // Whatever the person types: APPROVE IT!
    $result = attemptLogin($rawUser, $rawPass);
    if ($result['role'] === 'faculty') {
        redirect('faculty/dashboard.php');
    } else {
        redirect('admin/dashboard.php');
    }
}

$csrf_token = generateCSRFToken();
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In &bull; <?php echo escape(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="/fms/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body.auth-screen {
            min-height: 100vh;
            background: linear-gradient(135deg, #0F172A 0%, #1E1B4B 40%, #172554 100%);
            background-attachment: fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            position: relative;
            overflow-x: hidden;
        }
        body.auth-screen::before {
            content: '';
            position: absolute;
            top: -100px;
            left: -100px;
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.25) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        body.auth-screen::after {
            content: '';
            position: absolute;
            bottom: -120px;
            right: -120px;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(14, 165, 233, 0.2) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .auth-box {
            width: 100%;
            max-width: 460px;
            position: relative;
            z-index: 10;
        }
        .auth-card-modern {
            background: #FFFFFF;
            border-radius: 18px;
            padding: 34px 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.1);
            border: 1px solid #E2E8F0;
        }
        .approval-notice {
            background: linear-gradient(90deg, #EFF6FF 0%, #F5F3FF 100%);
            border: 1px solid #C7D2FE;
            border-left: 4px solid #6366F1;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
            color: #312E81;
        }
        .instant-card-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
            margin-top: 14px;
        }
        .role-btn-admin {
            background: linear-gradient(135deg, #4F46E5 0%, #4338CA 100%);
            color: #FFFFFF;
            border: none;
            padding: 11px 16px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            font-weight: 700;
            font-size: 13.5px;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
            transition: all 0.15s ease;
            width: 100%;
        }
        .role-btn-admin:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.4);
        }
        .role-btn-demo1 {
            background: linear-gradient(135deg, #0284C7 0%, #0369A1 100%);
            color: #FFFFFF;
            border: none;
            padding: 11px 16px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            font-weight: 700;
            font-size: 13.5px;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
            transition: all 0.15s ease;
            width: 100%;
        }
        .role-btn-demo1:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(2, 132, 199, 0.4);
        }
        .role-btn-demo2 {
            background: linear-gradient(135deg, #0D9488 0%, #0F766E 100%);
            color: #FFFFFF;
            border: none;
            padding: 11px 16px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            font-weight: 700;
            font-size: 13.5px;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
            transition: all 0.15s ease;
            width: 100%;
        }
        .role-btn-demo2:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(13, 148, 136, 0.4);
        }
        .badge-role {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.2);
            letter-spacing: 0.04em;
        }
    </style>
</head>
<body class="auth-screen">
    <div class="auth-box">
        <!-- Top Navigation to Introduction Portal -->
        <div style="text-align: center; margin-bottom: 20px;">
            <a href="<?php echo BASE_URL; ?>index.php" style="display: inline-flex; align-items: center; gap: 8px; font-size: 13.5px; color: #A5B4FC; font-weight: 600; text-decoration: none; padding: 6px 14px; border-radius: 9999px; background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.15);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Institutional Introduction &amp; Portal Gateway
            </a>
        </div>

        <!-- Brand Header with Glowing Indigo Logo -->
        <div style="text-align: center; margin-bottom: 24px;">
            <div style="width: 54px; height: 54px; border-radius: 14px; background: linear-gradient(135deg, #6366F1, #4F46E5); display: inline-flex; align-items: center; justify-content: center; color: #FFFFFF; margin-bottom: 12px; box-shadow: 0 8px 25px rgba(99, 102, 241, 0.5);">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                    <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                </svg>
            </div>
            <h1 style="font-size: 24px; font-weight: 800; color: #FFFFFF; letter-spacing: -0.02em; margin-bottom: 4px;">Faculty Management System</h1>
            <p style="font-size: 13.5px; color: #94A3B8;">Institutional Academic Administration &amp; Faculty Portal</p>
        </div>

        <!-- Main Authentication Card -->
        <div class="auth-card-modern">
            <!-- Universal Approval Banner Notice -->
            <div class="approval-notice">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#4F46E5" stroke-width="2.5" style="flex-shrink: 0;">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <div style="line-height: 1.4;">
                    <strong>Instant Approval Active:</strong> Type anything you want! Whatever username or password you enter will be approved and logged in.
                </div>
            </div>

            <!-- Login Form -->
            <form action="login.php" method="POST" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo escape($csrf_token); ?>">

                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="username" class="form-label" style="font-weight: 700; color: #0F172A;">Username</label>
                    <input 
                        type="text" 
                        name="username" 
                        id="username" 
                        class="form-control" 
                        style="width: 100%; height: 44px; border: 1.5px solid #CBD5E1; border-radius: 8px; padding: 0 14px; font-size: 14px; background: #F8FAFC;"
                        placeholder="e.g. admin or any name you choose" 
                        value="<?php echo escape($username); ?>" 
                        autofocus
                    >
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <label for="password" class="form-label" style="font-weight: 700; color: #0F172A; margin-bottom: 0;">Password</label>
                        <span style="font-size: 12px; color: #64748B; font-weight: 500;">(Any password accepted)</span>
                    </div>
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        class="form-control" 
                        style="width: 100%; height: 44px; border: 1.5px solid #CBD5E1; border-radius: 8px; padding: 0 14px; font-size: 14px; background: #F8FAFC;"
                        placeholder="••••••••" 
                    >
                </div>

                <button type="submit" class="btn" style="width: 100%; height: 46px; font-size: 15px; font-weight: 800; border-radius: 10px; background: #4F46E5; color: #FFFFFF; border: none; box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35); cursor: pointer;" id="submitBtn">
                    Sign In to Portal &rarr;
                </button>
            </form>

            <!-- Sign Up Alternative Link -->
            <div style="text-align: center; margin-top: 18px; font-size: 13.5px; color: #64748B;">
                Need to create a specific profile? 
                <a href="<?php echo BASE_URL; ?>register.php" style="color: #4F46E5; font-weight: 700; text-decoration: none;">
                    Create Account
                </a>
            </div>

            <!-- Instant 1-Click Role Direct Logins with Rich Vibrant Colors -->
            <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #E2E8F0;">
                <div style="font-size: 11.5px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#4F46E5" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    1-Click Direct Access (Instant Entry)
                </div>

                <div class="instant-card-grid">
                    <!-- Admin Button -->
                    <form action="login.php" method="POST" style="margin: 0;">
                        <input type="hidden" name="instant_login" value="admin">
                        <button type="submit" class="role-btn-admin">
                            <span style="display: flex; align-items: center; gap: 8px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                Administrator (Executive Admin)
                            </span>
                            <span class="badge-role">Admin</span>
                        </button>
                    </form>

                    <!-- Affan Sir Button -->
                    <form action="login.php" method="POST" style="margin: 0;">
                        <input type="hidden" name="instant_login" value="affan">
                        <button type="submit" class="role-btn-demo1" style="background: linear-gradient(135deg, #4F46E5, #3730A3);">
                            <span style="display: flex; align-items: center; gap: 8px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="7" r="4"/><path d="M5.5 21v-2a6.5 6.5 0 0 1 13 0v2"/></svg>
                                Affan Sir (Head &bull; Computer Science)
                            </span>
                            <span class="badge-role">Professor</span>
                        </button>
                    </form>

                    <!-- Shagufta Button -->
                    <form action="login.php" method="POST" style="margin: 0;">
                        <input type="hidden" name="instant_login" value="shagufta">
                        <button type="submit" class="role-btn-demo2">
                            <span style="display: flex; align-items: center; gap: 8px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="7" r="4"/><path d="M5.5 21v-2a6.5 6.5 0 0 1 13 0v2"/></svg>
                                Shagufta (Associate Professor &bull; IT)
                            </span>
                            <span class="badge-role">Faculty</span>
                        </button>
                    </form>

                    <!-- Ummul Button -->
                    <form action="login.php" method="POST" style="margin: 0;">
                        <input type="hidden" name="instant_login" value="ummul">
                        <button type="submit" class="role-btn-demo2" style="background: linear-gradient(135deg, #0D9488, #0F766E);">
                            <span style="display: flex; align-items: center; gap: 8px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="7" r="4"/><path d="M5.5 21v-2a6.5 6.5 0 0 1 13 0v2"/></svg>
                                Ummul (Assistant Professor &bull; CS &amp; AI)
                            </span>
                            <span class="badge-role">Faculty</span>
                        </button>
                    </form>

                    <!-- Sofia Button -->
                    <form action="login.php" method="POST" style="margin: 0;">
                        <input type="hidden" name="instant_login" value="sofia">
                        <button type="submit" class="role-btn-demo1" style="background: linear-gradient(135deg, #7C3AED, #6D28D9);">
                            <span style="display: flex; align-items: center; gap: 8px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="7" r="4"/><path d="M5.5 21v-2a6.5 6.5 0 0 1 13 0v2"/></svg>
                                Sofia (Assistant Professor &bull; ECE)
                            </span>
                            <span class="badge-role">Faculty</span>
                        </button>
                    </form>

                    <!-- Mahwish Momin Button -->
                    <form action="login.php" method="POST" style="margin: 0;">
                        <input type="hidden" name="instant_login" value="mahwish">
                        <button type="submit" class="role-btn-demo2" style="background: linear-gradient(135deg, #059669, #047857);">
                            <span style="display: flex; align-items: center; gap: 8px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                Assistant Professor Mahwish Momin (Guide)
                            </span>
                            <span class="badge-role">Guide</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Academic Credits Footer Box -->
        <div style="text-align: center; margin-top: 20px; background: rgba(255, 255, 255, 0.06); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 12px; padding: 14px 20px; color: #CBD5E1; font-size: 12.5px;">
            <div style="margin-bottom: 4px;">
                Built by: <strong style="color: #A5B4FC;">Saniya Momin (Roll No: 124)</strong> &amp; <strong style="color: #7DD3FC;">Tasmiya Shaikh (Roll No: 123)</strong>
            </div>
            <div>
                Project Guide: <strong style="color: #6EE7B7;">Assistant Professor Mahwish Momin</strong>
            </div>
        </div>

        <div style="text-align: center; margin-top: 14px; color: #64748B; font-size: 11.5px;">
            &copy; <?php echo date('Y'); ?> <?php echo escape(APP_NAME); ?> &bull; Institutional Academic Portal
        </div>
    </div>
</body>
</html>
