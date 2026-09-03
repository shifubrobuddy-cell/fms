<?php
/**
 * Faculty Management System (FMS)
 * Admin: Subjects Directory & Faculty Allocation Roster
 */

require_once __DIR__ . '/../../includes/admin-auth.php';

$pageTitle = 'Subjects & Course Allocation';
$activeMenu = 'subjects';
$db = getDB();

// Filters & Search
$search = trim($_GET['search'] ?? '');
$departmentId = (int)($_GET['department_id'] ?? 0);
$semester = (int)($_GET['semester'] ?? 0);

// Fetch departments for filter
$departments = [];
try {
    $deptStmt = $db->query("SELECT id, dept_code, dept_name FROM departments ORDER BY dept_code ASC");
    $departments = $deptStmt->fetchAll();
} catch (Exception $e) {
    error_log("Subjects Filter Depts Error: " . $e->getMessage());
}

// Build query
$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(s.subject_code LIKE :s1 OR s.subject_name LIKE :s2)";
    $term = "%{$search}%";
    $params[':s1'] = $term;
    $params[':s2'] = $term;
}

if ($departmentId > 0) {
    $where[] = "s.department_id = :dept_id";
    $params[':dept_id'] = $departmentId;
}

if ($semester > 0) {
    $where[] = "s.semester = :sem";
    $params[':sem'] = $semester;
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Fetch subjects with department and assigned faculty list
$subjects = [];
try {
    $sql = "
        SELECT s.*, 
               d.dept_code, 
               d.dept_name,
               (
                   SELECT GROUP_CONCAT(f.full_name || '::' || f.id || '::' || f.emp_id, '||')
                   FROM faculty_subjects fs
                   JOIN faculty f ON fs.faculty_id = f.id
                   WHERE fs.subject_id = s.id
               ) AS assigned_faculty_raw,
               (
                   SELECT COUNT(*) 
                   FROM timetable t 
                   WHERE t.subject_id = s.id
               ) AS timetable_slot_count
        FROM subjects s
        JOIN departments d ON s.department_id = d.id
        {$whereClause}
        ORDER BY d.dept_code ASC, s.semester ASC, s.subject_code ASC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $subjects = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Subjects List Error: " . $e->getMessage());
}

// Metrics
$totalSubjects = count($subjects);
$totalCredits = 0;
$allocatedCount = 0;
$unallocatedCount = 0;

foreach ($subjects as $s) {
    $totalCredits += (int)$s['credits'];
    if (!empty($s['assigned_faculty_raw'])) {
        $allocatedCount++;
    } else {
        $unallocatedCount++;
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-header">
    <div>
        <h2 style="font-size: 20px; font-weight: 700; color: var(--text); margin: 0 0 4px;">Curriculum Subjects &amp; Allocation</h2>
        <p style="color: var(--text-muted); font-size: 14px; margin: 0;">Manage academic syllabus, course credit values, and faculty teaching assignments.</p>
    </div>
    <div style="display: flex; gap: 10px;" class="no-print">
        <a href="<?php echo BASE_URL; ?>admin/subjects/allocate.php" class="btn btn-secondary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <line x1="19" y1="8" x2="19" y2="14"/>
                <line x1="22" y1="11" x2="16" y2="11"/>
            </svg>
            Allocate Faculty
        </a>
        <a href="<?php echo BASE_URL; ?>admin/subjects/add.php" class="btn btn-primary" id="btn-add-subject">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Add New Subject
        </a>
    </div>
</div>

<!-- Metrics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(13, 148, 136, 0.1); color: var(--accent);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Active Courses</div>
            <div class="stat-value"><?php echo $totalSubjects; ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(2, 132, 199, 0.1); color: #0284C7;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 6v6l4 2"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Total Credit Hours</div>
            <div class="stat-value"><?php echo $totalCredits; ?> Credits</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Faculty Assigned</div>
            <div class="stat-value"><?php echo $allocatedCount; ?> Subjects</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: var(--danger);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Unallocated Courses</div>
            <div class="stat-value"><?php echo $unallocatedCount; ?> Need Faculty</div>
        </div>
    </div>
</div>

<!-- Filters Toolbar -->
<div class="card no-print" style="margin-bottom: 24px;">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" action="" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) auto; gap: 12px; align-items: end;">
            <div>
                <label class="form-label" style="font-size: 12px; margin-bottom: 4px;">Search Course</label>
                <input type="text" 
                       name="search" 
                       class="form-control" 
                       placeholder="Code or Course title..." 
                       value="<?php echo escape($search); ?>">
            </div>

            <div>
                <label class="form-label" style="font-size: 12px; margin-bottom: 4px;">Department</label>
                <select name="department_id" class="form-control">
                    <option value="0">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo (int)$dept['id']; ?>" <?php echo ($departmentId === (int)$dept['id']) ? 'selected' : ''; ?>>
                            <?php echo escape($dept['dept_code']); ?> — <?php echo escape($dept['dept_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="form-label" style="font-size: 12px; margin-bottom: 4px;">Semester</label>
                <select name="semester" class="form-control">
                    <option value="0">All Semesters</option>
                    <?php for ($s = 1; $s <= 8; $s++): ?>
                        <option value="<?php echo $s; ?>" <?php echo ($semester === $s) ? 'selected' : ''; ?>>
                            Semester <?php echo $s; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary" style="height: 40px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    Filter
                </button>
                <?php if ($search !== '' || $departmentId > 0 || $semester > 0): ?>
                    <a href="<?php echo BASE_URL; ?>admin/subjects/index.php" class="btn btn-secondary" style="height: 40px;" title="Reset filters">
                        Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Subjects Table Card -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Subject Catalog &amp; Assigned Instructors</h3>
        <span style="font-size: 13px; color: var(--text-muted);"><?php echo count($subjects); ?> record(s) matching criteria</span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 120px;">Course Code</th>
                    <th>Subject Title</th>
                    <th>Department</th>
                    <th style="text-align: center; width: 100px;">Semester</th>
                    <th style="text-align: center; width: 90px;">Credits</th>
                    <th>Assigned Instructors</th>
                    <th style="text-align: right; width: 140px;" class="no-print">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subjects)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                                    </svg>
                                </div>
                                <div class="empty-state-title">No Subjects Found</div>
                                <p class="empty-state-desc">
                                    No subjects match the filter criteria or no curriculum has been entered yet.
                                </p>
                                <a href="<?php echo BASE_URL; ?>admin/subjects/add.php" class="btn btn-primary" style="margin-top: 12px;">
                                    Create New Course
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($subjects as $s): ?>
                        <tr>
                            <td>
                                <span class="badge badge-info" style="font-family: monospace; font-size: 13px; font-weight: 700;">
                                    <?php echo escape($s['subject_code']); ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: var(--text);">
                                    <?php echo escape($s['subject_name']); ?>
                                </div>
                                <?php if ($s['timetable_slot_count'] > 0): ?>
                                    <div style="font-size: 11px; color: var(--text-muted);">
                                        <?php echo (int)$s['timetable_slot_count']; ?> timetable slot(s) scheduled
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-weight: 500; font-size: 13px;">
                                    <?php echo escape($s['dept_name']); ?>
                                </span>
                                <span style="font-size: 11px; color: var(--text-muted); display: block;">
                                    (<?php echo escape($s['dept_code']); ?>)
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <span style="font-weight: 600; color: var(--text);">
                                    Sem <?php echo (int)$s['semester']; ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge badge-warning" style="font-weight: 700;">
                                    <?php echo (int)$s['credits']; ?> Cr
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($s['assigned_faculty_raw'])): ?>
                                    <div style="display: flex; flex-direction: column; gap: 4px;">
                                        <?php 
                                            $facultyEntries = explode('||', $s['assigned_faculty_raw']);
                                            foreach ($facultyEntries as $entry):
                                                if (empty($entry)) continue;
                                                $parts = explode('::', $entry);
                                                $fName = $parts[0] ?? '';
                                                $fId = (int)($parts[1] ?? 0);
                                                $fEmp = $parts[2] ?? '';
                                        ?>
                                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 13px; background: #F8FAFC; padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border);">
                                                <a href="<?php echo BASE_URL; ?>admin/faculty/view.php?id=<?php echo $fId; ?>" style="color: var(--primary-dark); font-weight: 600; text-decoration: none;">
                                                    <?php echo escape($fName); ?> 
                                                    <span style="font-size: 11px; color: var(--text-muted); font-weight: normal;">(<?php echo escape($fEmp); ?>)</span>
                                                </a>
                                                <form method="POST" action="<?php echo BASE_URL; ?>admin/subjects/deallocate.php" style="display: inline;" onsubmit="return confirm('Remove <?php echo escape($fName); ?> from teaching <?php echo escape($s['subject_code']); ?>?');">
                                                    <input type="hidden" name="faculty_id" value="<?php echo $fId; ?>">
                                                    <input type="hidden" name="subject_id" value="<?php echo (int)$s['id']; ?>">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                    <button type="submit" class="btn-action btn-action-danger no-print" title="Remove Allocation" style="width: 22px; height: 22px; padding: 0;">
                                                        &times;
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        <span class="badge badge-danger">Unassigned</span>
                                        <a href="<?php echo BASE_URL; ?>admin/subjects/allocate.php?subject_id=<?php echo (int)$s['id']; ?>" class="btn-action no-print" title="Assign Faculty" style="font-size: 11px; padding: 2px 8px; height: auto;">
                                            + Assign
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;" class="no-print">
                                <div class="table-actions" style="justify-content: flex-end;">
                                    <a href="<?php echo BASE_URL; ?>admin/subjects/allocate.php?subject_id=<?php echo (int)$s['id']; ?>" 
                                       class="btn-action" 
                                       title="Allocate Faculty">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                            <circle cx="9" cy="7" r="4"/>
                                            <line x1="19" y1="8" x2="19" y2="14"/>
                                            <line x1="22" y1="11" x2="16" y2="11"/>
                                        </svg>
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>admin/subjects/edit.php?id=<?php echo (int)$s['id']; ?>" 
                                       class="btn-action" 
                                       title="Edit Subject">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </a>
                                    <form method="POST" 
                                          action="<?php echo BASE_URL; ?>admin/subjects/delete.php" 
                                          style="display: inline;"
                                          onsubmit="return confirm('Permanently delete <?php echo escape($s['subject_code']); ?> — <?php echo escape($s['subject_name']); ?>? This removes its timetable sessions as well.');">
                                        <input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <button type="submit" class="btn-action btn-action-danger" title="Delete Subject">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"/>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
