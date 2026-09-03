<?php
/**
 * Faculty Management System (FMS)
 * Admin: Edit Faculty Profile & User Credentials
 */

require_once __DIR__ . '/../../includes/admin-auth.php';

$pageTitle = 'Edit Faculty Profile';
$activeMenu = 'faculty';
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlashMessage('danger', 'Invalid faculty reference.');
    header('Location: ' . BASE_URL . 'admin/faculty/index.php');
    exit;
}

// Fetch faculty record
try {
    $stmt = $db->prepare("
        SELECT f.*, u.username, u.status AS account_status, d.dept_name, d.dept_code
        FROM faculty f
        JOIN users u ON f.user_id = u.id
        JOIN departments d ON f.department_id = d.id
        WHERE f.id = ?
    ");
    $stmt->execute([$id]);
    $faculty = $stmt->fetch();

    if (!$faculty) {
        setFlashMessage('danger', 'Faculty member not found.');
        header('Location: ' . BASE_URL . 'admin/faculty/index.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Edit Faculty Fetch Error: " . $e->getMessage());
    setFlashMessage('danger', 'Error retrieving faculty profile.');
    header('Location: ' . BASE_URL . 'admin/faculty/index.php');
    exit;
}

// Fetch departments for dropdown
$departments = [];
try {
    $deptStmt = $db->query("SELECT id, dept_code, dept_name FROM departments ORDER BY dept_code ASC");
    $departments = $deptStmt->fetchAll();
} catch (Exception $e) {
    // Non-blocking
}

$errors = [];
$empId = $faculty['emp_id'];
$fullName = $faculty['full_name'];
$departmentId = (int)$faculty['department_id'];
$designation = $faculty['designation'];
$qualification = $faculty['qualification'];
$joiningDate = $faculty['joining_date'];
$email = $faculty['email'];
$phone = $faculty['phone'];
$status = $faculty['account_status'];
$newPassword = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        $errors[] = 'Security session expired. Please reload and try again.';
    }

    $empId = strtoupper(trim($_POST['emp_id'] ?? ''));
    $fullName = trim($_POST['full_name'] ?? '');
    $departmentId = (int)($_POST['department_id'] ?? 0);
    $designation = trim($_POST['designation'] ?? '');
    $qualification = trim($_POST['qualification'] ?? '');
    $joiningDate = trim($_POST['joining_date'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $status = trim($_POST['status'] ?? 'active');
    $newPassword = $_POST['new_password'] ?? '';

    // Validations
    if ($empId === '') {
        $errors[] = 'Employee ID is required.';
    }
    if ($fullName === '') {
        $errors[] = 'Full name is required.';
    }
    if ($departmentId <= 0) {
        $errors[] = 'Please select an academic department.';
    }
    if ($designation === '') {
        $errors[] = 'Designation is required.';
    }
    if ($qualification === '') {
        $errors[] = 'Qualification is required.';
    }
    if ($joiningDate === '' || !strtotime($joiningDate)) {
        $errors[] = 'A valid joining date is required.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if ($phone === '') {
        $errors[] = 'Phone contact is required.';
    }
    if ($newPassword !== '' && strlen($newPassword) < 6) {
        $errors[] = 'New password must be at least 6 characters in length.';
    }
    if (!in_array($status, ['active', 'inactive'])) {
        $status = 'active';
    }

    // Check unique emp_id excluding current
    if (empty($errors)) {
        try {
            $chkEmp = $db->prepare("SELECT COUNT(*) FROM faculty WHERE emp_id = ? AND id != ?");
            $chkEmp->execute([$empId, $id]);
            if ($chkEmp->fetchColumn() > 0) {
                $errors[] = "Employee ID '{$empId}' is already assigned to another faculty member.";
            }

            $chkMail = $db->prepare("SELECT COUNT(*) FROM faculty WHERE email = ? AND id != ?");
            $chkMail->execute([$email, $id]);
            if ($chkMail->fetchColumn() > 0) {
                $errors[] = "Email address '{$email}' is already registered to another faculty member.";
            }
        } catch (Exception $e) {
            $errors[] = 'Uniqueness validation error: ' . $e->getMessage();
        }
    }

    // Handle photo replacement if uploaded
    $photoFilename = $faculty['photo'];
    if (empty($errors) && isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['photo'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed with error code ' . $file['error'];
        } else {
            if ($file['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Photo size must not exceed 2 MB.';
            } else {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                if (!in_array($ext, $allowed)) {
                    $errors[] = 'Only JPG, PNG, and WebP images are allowed.';
                } else {
                    $uploadDir = __DIR__ . '/../../assets/images/uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $safeEmp = preg_replace('/[^a-zA-Z0-9]/', '', $empId);
                    $newFilename = 'fac_' . strtolower($safeEmp) . '_' . time() . '.' . $ext;
                    $dest = $uploadDir . $newFilename;

                    if (move_uploaded_file($file['tmp_name'], $dest)) {
                        // Delete previous custom photo if exists
                        if ($photoFilename && $photoFilename !== 'default_avatar.png' && file_exists($uploadDir . $photoFilename)) {
                            @unlink($uploadDir . $photoFilename);
                        }
                        $photoFilename = $newFilename;
                    } else {
                        $errors[] = 'Failed to move uploaded photo to disk.';
                    }
                }
            }
        }
    }

    // Execute Database Updates
    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // 1. Update User (status and optional new password)
            if ($newPassword !== '') {
                $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
                $uStmt = $db->prepare("UPDATE users SET status = ?, password = ? WHERE id = ?");
                $uStmt->execute([$status, $newHash, (int)$faculty['user_id']]);
            } else {
                $uStmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
                $uStmt->execute([$status, (int)$faculty['user_id']]);
            }

            // 2. Update Faculty record
            $fStmt = $db->prepare("
                UPDATE faculty 
                SET department_id = ?, emp_id = ?, full_name = ?, email = ?, phone = ?,
                    designation = ?, qualification = ?, joining_date = ?, photo = ?
                WHERE id = ?
            ");
            $fStmt->execute([
                $departmentId, $empId, $fullName, $email, $phone,
                $designation, $qualification, $joiningDate, $photoFilename,
                $id
            ]);

            $db->commit();

            setFlashMessage('success', "Profile for {$fullName} ({$empId}) was updated successfully.");
            header('Location: ' . BASE_URL . 'admin/faculty/view.php?id=' . $id);
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Update Faculty Transaction Error: " . $e->getMessage());
            $errors[] = 'Update failed: ' . $e->getMessage();
        }
    }
}

$currentPhotoUrl = BASE_URL . 'assets/images/default_avatar.png';
if (!empty($faculty['photo']) && $faculty['photo'] !== 'default_avatar.png') {
    if (file_exists(__DIR__ . '/../../assets/images/uploads/' . $faculty['photo'])) {
        $currentPhotoUrl = BASE_URL . 'assets/images/uploads/' . $faculty['photo'];
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-header">
    <div>
        <h2 style="font-size: 20px; font-weight: 700; color: var(--text); margin: 0 0 4px;">Edit Faculty Profile</h2>
        <p style="color: var(--text-muted); font-size: 14px; margin: 0;">Update institutional data, contact information, and portal account settings.</p>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="<?php echo BASE_URL; ?>admin/faculty/view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
            View Profile
        </a>
        <a href="<?php echo BASE_URL; ?>admin/faculty/index.php" class="btn btn-secondary">
            &larr; Back to Directory
        </a>
    </div>
</div>

<div class="card" style="max-width: 900px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title">Modify Profile: <?php echo escape($faculty['full_name']); ?> (<?php echo escape($faculty['emp_id']); ?>)</h3>
        <span style="font-size: 13px; color: var(--text-muted);"><span style="color: var(--danger);">*</span> Required fields</span>
    </div>
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo escape($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <!-- Section 1: Account Controls -->
            <div style="font-weight: 700; color: var(--primary-dark); font-size: 15px; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid var(--border);">
                1. Portal Authentication &amp; Status
            </div>

            <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
                <div class="form-group">
                    <label class="form-label">Portal Username (Read-Only)</label>
                    <input type="text" class="form-control" value="<?php echo escape($faculty['username']); ?>" readonly style="background: #EDF2F7; cursor: not-allowed;">
                    <small style="color: var(--text-muted); font-size: 11px;">User handles are fixed for audit logging.</small>
                </div>

                <div class="form-group">
                    <label for="status" class="form-label">Account Status <span style="color: var(--danger);">*</span></label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="active" <?php echo ($status === 'active') ? 'selected' : ''; ?>>Active (Can sign in)</option>
                        <option value="inactive" <?php echo ($status === 'inactive') ? 'selected' : ''; ?>>Inactive (Login blocked)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="new_password" class="form-label">Reset Password</label>
                    <input type="password" 
                           id="new_password" 
                           name="new_password" 
                           class="form-control" 
                           placeholder="Leave empty to keep existing password">
                    <small style="color: var(--text-muted); font-size: 11px;">Enter a new password (min. 6 chars) to overwrite.</small>
                </div>
            </div>

            <!-- Section 2: Academic & Institutional Profile -->
            <div style="font-weight: 700; color: var(--primary-dark); font-size: 15px; margin: 24px 0 16px; padding-bottom: 8px; border-bottom: 1px solid var(--border);">
                2. Academic &amp; Institutional Profile
            </div>

            <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
                <div class="form-group">
                    <label for="emp_id" class="form-label">
                        Employee ID <span style="color: var(--danger);">*</span>
                    </label>
                    <input type="text" 
                           id="emp_id" 
                           name="emp_id" 
                           class="form-control" 
                           value="<?php echo escape($empId); ?>" 
                           required 
                           style="text-transform: uppercase;">
                </div>

                <div class="form-group">
                    <label for="full_name" class="form-label">
                        Full Name <span style="color: var(--danger);">*</span>
                    </label>
                    <input type="text" 
                           id="full_name" 
                           name="full_name" 
                           class="form-control" 
                           value="<?php echo escape($fullName); ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="department_id" class="form-label">
                        Department <span style="color: var(--danger);">*</span>
                    </label>
                    <select id="department_id" name="department_id" class="form-control" required>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo (int)$dept['id']; ?>" <?php echo ($departmentId === (int)$dept['id']) ? 'selected' : ''; ?>>
                                <?php echo escape($dept['dept_code']); ?> — <?php echo escape($dept['dept_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="designation" class="form-label">
                        Designation <span style="color: var(--danger);">*</span>
                    </label>
                    <select id="designation" name="designation" class="form-control" required>
                        <?php 
                            $designations = ['Professor & HOD', 'Professor', 'Associate Professor', 'Assistant Professor', 'Lecturer', 'Visiting Faculty'];
                            foreach ($designations as $des): 
                        ?>
                            <option value="<?php echo escape($des); ?>" <?php echo ($designation === $des) ? 'selected' : ''; ?>>
                                <?php echo escape($des); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="qualification" class="form-label">
                        Highest Qualification <span style="color: var(--danger);">*</span>
                    </label>
                    <input type="text" 
                           id="qualification" 
                           name="qualification" 
                           class="form-control" 
                           value="<?php echo escape($qualification); ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="joining_date" class="form-label">
                        Date of Joining <span style="color: var(--danger);">*</span>
                    </label>
                    <input type="date" 
                           id="joining_date" 
                           name="joining_date" 
                           class="form-control" 
                           value="<?php echo escape($joiningDate); ?>" 
                           required>
                </div>
            </div>

            <!-- Section 3: Contact Details & Photo -->
            <div style="font-weight: 700; color: var(--primary-dark); font-size: 15px; margin: 24px 0 16px; padding-bottom: 8px; border-bottom: 1px solid var(--border);">
                3. Contact Information &amp; Photograph
            </div>

            <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
                <div class="form-group">
                    <label for="email" class="form-label">
                        Official Email <span style="color: var(--danger);">*</span>
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control" 
                           value="<?php echo escape($email); ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="phone" class="form-label">
                        Phone Number <span style="color: var(--danger);">*</span>
                    </label>
                    <input type="tel" 
                           id="phone" 
                           name="phone" 
                           class="form-control" 
                           value="<?php echo escape($phone); ?>" 
                           required>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="photo" class="form-label">Profile Photo</label>
                    <div style="display: flex; gap: 16px; align-items: center; margin-bottom: 8px;">
                        <img src="<?php echo escape($currentPhotoUrl); ?>" 
                             alt="Current Photo" 
                             style="width: 54px; height: 54px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border);"
                             referrerpolicy="no-referrer">
                        <div style="flex: 1;">
                            <input type="file" 
                                   id="photo" 
                                   name="photo" 
                                   class="form-control" 
                                   accept="image/jpeg,image/png,image/webp">
                            <small style="color: var(--text-muted); font-size: 11px;">Upload a new image to replace current photo (Max 2MB: JPG, PNG, WebP).</small>
                        </div>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border);">
                <a href="<?php echo BASE_URL; ?>admin/faculty/view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
