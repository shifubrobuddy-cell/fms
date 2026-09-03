<?php
/**
 * Faculty Management System (FMS)
 * Admin: Faculty Subject Allocation Hub
 */

require_once __DIR__ . '/../../includes/admin-auth.php';

$pageTitle = 'Faculty Subject Allocation';
$activeMenu = 'subjects';
$db = getDB();

$preSubjectId = (int)($_GET['subject_id'] ?? 0);
$preFacultyId = (int)($_GET['faculty_id'] ?? 0);
$filterDeptId = (int)($_GET['dept_id'] ?? 0);

// Fetch departments for filter
$departments = [];
try {
    $departments = $db->query("SELECT id, dept_code, dept_name FROM departments ORDER BY dept_code ASC")->fetchAll();
} catch (Exception $e) {}

// Fetch all active faculty
$facultyList = [];
try {
    $facSql = "
        SELECT f.id, f.full_name, f.emp_id, f.department_id, d.dept_code,
               (SELECT COUNT(*) FROM faculty_subjects WHERE faculty_id = f.id) AS current_load
        FROM faculty f
        JOIN departments d ON f.department_id = d.id
        ORDER BY d.dept_code ASC, f.full_name ASC
    ";
    $facultyList = $db->query($facSql)->fetchAll();
} catch (Exception $e) {}

// Fetch all subjects
$subjectsList = [];
try {
    $subSql = "
        SELECT s.id, s.subject_code, s.subject_name, s.semester, s.credits, s.department_id, d.dept_code
        FROM subjects s
        JOIN departments d ON s.department_id = d.id
        ORDER BY d.dept_code ASC, s.semester ASC, s.subject_code ASC
    ";
    $subjectsList = $db->query($subSql)->fetchAll();
} catch (Exception $e) {}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        $errors[] = 'Invalid security session. Please try again.';
    }

    $facultyId = (int)($_POST['faculty_id'] ?? 0);
    $subjectId = (int)($_POST['subject_id'] ?? 0);
    $academicYear = trim($_POST['academic_year'] ?? ACADEMIC_YEAR);

    if ($facultyId <= 0) {
        $errors[] = 'Please select a faculty member.';
    }
    if ($subjectId <= 0) {
        $errors[] = 'Please select a curriculum subject.';
    }
    if ($academicYear === '') {
        $academicYear = ACADEMIC_YEAR;
    }

    // Check duplicate
    if (empty($errors)) {
        try {
            $chk = $db->prepare("SELECT COUNT(*) FROM faculty_subjects WHERE faculty_id = ? AND subject_id = ?");
            $chk->execute([$facultyId, $subjectId]);
            if ($chk->fetchColumn() > 0) {
                $errors[] = 'This faculty member is already allocated to teach this course.';
            }
        } catch (Exception $e) {
            $errors[] = 'Verification error: ' . $e->getMessage();
        }
    }

    // Insert allocation
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("INSERT INTO faculty_subjects (faculty_id, subject_id, academic_year) VALUES (?, ?, ?)");
            $stmt->execute([$facultyId, $subjectId, $academicYear]);

            setFlashMessage('success', 'Faculty course allocation established successfully.');
            header('Location: ' . BASE_URL . 'admin/subjects/allocate.php' . ($filterDeptId ? '?dept_id=' . $filterDeptId : ''));
            exit;
        } catch (Exception $e) {
            error_log("Allocation Insert Error: " . $e->getMessage());
            $errors[] = 'Failed to record allocation: ' . $e->getMessage();
        }
    }
}

// Fetch Current Active Allocations Roster
$allocations = [];
try {
    $whereDept = $filterDeptId > 0 ? "WHERE s.department_id = " . $filterDeptId : "";
    $allocSql = "
        SELECT fs.id AS allocation_id, fs.academic_year, fs.created_at,
               f.id AS faculty_id, f.full_name, f.emp_id, f.designation,
               s.id AS subject_id, s.subject_code, s.subject_name, s.semester, s.credits,
               d.dept_code, d.dept_name
        FROM faculty_subjects fs
        JOIN faculty f ON fs.faculty_id = f.id
        JOIN subjects s ON fs.subject_id = s.id
        JOIN departments d ON s.department_id = d.id
        {$whereDept}
        ORDER BY d.dept_code ASC, s.semester ASC, s.subject_code ASC, f.full_name ASC
    ";
    $allocations = $db->query($allocSql)->fetchAll();
} catch (Exception $e) {
    error_log("Fetch Allocations Error: " . $e->getMessage());
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-header">
    <div>
        <h2 style="font-size: 20px; font-weight: 700; color: var(--text); margin: 0 0 4px;">Faculty Course Allocation Matrix</h2>
        <p style="color: var(--text-muted); font-size: 14px; margin: 0;">Map teaching faculty to curriculum subjects for Academic Year <?php echo escape(ACADEMIC_YEAR); ?>.</p>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>admin/subjects/index.php" class="btn btn-secondary">
            &larr; Back to Subjects
        </a>
    </div>
</div>

<div style="display: grid; grid-template-columns: minmax(320px, 380px) 1fr; gap: 24px; align-items: start;">
    <!-- Form Card: Assign New -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">New Course Allocation</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" style="padding: 10px 14px; font-size: 13px;">
                    <ul style="margin: 0; padding-left: 16px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo escape($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <div class="form-group">
                    <label for="faculty_id" class="form-label">
                        Teaching Faculty <span style="color: var(--danger);">*</span>
                    </label>
                    <select id="faculty_id" name="faculty_id" class="form-control" required>
                        <option value="">-- Choose Faculty --</option>
                        <?php foreach ($facultyList as $fac): ?>
                            <option value="<?php echo (int)$fac['id']; ?>" <?php echo ($preFacultyId === (int)$fac['id']) ? 'selected' : ''; ?>>
                                [<?php echo escape($fac['dept_code']); ?>] <?php echo escape($fac['full_name']); ?> (<?php echo escape($fac['emp_id']); ?>) &bull; <?php echo (int)$fac['current_load']; ?> courses
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="subject_id" class="form-label">
                        Target Subject <span style="color: var(--danger);">*</span>
                    </label>
                    <select id="subject_id" name="subject_id" class="form-control" required>
                        <option value="">-- Choose Subject --</option>
                        <?php foreach ($subjectsList as $sub): ?>
                            <option value="<?php echo (int)$sub['id']; ?>" <?php echo ($preSubjectId === (int)$sub['id']) ? 'selected' : ''; ?>>
                                [<?php echo escape($sub['dept_code']); ?> | Sem <?php echo (int)$sub['semester']; ?>] <?php echo escape($sub['subject_code']); ?> — <?php echo escape($sub['subject_name']); ?> (<?php echo (int)$sub['credits']; ?> Cr)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="academic_year" class="form-label">Academic Year</label>
                    <input type="text" 
                           id="academic_year" 
                           name="academic_year" 
                           class="form-control" 
                           value="<?php echo escape(ACADEMIC_YEAR); ?>" 
                           required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <line x1="19" y1="8" x2="19" y2="14"/>
                        <line x1="22" y1="11" x2="16" y2="11"/>
                    </svg>
                    Confirm Allocation
                </button>
            </form>
        </div>
    </div>

    <!-- Active Allocations Roster -->
    <div class="card">
        <div class="card-header" style="flex-wrap: wrap; gap: 10px;">
            <div>
                <h3 class="card-title">Allocated Workload Register</h3>
                <span style="font-size: 13px; color: var(--text-muted);"><?php echo count($allocations); ?> Active mapping(s)</span>
            </div>
            <!-- Department Filter -->
            <form method="GET" action="" style="display: flex; gap: 8px;">
                <select name="dept_id" class="form-control" style="font-size: 12px; padding: 4px 8px; width: auto;" onchange="this.form.submit()">
                    <option value="0">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?php echo (int)$d['id']; ?>" <?php echo ($filterDeptId === (int)$d['id']) ? 'selected' : ''; ?>>
                            <?php echo escape($d['dept_code']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Sem / Cr</th>
                        <th>Assigned Faculty</th>
                        <th>Dept</th>
                        <th style="text-align: right; width: 60px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($allocations)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                No course allocations recorded yet for this selection.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($allocations as $row): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--primary-dark); font-size: 13px;">
                                        <?php echo escape($row['subject_code']); ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--text);">
                                        <?php echo escape($row['subject_name']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-size: 12px;">Sem <?php echo (int)$row['semester']; ?></span>
                                    <span class="badge badge-warning" style="font-size: 11px; padding: 1px 6px; margin-left: 4px;">
                                        <?php echo (int)$row['credits']; ?> Cr
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>admin/faculty/view.php?id=<?php echo (int)$row['faculty_id']; ?>" style="color: inherit; font-weight: 600; text-decoration: none;">
                                        <?php echo escape($row['full_name']); ?>
                                    </a>
                                    <div style="font-size: 11px; color: var(--text-muted);">
                                        <?php echo escape($row['emp_id']); ?> &bull; <?php echo escape($row['designation']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo escape($row['dept_code']); ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <form method="POST" 
                                          action="<?php echo BASE_URL; ?>admin/subjects/deallocate.php" 
                                          onsubmit="return confirm('Remove allocation of <?php echo escape($row['subject_code']); ?> from <?php echo escape($row['full_name']); ?>?');">
                                        <input type="hidden" name="faculty_id" value="<?php echo (int)$row['faculty_id']; ?>">
                                        <input type="hidden" name="subject_id" value="<?php echo (int)$row['subject_id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <button type="submit" class="btn-action btn-action-danger" title="Unlink Allocation">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"/>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
