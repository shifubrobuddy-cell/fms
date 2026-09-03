<?php
/**
 * Faculty Management System (FMS)
 * Admin: Add New Department
 */

require_once __DIR__ . '/../../includes/admin-auth.php';

$pageTitle = 'Add Department';
$activeMenu = 'departments';
$db = getDB();

$errors = [];
$deptCode = '';
$deptName = '';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        $errors[] = 'Invalid security session. Please try again.';
    }

    $deptCode = strtoupper(trim($_POST['dept_code'] ?? ''));
    $deptName = trim($_POST['dept_name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Validation
    if ($deptCode === '') {
        $errors[] = 'Department code is required (e.g. CSE, BCA).';
    } elseif (strlen($deptCode) > 10) {
        $errors[] = 'Department code cannot exceed 10 characters.';
    } elseif (!preg_match('/^[A-Z0-9_-]+$/', $deptCode)) {
        $errors[] = 'Department code may only contain uppercase letters, numbers, hyphens, and underscores.';
    }

    if ($deptName === '') {
        $errors[] = 'Department name is required.';
    } elseif (strlen($deptName) > 100) {
        $errors[] = 'Department name cannot exceed 100 characters.';
    }

    // Check uniqueness of dept_code
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM departments WHERE dept_code = ?");
            $stmt->execute([$deptCode]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "A department with code '{$deptCode}' already exists.";
            }
        } catch (Exception $e) {
            $errors[] = 'Database validation error: ' . $e->getMessage();
        }
    }

    // Insert
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("INSERT INTO departments (dept_code, dept_name, description) VALUES (?, ?, ?)");
            $stmt->execute([$deptCode, $deptName, $description]);

            setFlashMessage('success', "Department '{$deptCode} — {$deptName}' created successfully.");
            header('Location: ' . BASE_URL . 'admin/departments/index.php');
            exit;
        } catch (Exception $e) {
            error_log("Add Department Error: " . $e->getMessage());
            $errors[] = 'An error occurred while saving the department. Please try again.';
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-header">
    <div>
        <h2 style="font-size: 20px; font-weight: 700; color: var(--text); margin: 0 0 4px;">Create Academic Department</h2>
        <p style="color: var(--text-muted); font-size: 14px; margin: 0;">Add a new academic discipline or administrative faculty division.</p>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>admin/departments/index.php" class="btn btn-secondary">
            &larr; Back to Departments
        </a>
    </div>
</div>

<div class="card" style="max-width: 680px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title">Department Details</h3>
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

        <form method="POST" action="" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <div class="form-group">
                <label for="dept_code" class="form-label">
                    Department Code <span style="color: var(--danger);">*</span>
                </label>
                <input type="text" 
                       id="dept_code" 
                       name="dept_code" 
                       class="form-control" 
                       placeholder="e.g. CSE, BCA, MECH, ECE" 
                       value="<?php echo escape($deptCode); ?>" 
                       maxlength="10" 
                       required 
                       autofocus 
                       style="text-transform: uppercase;">
                <small style="color: var(--text-muted); font-size: 12px; display: block; margin-top: 4px;">
                    Short uppercase acronym (up to 10 characters). Unique across the institution.
                </small>
            </div>

            <div class="form-group">
                <label for="dept_name" class="form-label">
                    Full Department Name <span style="color: var(--danger);">*</span>
                </label>
                <input type="text" 
                       id="dept_name" 
                       name="dept_name" 
                       class="form-control" 
                       placeholder="e.g. Computer Science & Engineering" 
                       value="<?php echo escape($deptName); ?>" 
                       maxlength="100" 
                       required>
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Description / Scope</label>
                <textarea id="description" 
                          name="description" 
                          class="form-control" 
                          rows="3" 
                          placeholder="Optional overview of department curriculum, objectives, or facilities..."><?php echo escape($description); ?></textarea>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border);">
                <a href="<?php echo BASE_URL; ?>admin/departments/index.php" class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Save Department
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
