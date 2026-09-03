<?php
/**
 * Faculty Management System (FMS)
 * Faculty: My Assigned Curriculum Subjects & Teaching Load
 */

require_once __DIR__ . '/../includes/faculty-auth.php';

$pageTitle = 'My Teaching Subjects';
$activeMenu = 'subjects';
$db = getDB();

$facultyId = (int)$_SESSION['faculty_id'];

// Fetch subjects assigned to this faculty
$assignedSubjects = [];
try {
    $sql = "
        SELECT s.*, 
               d.dept_code, d.dept_name,
               fs.academic_year, fs.created_at AS assigned_date,
               (
                   SELECT COUNT(*) 
                   FROM timetable t 
                   WHERE t.subject_id = s.id AND t.faculty_id = :fac_id
               ) AS my_weekly_periods
        FROM faculty_subjects fs
        JOIN subjects s ON fs.subject_id = s.id
        JOIN departments d ON s.department_id = d.id
        WHERE fs.faculty_id = :fac_id
        ORDER BY s.semester ASC, s.subject_code ASC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([':fac_id' => $facultyId]);
    $assignedSubjects = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Faculty Subjects Error: " . $e->getMessage());
}

$totalCourses = count($assignedSubjects);
$totalCredits = 0;
$totalWeeklyHours = 0;

foreach ($assignedSubjects as $sub) {
    $totalCredits += (int)$sub['credits'];
    $totalWeeklyHours += (int)$sub['my_weekly_periods'];
}

include __DIR__ . '/../includes/header.php';
?>

<div class="content-header">
    <div>
        <h2 style="font-size: 20px; font-weight: 700; color: var(--text); margin: 0 0 4px;">My Assigned Course Portfolio</h2>
        <p style="color: var(--text-muted); font-size: 14px; margin: 0;">Curriculum subjects and academic credits assigned to you for <?php echo escape(ACADEMIC_YEAR); ?>.</p>
    </div>
    <div class="no-print">
        <a href="<?php echo BASE_URL; ?>faculty/timetable.php" class="btn btn-secondary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            View My Weekly Timetable
        </a>
    </div>
</div>

<!-- Workload Metrics -->
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
            <div class="stat-value"><?php echo $totalCourses; ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(2, 132, 199, 0.1); color: #0284C7;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
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
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Teaching Periods</div>
            <div class="stat-value"><?php echo $totalWeeklyHours; ?> Periods / Week</div>
        </div>
    </div>
</div>

<!-- Subjects Table Card -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">My Curriculum Courses</h3>
        <span style="font-size: 13px; color: var(--text-muted);">Session <?php echo escape(ACADEMIC_YEAR); ?></span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 130px;">Course Code</th>
                    <th>Subject Name</th>
                    <th>Department</th>
                    <th style="text-align: center; width: 100px;">Semester</th>
                    <th style="text-align: center; width: 100px;">Credits</th>
                    <th style="text-align: center; width: 130px;">Class Schedule</th>
                    <th style="text-align: right; width: 120px;" class="no-print">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($assignedSubjects)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                                    </svg>
                                </div>
                                <div class="empty-state-title">No Subjects Allocated</div>
                                <p class="empty-state-desc">The college administrator has not assigned courses to your profile yet.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($assignedSubjects as $sub): ?>
                        <tr>
                            <td>
                                <span class="badge badge-info" style="font-family: monospace; font-size: 13px; font-weight: 700;">
                                    <?php echo escape($sub['subject_code']); ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: var(--text); font-size: 14px;">
                                    <?php echo escape($sub['subject_name']); ?>
                                </div>
                                <div style="font-size: 11px; color: var(--text-muted);">
                                    Academic Year: <?php echo escape($sub['academic_year']); ?>
                                </div>
                            </td>
                            <td>
                                <span style="font-weight: 500; font-size: 13px;">
                                    <?php echo escape($sub['dept_name']); ?>
                                </span>
                                <span style="font-size: 11px; color: var(--text-muted); display: block;">
                                    (<?php echo escape($sub['dept_code']); ?>)
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <span style="font-weight: 600; color: var(--text);">
                                    Sem <?php echo (int)$sub['semester']; ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge badge-warning" style="font-weight: 700;">
                                    <?php echo (int)$sub['credits']; ?> Cr
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <?php if ((int)$sub['my_weekly_periods'] > 0): ?>
                                    <span class="badge badge-success" style="font-weight: 600;">
                                        <?php echo (int)$sub['my_weekly_periods']; ?> periods / wk
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-warning" style="font-size: 11px;">
                                        Pending Slot
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;" class="no-print">
                                <a href="<?php echo BASE_URL; ?>faculty/timetable.php" class="btn-action" style="font-size: 12px; padding: 4px 10px; height: auto;">
                                    Timetable
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
