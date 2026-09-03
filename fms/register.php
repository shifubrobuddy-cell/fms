<?php
/**
 * Faculty Management System (FMS)
 * User Registration / Sign Up Portal
 * Bulletproof registration flow with instant authenticated login and quick test fillers
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$loggedInUser = null;
if (isLoggedIn()) {
    $loggedInUser = [
        'name' => $_SESSION['full_name'] ?? $_SESSION['username'],
        'role' => $_SESSION['user_role'] ?? 'faculty',
        'dashboard' => ($_SESSION['user_role'] === 'admin') ? 'admin/dashboard.php' : 'faculty/dashboard.php'
    ];
}

$db = getDB();
$error = '';
$success = '';

$role = 'faculty';
$fullName = '';
$username = '';
$email = '';
$phone = '';
$departmentId = 0;
$designation = 'Assistant Professor';
$qualification = 'Ph.D. / Post Graduate';

// Fetch active departments for selection
$departments = [];
try {
    $departments = $db->query("SELECT id, dept_code, dept_name FROM departments ORDER BY id ASC")->fetchAll();
    if (!empty($departments)) {
        $departmentId = (int)$departments[0]['id'];
    }
} catch (Exception $e) {
    $departments = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = trim($_POST['role'] ?? 'faculty');
    $fullName = trim($_POST['full_name'] ?? '');
    
    // Sanitize username: lowercase, trim, replace spaces with underscores
    $rawUsername = trim($_POST['username'] ?? '');
    $username = strtolower(preg_replace('/\s+/', '_', $rawUsername));
    
    $email = trim(strtolower($_POST['email'] ?? ''));
    if (empty($email) && !empty($username)) {
        $email = $username . '@fms.local';
    }
    
    $phone = trim($_POST['phone'] ?? '9876500000');
    $departmentId = (int)($_POST['department_id'] ?? 0);
    $designation = trim($_POST['designation'] ?? 'Assistant Professor');
    $qualification = trim($_POST['qualification'] ?? 'Post Graduate');
    $password = $_POST['password'] ?? 'password123';
    $confirmPassword = $_POST['confirm_password'] ?? $password;
    if (empty($confirmPassword)) {
        $confirmPassword = $password;
    }

    if (empty($fullName)) {
        $fullName = !empty($username) ? ucfirst($username) . " User" : "New Faculty Member";
    }
    if (empty($username)) {
        $username = 'faculty_' . rand(100, 999);
    }
    if (empty($password)) {
        $password = 'password123';
    }

    if (true) {
        try {
            // Check username uniqueness (if taken, append a random number to prevent frustration)
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE LOWER(username) = ?");
            $checkStmt->execute([$username]);
            if ((int)$checkStmt->fetchColumn() > 0) {
                // If username is taken, auto-append random suffix so signup doesn't block the user
                $username = $username . '_' . rand(10, 99);
            }

            // Check email uniqueness
            if (!empty($email)) {
                $emailCheck = $db->prepare("SELECT COUNT(*) FROM faculty WHERE LOWER(email) = ?");
                $emailCheck->execute([$email]);
                if ((int)$emailCheck->fetchColumn() > 0) {
                    $email = str_replace('@', '_' . rand(10, 99) . '@', $email);
                }
            }

            $db->beginTransaction();

            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $assignedRole = ($role === 'admin') ? 'admin' : 'faculty';

            // Insert into users
            $userStmt = $db->prepare("INSERT INTO users (username, password, role, status) VALUES (?, ?, ?, 'active')");
            $userStmt->execute([$username, $hashedPassword, $assignedRole]);
            $newUserId = (int)$db->lastInsertId();

            if ($assignedRole === 'faculty') {
                // Generate new Employee ID: FMS + sequence
                $countStmt = $db->query("SELECT COUNT(*) FROM faculty");
                $nextNum = (int)$countStmt->fetchColumn() + 1;
                $empId = 'FMS' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

                if ($departmentId <= 0 && !empty($departments)) {
                    $departmentId = (int)$departments[0]['id'];
                }

                $avatar = getAIBotAvatarUrl($fullName);
                $todayDate = date('Y-m-d');

                $facStmt = $db->prepare("
                    INSERT INTO faculty (user_id, department_id, emp_id, full_name, email, phone, designation, qualification, joining_date, photo)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $facStmt->execute([
                    $newUserId,
                    $departmentId,
                    $empId,
                    $fullName,
                    $email,
                    $phone,
                    $designation,
                    $qualification,
                    $todayDate,
                    $avatar
                ]);
            }

            $db->commit();

            // Establish fresh session for the newly registered user
            session_regenerate_id(true);
            $_SESSION['user_id'] = $newUserId;
            $_SESSION['username'] = $username;
            $_SESSION['user_role'] = $assignedRole;

            if ($assignedRole === 'faculty') {
                $facData = $db->query("SELECT f.*, d.dept_name FROM faculty f LEFT JOIN departments d ON f.department_id = d.id WHERE f.user_id = $newUserId LIMIT 1")->fetch();
                if ($facData) {
                    $_SESSION['faculty_id'] = (int)$facData['id'];
                    $_SESSION['full_name'] = $facData['full_name'];
                    $_SESSION['emp_id'] = $facData['emp_id'];
                    $_SESSION['designation'] = $facData['designation'];
                    $_SESSION['department_id'] = $facData['department_id'];
                    $_SESSION['dept_name'] = $facData['dept_name'];
                    $_SESSION['photo'] = $facData['photo'] ?: 'default_avatar.png';
                } else {
                    $_SESSION['faculty_id'] = null;
                    $_SESSION['full_name'] = $fullName;
                }
                setFlashMessage('success', "Account created successfully! Welcome to the Faculty Portal, {$fullName}.");
                redirect('faculty/dashboard.php');
            } else {
                $_SESSION['faculty_id'] = null;
                $_SESSION['full_name'] = $fullName ?: 'Administrator';
                $_SESSION['designation'] = 'System Administrator';
                $_SESSION['photo'] = 'default_avatar.png';
                setFlashMessage('success', "Administrator account created successfully! Welcome, {$fullName}.");
                redirect('admin/dashboard.php');
            }

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $error = 'Registration could not be completed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account &bull; <?php echo escape(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .role-selector-tab {
            flex: 1;
            padding: 10px;
            text-align: center;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 700;
            cursor: pointer;
            border: 1px solid #E2E8F0;
            background: #F8FAFC;
            color: #64748B;
            transition: all 0.15s ease;
        }
        .role-selector-tab.active {
            background: #EEF2FF;
            color: #4F46E5;
            border-color: #6366F1;
        }
        .btn-sample-fill {
            background: #F1F5F9;
            border: 1px solid #CBD5E1;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #334155;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .btn-sample-fill:hover {
            background: #E2E8F0;
            color: #0F172A;
        }
    </style>
</head>
<body class="auth-body">
    <div class="auth-container" style="max-width: 540px; margin: 30px auto;">
        <!-- Top Navigation to Home -->
        <div style="text-align: center; margin-bottom: 18px;">
            <a href="<?php echo BASE_URL; ?>index.php" style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; color: #64748B; font-weight: 600; text-decoration: none;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Back to System Introduction
            </a>
        </div>

        <!-- Brand Header -->
        <div class="auth-brand">
            <div class="auth-logo-badge">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                    <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                </svg>
            </div>
            <h1 class="auth-title">Create an Account</h1>
            <p class="auth-subtitle">Join the Institutional Faculty &amp; Administration Portal</p>
        </div>

        <?php if ($loggedInUser): ?>
            <div style="background: #FEF3C7; border: 1px solid #FDE68A; border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; font-size: 13px; color: #92400E; display: flex; align-items: center; justify-content: space-between;">
                <div>Currently logged in as <strong><?php echo escape($loggedInUser['name']); ?></strong></div>
                <div style="display: flex; gap: 10px;">
                    <a href="<?php echo BASE_URL . $loggedInUser['dashboard']; ?>" style="color: #92400E; font-weight: 700; text-decoration: underline;">Go to Dashboard</a>
                    <a href="<?php echo BASE_URL; ?>logout.php" style="color: #B91C1C; font-weight: 600;">Sign Out</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Registration Card -->
        <div class="auth-card" style="padding: 28px 32px;">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" style="padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; background: #FEE2E2; color: #B91C1C; border: 1px solid #FECACA;">
                    <?php echo escape($error); ?>
                </div>
            <?php endif; ?>

            <!-- Quick 1-Click Fillers -->
            <div style="margin-bottom: 20px; background: #F8FAFC; border: 1px dashed #CBD5E1; border-radius: 8px; padding: 10px 14px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                <span style="font-size: 12px; font-weight: 700; color: #475569;">Quick Fill Form:</span>
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="btn-sample-fill" onclick="fillSample('faculty')">Fill Faculty</button>
                    <button type="button" class="btn-sample-fill" onclick="fillSample('admin')">Fill Admin</button>
                </div>
            </div>

            <form action="register.php" method="POST" id="regForm">
                <!-- Role Selector -->
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" style="margin-bottom: 6px; display: block;">Select Account Role</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="hidden" name="role" id="roleInput" value="<?php echo escape($role); ?>">
                        <div class="role-selector-tab <?php echo ($role === 'faculty') ? 'active' : ''; ?>" id="tabFaculty" onclick="selectRole('faculty')">
                            Faculty Member
                        </div>
                        <div class="role-selector-tab <?php echo ($role === 'admin') ? 'active' : ''; ?>" id="tabAdmin" onclick="selectRole('admin')">
                            Administrator
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="full_name" class="form-label">Full Name *</label>
                    <input 
                        type="text" 
                        name="full_name" 
                        id="full_name" 
                        class="form-control" 
                        style="width: 100%; height: 42px;"
                        placeholder="e.g. Dr. Alex Morgan" 
                        value="<?php echo escape($fullName); ?>" 
                        required
                    >
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label for="username" class="form-label">Username *</label>
                        <input 
                            type="text" 
                            name="username" 
                            id="username" 
                            class="form-control" 
                            style="width: 100%; height: 42px;"
                            placeholder="e.g. alex_morgan" 
                            value="<?php echo escape($username); ?>" 
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input 
                            type="email" 
                            name="email" 
                            id="email" 
                            class="form-control" 
                            style="width: 100%; height: 42px;"
                            placeholder="alex@fms.local" 
                            value="<?php echo escape($email); ?>"
                        >
                    </div>
                </div>

                <!-- Faculty Specific Fields (hidden when admin is picked) -->
                <div id="facultyFieldsSection" style="<?php echo ($role === 'admin') ? 'display: none;' : ''; ?>">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label for="department_id" class="form-label">Department *</label>
                            <select name="department_id" id="department_id" class="form-control" style="width: 100%; height: 42px;">
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?php echo $d['id']; ?>" <?php echo ($departmentId == $d['id']) ? 'selected' : ''; ?>>
                                        <?php echo escape($d['dept_name']); ?> (<?php echo escape($d['dept_code']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="designation" class="form-label">Designation</label>
                            <select name="designation" id="designation" class="form-control" style="width: 100%; height: 42px;">
                                <option value="Assistant Professor">Assistant Professor</option>
                                <option value="Associate Professor">Associate Professor</option>
                                <option value="Professor">Professor</option>
                                <option value="Senior Lecturer">Senior Lecturer</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label for="password" class="form-label">Password *</label>
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            class="form-control" 
                            style="width: 100%; height: 42px;"
                            placeholder="••••••••" 
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password *</label>
                        <input 
                            type="password" 
                            name="confirm_password" 
                            id="confirm_password" 
                            class="form-control" 
                            style="width: 100%; height: 42px;"
                            placeholder="••••••••" 
                            required
                        >
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; height: 46px; font-size: 15px; font-weight: 700; border-radius: 10px; margin-top: 10px; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);">
                    Create Account &amp; Log In
                </button>
            </form>

            <div style="text-align: center; margin-top: 20px; font-size: 13.5px; color: #64748B;">
                Already have an account? 
                <a href="<?php echo BASE_URL; ?>login.php" style="color: #4F46E5; font-weight: 700; text-decoration: none;">
                    Sign In
                </a>
            </div>
        </div>

        <div style="text-align: center; margin-top: 24px; color: #64748B; font-size: 12.5px;">
            &copy; <?php echo date('Y'); ?> <?php echo escape(APP_NAME); ?> &bull; Institutional Academic Portal
        </div>
    </div>

    <script>
        function selectRole(r) {
            document.getElementById('roleInput').value = r;
            const facTab = document.getElementById('tabFaculty');
            const adminTab = document.getElementById('tabAdmin');
            const facSection = document.getElementById('facultyFieldsSection');

            if (r === 'faculty') {
                facTab.classList.add('active');
                adminTab.classList.remove('active');
                facSection.style.display = 'block';
            } else {
                adminTab.classList.add('active');
                facTab.classList.remove('active');
                facSection.style.display = 'none';
            }
        }

        function fillSample(type) {
            const rand = Math.floor(Math.random() * 900) + 100;
            if (type === 'faculty') {
                selectRole('faculty');
                document.getElementById('full_name').value = 'Prof. Test User ' + rand;
                document.getElementById('username').value = 'faculty_test_' + rand;
                document.getElementById('email').value = 'faculty' + rand + '@fms.local';
                document.getElementById('password').value = 'password123';
                document.getElementById('confirm_password').value = 'password123';
            } else {
                selectRole('admin');
                document.getElementById('full_name').value = 'Admin Associate ' + rand;
                document.getElementById('username').value = 'admin_test_' + rand;
                document.getElementById('email').value = 'admin' + rand + '@fms.local';
                document.getElementById('password').value = 'password123';
                document.getElementById('confirm_password').value = 'password123';
            }
        }
    </script>
</body>
</html>
