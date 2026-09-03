<?php
/**
 * Faculty Management System (FMS)
 * Admin: Edit Curriculum Subject
 */

require_once __DIR__ . '/../../includes/admin-auth.php';

$pageTitle = 'Edit Curriculum Subject';
$activeMenu = 'subjects';
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlashMessage('danger', 'Invalid subject reference.');
    header('Location: ' . BASE_URL . 'admin/subjects/index.php');
    exit;
}

// Fetch existing subject
try {
    $stmt = $db->prepare("SELECT * FROM subjects WHERE id = ?");
    $stmt->execute([$id]);
    $subject = $stmt->fetch();

    if (!$subject) {
        setFlashMessage('danger', 'Subject not found.');
        header('Location: ' . BASE_URL . 'admin/subjects/index.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Edit Subject Fetch Error: " . $e->getMessage());
    setFlashMessage('danger', 'Error loading subject details.');
    header('Location: ' . BASE_URL . 'admin/subjects/index.php');
    exit;
}

// Fetch departments
$departments = [];
try {
    $deptStmt = $db->query("SELECT id, dept_code, dept_name FROM departments ORDER BY dept_code ASC");
    $departments = $deptStmt->fetchAll();
} catch (Exception $e) {
    // Non-blocking
}

$errors = [];
$subjectCode = $subject['subject_code'];
$subjectName = $subject['subject_name'];
$departmentId = (int)$subject['department_id'];
$semester = (int)$subject['semester'];
$credits = (int)$subject['credits'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        $errors[] = 'Invalid security session. Please try again.';
    }

    $subjectCode = strtoupper(trim($_POST['subject_code'] ?? ''));
    $subjectName = trim($_POST['subject_name'] ?? '');
    $departmentId = (int)($_POST['department_id'] ?? 0);
    $semester = (int)($_POST['semester'] ?? 1);
    $credits = (int)($_POST['credits'] ?? 3);

    // Validation
    if ($subjectCode === '') {
        $errors[] = 'Subject code is required.';
    }
    if ($subjectName === '') {
        $errors[] = 'Subject name is required.';
    }
    if ($departmentId <= 0) {
        $errors[] = 'Please select a valid academic department.';
    }
    if ($semester < 1 || $semester > 12) {
        $errors[] = 'Semester must be between 1 and 12.';
    }
    if ($credits < 1 || $credits > 10) {
        $errors[] = 'Credits must be between 1 and 10.';
    }

    // Check unique code excluding current
    if (empty($errors)) {
        try {
            $chk = $db->prepare("SELECT COUNT(*) FROM subjects WHERE subject_code = ? AND id != ?");
            $chk->execute([$subjectCode, $id]);
            if ($chk->fetchColumn() > 0) {
                $errors[] = "Another subject with code '{$subjectCode}' already exists.";
            }
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }

    // Update
    if (empty($errors)) {
        try {
            $updateStmt = $db->prepare("
                UPDATE subjects 
                SET subject_code = ?, subject_name = ?, department_id = ?, semester = ?, credits = ?
                WHERE id = ?
            ");
            $updateStmt->execute([$subjectCode, $subjectName, $departmentId, $semester, $credits, $id]);

            setFlashMessage('success', "Course '{$subjectCode}' updated successfully.");
            header('Location: ' . BASE_URL . 'admin/subjects/index.php');
            exit;
        } catch (Exception $e) {
            error_log("Update Subject Error: " . $e->getMessage());
            $errors[] = 'Failed to update subject: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-header">
    <div>
        <h2 style="font-size: 20px; font-weight: 700; color: var(--text); margin: 0 0 4px;">Edit Curriculum Subject</h2>
        <p style="color: var(--text-muted); font-size: 14px; margin: 0;">Update course details for <?php echo escape($subject['subject_code']); ?>.</p>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>admin/subjects/index.php" class="btn btn-secondary">
            &larr; Back to Subjects
        </a>
    </div>
</div>

<div class="card" style="max-width: 720px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title">Modify Subject: <?php echo escape($subject['subject_code']); ?></h3>
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

            <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
                <div class="form-group">
                    <label for="subject_code" class="form-label">
                        Subject Code <span style="color: var(--danger);">*</span>
                    </label>
                    <input type="text" 
                           id="subject_code" 
                           name="subject_code" 
                           class="form-control" 
                           value="<?php echo escape($subjectCode); ?>" 
                           maxlength="20" 
                           required 
                           style="text-transform: uppercase;">
                </div>

                <div class="form-group">
                    <label for="subject_name" class="form-label">
                        Subject Title <span style="color: var(--danger);">*</span>
                    </label>
                    <input type="text" 
                           id="subject_name" 
                           name="subject_name" 
                           class="form-control" 
                           value="<?php echo escape($subjectName); ?>" 
                           maxlength="150" 
                           required>
                </div>

                <div class="form-group">
                    <label for="department_id" class="form-label">
                        Host Department <span style="color: var(--danger);">*</span>
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
                    <label for="semester" class="form-label">
                        Semester <span style="color: var(--danger);">*</span>
                    </label>
                    <select id="semester" name="semester" class="form-control" required>
                        <?php for ($s = 1; $s <= 8; $s++): ?>
                            <option value="<?php echo $s; ?>" <?php echo ($semester === $s) ? 'selected' : ''; ?>>
                                Semester <?php echo $s; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="credits" class="form-label">
                        Credit Points <span style="color: var(--danger);">*</span>
                    </label>
                    <input type="number" 
                           id="credits" 
                           name="credits" 
                           class="form-control" 
                           value="<?php echo $credits; ?>" 
                           min="1" 
                           max="10" 
                           required>
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border);">
                <a href="<?php echo BASE_URL; ?>admin/subjects/index.php" class="btn btn-secondary">
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
