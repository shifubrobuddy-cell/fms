<?php
/**
 * Faculty Management System (FMS)
 * Admin: Comprehensive Faculty Profile Dossier
 */

require_once __DIR__ . '/../../includes/admin-auth.php';

$pageTitle = 'Faculty Profile Details';
$activeMenu = 'faculty';
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlashMessage('danger', 'Invalid faculty reference.');
    header('Location: ' . BASE_URL . 'admin/faculty/index.php');
    exit;
}

try {
    // 1. Fetch Faculty Profile & Department Info
    $stmt = $db->prepare("
        SELECT f.*, u.username, u.status AS account_status, u.created_at AS user_created_at,
               d.dept_code, d.dept_name, d.description AS dept_desc
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

    // 2. Fetch Allocated Subjects
    $subStmt = $db->prepare("
        SELECT s.*, fs.academic_year, fs.created_at AS assigned_at
        FROM faculty_subjects fs
        JOIN subjects s ON fs.subject_id = s.id
        WHERE fs.faculty_id = ?
        ORDER BY s.semester ASC, s.subject_code ASC
    ");
    $subStmt->execute([$id]);
    $subjects = $subStmt->fetchAll();

    // 3. Fetch Timetable Schedule
    $ttStmt = $db->prepare("
        SELECT t.*, s.subject_code, s.subject_name
        FROM timetable t
        JOIN subjects s ON t.subject_id = s.id
        WHERE t.faculty_id = ?
        ORDER BY 
            CASE t.day_of_week
                WHEN 'Monday' THEN 1
                WHEN 'Tuesday' THEN 2
                WHEN 'Wednesday' THEN 3
                WHEN 'Thursday' THEN 4
                WHEN 'Friday' THEN 5
                WHEN 'Saturday' THEN 6
                ELSE 7
            END, t.start_time ASC
    ");
    $ttStmt->execute([$id]);
    $timetableSlots = $ttStmt->fetchAll();

    // 4. Attendance Stats
    $attStmt = $db->prepare("
        SELECT 
            COUNT(*) AS total_days,
            SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_days,
            SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) AS absent_days,
            SUM(CASE WHEN status = 'On Leave' THEN 1 ELSE 0 END) AS leave_days
        FROM attendance
        WHERE faculty_id = ?
    ");
    $attStmt->execute([$id]);
    $attStats = $attStmt->fetch();

    $totalDays = (int)($attStats['total_days'] ?? 0);
    $presentDays = (int)($attStats['present_days'] ?? 0);
    $attPercentage = ($totalDays > 0) ? round(($presentDays / $totalDays) * 100, 1) : 0.0;

    // 5. Recent Leave History
    $leaveStmt = $db->prepare("
        SELECT * FROM leave_requests
        WHERE faculty_id = ?
        ORDER BY id DESC
        LIMIT 5
    ");
    $leaveStmt->execute([$id]);
    $recentLeaves = $leaveStmt->fetchAll();

} catch (Exception $e) {
    error_log("Faculty View Error: " . $e->getMessage());
    setFlashMessage('danger', 'Error loading comprehensive faculty profile.');
    header('Location: ' . BASE_URL . 'admin/faculty/index.php');
    exit;
}

// Calculate tenure
$joinTime = strtotime($faculty['joining_date']);
$tenureYears = floor((time() - $joinTime) / (365.25 * 86400));
$tenureMonths = floor(((time() - $joinTime) % (365.25 * 86400)) / (30.4 * 86400));

$photoUrl = BASE_URL . 'assets/images/default_avatar.png';
if (!empty($faculty['photo']) && $faculty['photo'] !== 'default_avatar.png') {
    if (file_exists(__DIR__ . '/../../assets/images/uploads/' . $faculty['photo'])) {
        $photoUrl = BASE_URL . 'assets/images/uploads/' . $faculty['photo'];
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<!-- Header Actions -->
<div class="content-header">
    <div>
        <h2 style="font-size: 20px; font-weight: 700; color: var(--text); margin: 0 0 4px;">Faculty Profile Dossier</h2>
        <p style="color: var(--text-muted); font-size: 14px; margin: 0;">Institutional records for <?php echo escape($faculty['full_name']); ?>.</p>
    </div>
    <div style="display: flex; gap: 8px;" class="no-print">
        <button type="button" class="btn btn-secondary" onclick="window.print();">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"/>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                <rect x="6" y="14" width="12" height="8"/>
            </svg>
            Print Dossier
        </button>
        <a href="<?php echo BASE_URL; ?>admin/faculty/edit.php?id=<?php echo $id; ?>" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
            Edit Profile
        </a>
        <a href="<?php echo BASE_URL; ?>admin/faculty/index.php" class="btn btn-secondary">
            &larr; Back
        </a>
    </div>
</div>

<!-- Print Institutional Header -->
<div class="print-header">
    <h2>Institutional Faculty Profile Dossier</h2>
    <p>Academic Year <?php echo escape(ACADEMIC_YEAR); ?> &bull; Printed: <?php echo date('d M Y, h:i A'); ?></p>
</div>

<!-- Profile Hero Card -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-body" style="padding: 24px;">
        <div style="display: flex; gap: 24px; align-items: flex-start; flex-wrap: wrap;">
            <img src="<?php echo escape($photoUrl); ?>" 
                 alt="<?php echo escape($faculty['full_name']); ?>" 
                 style="width: 100px; height: 100px; border-radius: 12px; object-fit: cover; border: 2px solid var(--border); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.06);"
                 referrerpolicy="no-referrer">
            
            <div style="flex: 1; min-width: 260px;">
                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 6px;">
                    <h2 style="font-size: 22px; font-weight: 700; color: var(--text); margin: 0;">
                        <?php echo escape($faculty['full_name']); ?>
                    </h2>
                    <span class="badge badge-info" style="font-size: 13px; font-weight: 700;">
                        <?php echo escape($faculty['emp_id']); ?>
                    </span>
                    <?php if ($faculty['account_status'] === 'active'): ?>
                        <span class="badge badge-success">Active Account</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Account Suspended</span>
                    <?php endif; ?>
                </div>

                <div style="font-size: 15px; font-weight: 600; color: var(--accent); margin-bottom: 4px;">
                    <?php echo escape($faculty['designation']); ?> &bull; Dept. of <?php echo escape($faculty['dept_name']); ?> (<?php echo escape($faculty['dept_code']); ?>)
                </div>

                <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 12px;">
                    <?php echo escape($faculty['qualification']); ?>
                </div>

                <!-- Meta Pills -->
                <div style="display: flex; gap: 20px; flex-wrap: wrap; font-size: 13px; color: var(--text);">
                    <div>
                        <strong style="color: var(--text-muted);">Email:</strong> 
                        <a href="mailto:<?php echo escape($faculty['email']); ?>" style="color: var(--accent); text-decoration: none;">
                            <?php echo escape($faculty['email']); ?>
                        </a>
                    </div>
                    <div>
                        <strong style="color: var(--text-muted);">Phone:</strong> 
                        <?php echo escape($faculty['phone']); ?>
                    </div>
                    <div>
                        <strong style="color: var(--text-muted);">Username:</strong> 
                        <code><?php echo escape($faculty['username']); ?></code>
                    </div>
                    <div>
                        <strong style="color: var(--text-muted);">Joined:</strong> 
                        <?php echo date('d M Y', strtotime($faculty['joining_date'])); ?> 
                        (<?php echo $tenureYears; ?>y <?php echo $tenureMonths; ?>m service)
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Performance & Stats Metrics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(13, 148, 136, 0.1); color: var(--accent);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Assigned Subjects</div>
            <div class="stat-value"><?php echo count($subjects); ?> Courses</div>
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
            <div class="stat-label">Weekly Sessions</div>
            <div class="stat-value"><?php echo count($timetableSlots); ?> Hours</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 11l3 3L22 4"/>
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Attendance Rate</div>
            <div class="stat-value"><?php echo $attPercentage; ?>%</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--warning);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Approved Leaves</div>
            <div class="stat-value"><?php echo (int)($attStats['leave_days'] ?? 0); ?> Days</div>
        </div>
    </div>
</div>

<!-- Two-Column Grid: Course Allocation & Timetable Schedule -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 24px; margin-bottom: 24px;">
    <!-- Column 1: Allocated Subjects -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Curriculum Subjects (<?php echo count($subjects); ?>)</h3>
            <a href="<?php echo BASE_URL; ?>admin/subjects/allocate.php?faculty_id=<?php echo $id; ?>" class="btn btn-secondary no-print" style="padding: 4px 10px; font-size: 12px;">
                Manage Allocation
            </a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Subject Title</th>
                        <th>Sem</th>
                        <th style="text-align: center;">Credits</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subjects)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 24px;">
                                No courses currently allocated to this faculty member.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($subjects as $sub): ?>
                            <tr>
                                <td>
                                    <span class="badge badge-info" style="font-weight: 700;">
                                        <?php echo escape($sub['subject_code']); ?>
                                    </span>
                                </td>
                                <td style="font-weight: 600; color: var(--text);">
                                    <?php echo escape($sub['subject_name']); ?>
                                </td>
                                <td>Sem <?php echo (int)$sub['semester']; ?></td>
                                <td style="text-align: center;">
                                    <?php echo (int)$sub['credits']; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Column 2: Weekly Schedule Overview -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Weekly Lecture Timetable</h3>
            <span style="font-size: 13px; color: var(--text-muted);">AY <?php echo escape(ACADEMIC_YEAR); ?></span>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Course</th>
                        <th>Room</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($timetableSlots)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 24px;">
                                No lecture sessions scheduled on the institutional master timetable.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($timetableSlots as $tt): ?>
                            <tr>
                                <td style="font-weight: 600; color: var(--primary-dark);">
                                    <?php echo escape($tt['day_of_week']); ?>
                                </td>
                                <td style="font-size: 12px; color: var(--text-muted); white-space: nowrap;">
                                    <?php echo date('h:i A', strtotime($tt['start_time'])); ?> – <?php echo date('h:i A', strtotime($tt['end_time'])); ?>
                                </td>
                                <td>
                                    <strong><?php echo escape($tt['subject_code']); ?></strong>
                                    <span style="display: block; font-size: 11px; color: var(--text-muted);"><?php echo escape($tt['subject_name']); ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-warning"><?php echo escape($tt['room_number']); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Recent Leave History Card -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Recent Leave Applications</h3>
        <a href="<?php echo BASE_URL; ?>admin/leaves/index.php" class="btn btn-secondary no-print" style="padding: 4px 10px; font-size: 12px;">
            All Leave Approvals
        </a>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Leave Type</th>
                    <th>Date Period</th>
                    <th>Days</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Reviewer Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentLeaves)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 20px;">
                            No leave applications on record.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentLeaves as $leave): ?>
                        <tr>
                            <td style="font-weight: 600;">
                                <?php echo escape($leave['leave_type']); ?>
                            </td>
                            <td style="font-size: 13px;">
                                <?php echo date('d M Y', strtotime($leave['start_date'])); ?> to <?php echo date('d M Y', strtotime($leave['end_date'])); ?>
                            </td>
                            <td><?php echo (int)$leave['days_count']; ?> days</td>
                            <td style="font-size: 13px; color: var(--text-muted); max-width: 250px;">
                                <?php echo escape($leave['reason']); ?>
                            </td>
                            <td>
                                <?php echo getStatusBadge($leave['status']); ?>
                            </td>
                            <td style="font-size: 12px; color: var(--text-muted);">
                                <?php echo escape($leave['admin_remarks'] ?? '—'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
