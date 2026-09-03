<?php
/**
 * Faculty Management System (FMS)
 * Faculty: Profile Dossier & Account Security Settings
 */

require_once __DIR__ . '/../includes/faculty-auth.php';

$pageTitle = 'My Faculty Profile';
$activeMenu = 'profile';
$db = getDB();

$facultyId = (int)$_SESSION['faculty_id'];
$userId = (int)$_SESSION['user_id'];

// Fetch latest profile
$faculty = [];
try {
    $stmt = $db->prepare("
        SELECT f.*, d.dept_code, d.dept_name, u.username
        FROM faculty f
        JOIN departments d ON f.department_id = d.id
        JOIN users u ON f.user_id = u.id
        WHERE f.id = ?
    ");
    $stmt->execute([$facultyId]);
    $faculty = $stmt->fetch();

    if (!$faculty) {
        die("Faculty profile not found.");
    }
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

$contactErrors = [];
$contactSuccess = false;
$passwordErrors = [];
$passwordSuccess = false;

// Handle Contact Update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'update_contact') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        $contactErrors[] = 'Invalid security session token.';
    }

    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $contactErrors[] = 'Please enter a valid institutional email address.';
    }

    if (empty($phone)) {
        $contactErrors[] = 'Phone number is required.';
    }

    // Check email uniqueness excluding current faculty
    if (empty($contactErrors)) {
        try {
            $chk = $db->prepare("SELECT COUNT(*) FROM faculty WHERE email = ? AND id != ?");
            $chk->execute([$email, $facultyId]);
            if ($chk->fetchColumn() > 0) {
                $contactErrors[] = 'This email address is already registered to another faculty member.';
            }
        } catch (Exception $e) {
            $contactErrors[] = 'Email verification error.';
        }
    }

    if (empty($contactErrors)) {
        try {
            $upd = $db->prepare("UPDATE faculty SET email = ?, phone = ? WHERE id = ?");
            $upd->execute([$email, $phone, $facultyId]);

            // Refresh local variable
            $faculty['email'] = $email;
            $faculty['phone'] = $phone;

            setFlashMessage('success', 'Contact details updated successfully.');
            header('Location: ' . BASE_URL . 'faculty/profile.php');
            exit;
        } catch (Exception $e) {
            error_log("Update contact error: " . $e->getMessage());
            $contactErrors[] = 'Failed to update contact info.';
        }
    }
}

// Handle Password Change POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'change_password') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        $passwordErrors[] = 'Invalid security session token.';
    }

    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (empty($currentPass)) {
        $passwordErrors[] = 'Current password is required.';
    }
    if (strlen($newPass) < 6) {
        $passwordErrors[] = 'New password must be at least 6 characters long.';
    }
    if ($newPass !== $confirmPass) {
        $passwordErrors[] = 'New password and confirmation do not match.';
    }

    if (empty($passwordErrors)) {
        try {
            // Verify current password
            $uStmt = $db->prepare("SELECT password FROM users WHERE id = ?");
            $uStmt->execute([$userId]);
            $hash = $uStmt->fetchColumn();

            if (!$hash || !password_verify($currentPass, $hash)) {
                $passwordErrors[] = 'Current password entered is incorrect.';
            } else {
                $newHash = password_hash($newPass, PASSWORD_BCRYPT);
                $pUpd = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $pUpd->execute([$newHash, $userId]);

                setFlashMessage('success', 'Security password successfully changed.');
                header('Location: ' . BASE_URL . 'faculty/profile.php');
                exit;
            }
        } catch (Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            $passwordErrors[] = 'Failed to update password.';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="content-header">
    <div>
        <h2 style="font-size: 20px; font-weight: 700; color: var(--text); margin: 0 0 4px;">Faculty Profile &amp; Settings</h2>
        <p style="color: var(--text-muted); font-size: 14px; margin: 0;">View employment dossier and manage your personal credentials.</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: minmax(280px, 340px) 1fr; gap: 24px; align-items: start;">
    <!-- Profile Card (Read Only Employment Info) -->
    <div class="card">
        <div class="card-body" style="text-align: center; padding: 28px 20px;">
            <div style="width: 90px; height: 90px; margin: 0 auto 16px; border-radius: 50%; background: var(--primary-light); display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 700; color: #fff; border: 3px solid var(--border); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                <?php echo strtoupper(substr(escape($faculty['full_name']), 0, 2)); ?>
            </div>

            <h3 style="margin: 0 0 4px; font-size: 18px; font-weight: 700; color: var(--text);">
                <?php echo escape($faculty['full_name']); ?>
            </h3>
            <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 8px;">
                <?php echo escape($faculty['designation']); ?>
            </div>
            <div>
                <span class="badge badge-info" style="font-size: 12px; padding: 4px 10px;">
                    <?php echo escape($faculty['dept_name']); ?> (<?php echo escape($faculty['dept_code']); ?>)
                </span>
            </div>

            <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border); text-align: left; font-size: 13px; display: flex; flex-direction: column; gap: 12px;">
                <div>
                    <span style="color: var(--text-muted); display: block; font-size: 11px; text-transform: uppercase;">Employee ID</span>
                    <strong style="font-family: monospace; font-size: 14px;"><?php echo escape($faculty['emp_id']); ?></strong>
                </div>

                <div>
                    <span style="color: var(--text-muted); display: block; font-size: 11px; text-transform: uppercase;">Portal Username</span>
                    <strong><?php echo escape($faculty['username']); ?></strong>
                </div>

                <div>
                    <span style="color: var(--text-muted); display: block; font-size: 11px; text-transform: uppercase;">Highest Qualification</span>
                    <strong><?php echo escape($faculty['qualification'] ?? 'N/A'); ?></strong>
                </div>

                <div>
                    <span style="color: var(--text-muted); display: block; font-size: 11px; text-transform: uppercase;">Date of Joining</span>
                    <strong><?php echo date('F j, Y', strtotime($faculty['joining_date'])); ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Forms Column -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
        <!-- Contact Information Form -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Personal Contact Information</h3>
                <span style="font-size: 12px; color: var(--text-muted);">Direct phone and institutional email</span>
            </div>
            <div class="card-body">
                <?php if (!empty($contactErrors)): ?>
                    <div class="alert alert-danger" style="margin-bottom: 16px;">
                        <ul style="margin: 0; padding-left: 18px;">
                            <?php foreach ($contactErrors as $err): ?>
                                <li><?php echo escape($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="action_type" value="update_contact">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address <span style="color: var(--danger);">*</span></label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   class="form-control" 
                                   value="<?php echo escape($faculty['email']); ?>" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="phone" class="form-label">Phone Number <span style="color: var(--danger);">*</span></label>
                            <input type="tel" 
                                   id="phone" 
                                   name="phone" 
                                   class="form-control" 
                                   value="<?php echo escape($faculty['phone']); ?>" 
                                   required>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; margin-top: 16px;">
                        <button type="submit" class="btn btn-primary">
                            Update Contact Information
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Security / Password Change Form -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Change Account Password</h3>
                <span style="font-size: 12px; color: var(--text-muted);">Must be at least 6 characters</span>
            </div>
            <div class="card-body">
                <?php if (!empty($passwordErrors)): ?>
                    <div class="alert alert-danger" style="margin-bottom: 16px;">
                        <ul style="margin: 0; padding-left: 18px;">
                            <?php foreach ($passwordErrors as $err): ?>
                                <li><?php echo escape($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="action_type" value="change_password">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="form-group">
                        <label for="current_password" class="form-label">Current Password <span style="color: var(--danger);">*</span></label>
                        <input type="password" 
                               id="current_password" 
                               name="current_password" 
                               class="form-control" 
                               required 
                               autocomplete="current-password">
                    </div>

                    <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                        <div class="form-group">
                            <label for="new_password" class="form-label">New Password <span style="color: var(--danger);">*</span></label>
                            <input type="password" 
                                   id="new_password" 
                                   name="new_password" 
                                   class="form-control" 
                                   required 
                                   minlength="6"
                                   autocomplete="new-password">
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm New Password <span style="color: var(--danger);">*</span></label>
                            <input type="password" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   class="form-control" 
                                   required 
                                   minlength="6"
                                   autocomplete="new-password">
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; margin-top: 16px;">
                        <button type="submit" class="btn btn-secondary" style="background: #F1F5F9;">
                            Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
