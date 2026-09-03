<?php
/**
 * Faculty Management System (FMS)
 * Admin: Edit Department
 */

require_once __DIR__ . '/../../includes/admin-auth.php';

$pageTitle = 'Edit Department';
$activeMenu = 'departments';
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlashMessage('danger', 'Invalid department reference.');
    header('Location: ' . BASE_URL . 'admin/departments/index.php');
    exit;
}

// Fetch current record
try {
    $stmt = $db->prepare("SELECT * FROM departments WHERE id = ?");
    $stmt->execute([$id]);
    $department = $stmt->fetch();

    if (!$department) {
        setFlashMessage('danger', 'The requested department was not found.');
        header('Location: ' . BASE_URL . 'admin/departments/index.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Edit Department Fetch Error: " . $e->getMessage());
    setFlashMessage('danger', 'Error loading department details.');
    header('Location: ' . BASE_URL . 'admin/departments/index.php');
    exit;
}

$errors = [];
$deptCode = $department['dept_code'];
$deptName = $department['dept_name'];
$description = $department['description'] ?? '';

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
        $errors[] = 'Department code is required.';
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

    // Check uniqueness excluding current record
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM departments WHERE dept_code = ? AND id != ?");
            $stmt->execute([$deptCode, $id]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "A department with code '{$deptCode}' already exists.";
            }
        } catch (Exception $e) {
            $errors[] = 'Database error checking uniqueness: ' . $e->getMessage();
        }
    }

    // Update
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("UPDATE departments SET dept_code = ?, dept_name = ?, description = ? WHERE id = ?");
            $stmt->execute([$deptCode, $deptName, $description, $id]);

            setFlashMessage('success', "Department '{$deptCode}' updated successfully.");
            header('Location: ' . BASE_URL . 'admin/departments/index.php');
            exit;
        } catch (Exception $e) {
            error_log("Update Department Error: " . $e->getMessage());
            $errors[] = 'Failed to update department. Please try again.';
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-header">
    <div>
        <h2 style="font-size: 20px; font-weight: 700; color: var(--text); margin: 0 0 4px;">Edit Academic Department</h2>
        <p style="color: var(--text-muted); font-size: 14px; margin: 0;">Update code, title, or scope for <?php echo escape($department['dept_name']); ?>.</p>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>admin/departments/index.php" class="btn btn-secondary">
            &larr; Back to Departments
        </a>
    </div>
</div>

<div class="card" style="max-width: 680px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title">Modify Department: <?php echo escape($department['dept_code']); ?></h3>
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
                       value="<?php echo escape($deptCode); ?>" 
                       maxlength="10" 
                       required 
                       style="text-transform: uppercase;">
                <small style="color: var(--text-muted); font-size: 12px; display: block; margin-top: 4px;">
                    Warning: Changing this code will affect faculty and subject allocation displays.
                </small>
            </div>

            <div class="form-group">
                <label for="dept_name" class="form-label">
                    Department Name <span style="color: var(--danger);">*</span>
                </label>
                <input type="text" 
                       id="dept_name" 
                       name="dept_name" 
                       class="form-control" 
                       value="<?php echo escape($deptName); ?>" 
                       maxlength="100" 
                       required>
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Description / Scope</label>
                <textarea id="description" 
                          name="description" 
                          class="form-control" 
                          rows="3"><?php echo escape($description); ?></textarea>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border);">
                <a href="<?php echo BASE_URL; ?>admin/departments/index.php" class="btn btn-secondary">
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
