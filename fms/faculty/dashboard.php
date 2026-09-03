<?php
/**
 * Faculty Management System (FMS)
 * Faculty: Personal Workspace Dashboard
 */

require_once __DIR__ . '/../includes/faculty-auth.php';

$pageTitle = 'Faculty Workspace';
$activeMenu = 'dashboard';
$db = getDB();

$facultyId = (int)$_SESSION['faculty_id'];

// 1. Fetch Faculty Profile Meta
$faculty = [];
try {
    $fStmt = $db->prepare("
        SELECT f.*, d.dept_code, d.dept_name 
        FROM faculty f
        JOIN departments d ON f.department_id = d.id
        WHERE f.id = ?
    ");
    $fStmt->execute([$facultyId]);
    $faculty = $fStmt->fetch();
} catch (Exception $e) {
    error_log("Faculty Dashboard Profile Error: " . $e->getMessage());
}

// 2. Fetch Allocated Subjects Count
$allocatedCount = 0;
try {
    $subStmt = $db->prepare("SELECT COUNT(*) FROM faculty_subjects WHERE faculty_id = ?");
    $subStmt->execute([$facultyId]);
    $allocatedCount = (int)$subStmt->fetchColumn();
} catch (Exception $e) {}

// 3. Fetch Today's Lectures
$todayName = date('l'); // 'Monday', 'Tuesday', etc.
$todayLectures = [];
try {
    $ttStmt = $db->prepare("
        SELECT t.*, s.subject_code, s.subject_name, d.dept_code
        FROM timetable t
        JOIN subjects s ON t.subject_id = s.id
        JOIN departments d ON t.department_id = d.id
        WHERE t.faculty_id = ? AND t.day_of_week = ?
        ORDER BY t.start_time ASC
    ");
    $ttStmt->execute([$facultyId, $todayName]);
    $todayLectures = $ttStmt->fetchAll();
} catch (Exception $e) {}

// 4. Monthly Attendance Stats
$currentMonth = date('Y-m');
$firstDay = $currentMonth . '-01';
$lastDay = date('Y-m-t', strtotime($firstDay));
$attendanceSummary = ['Present' => 0, 'Late' => 0, 'Absent' => 0, 'On-Leave' => 0, 'Total' => 0];

try {
    $attStmt = $db->prepare("
        SELECT status, COUNT(*) AS cnt 
        FROM attendance 
        WHERE faculty_id = ? AND attendance_date BETWEEN ? AND ?
        GROUP BY status
    ");
    $attStmt->execute([$facultyId, $firstDay, $lastDay]);
    $attRows = $attStmt->fetchAll();
    foreach ($attRows as $r) {
        if (isset($attendanceSummary[$r['status']])) {
            $attendanceSummary[$r['status']] = (int)$r['cnt'];
        }
        $attendanceSummary['Total'] += (int)$r['cnt'];
    }
} catch (Exception $e) {}

$attendancePct = ($attendanceSummary['Total'] > 0)
    ? round((($attendanceSummary['Present'] + $attendanceSummary['Late']) / $attendanceSummary['Total']) * 100, 1)
    : 100;

// 5. Recent Leave Applications
$recentLeaves = [];
try {
    $lStmt = $db->prepare("
        SELECT * FROM leave_requests 
        WHERE faculty_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $lStmt->execute([$facultyId]);
    $recentLeaves = $lStmt->fetchAll();
} catch (Exception $e) {}

include __DIR__ . '/../includes/header.php';
?>

<!-- Welcome Banner -->
<div class="card" style="margin-bottom: 24px; background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%); color: #fff; border: none; overflow: hidden; position: relative;">
    <div style="position: absolute; right: -30px; top: -30px; opacity: 0.05; pointer-events: none;">
        <svg width="240" height="240" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.5">
            <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
            <path d="M6 12v5c3 3 9 3 12 0v-5"/>
        </svg>
    </div>
    <div class="card-body" style="padding: 24px 28px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px; position: relative; z-index: 1;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 56px; height: 56px; border-radius: 12px; background: var(--primary-light); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 20px; color: #fff; border: 2px solid rgba(255,255,255,0.2); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2);">
                <?php echo strtoupper(substr(escape($faculty['full_name'] ?? 'Faculty'), 0, 2)); ?>
            </div>
            <div>
                <h2 style="margin: 0; font-size: 22px; font-weight: 700; color: #fff;">
                    Welcome, <?php echo escape($faculty['full_name'] ?? 'Faculty Member'); ?>
                </h2>
                <div style="font-size: 13px; color: #94A3B8; margin-top: 4px;">
                    <?php echo escape($faculty['designation'] ?? 'Faculty'); ?> &bull; 
                    Department of <?php echo escape($faculty['dept_name'] ?? ''); ?> (<?php echo escape($faculty['dept_code'] ?? ''); ?>) &bull;
                    <span style="color: #38BDF8; font-weight: 600;">ID: <?php echo escape($faculty['emp_id'] ?? ''); ?></span>
                </div>
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="<?php echo BASE_URL; ?>faculty/leave.php" class="btn btn-primary" style="background: var(--accent); border-color: var(--accent);">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Apply for Leave
            </a>
            <a href="<?php echo BASE_URL; ?>faculty/profile.php" class="btn btn-secondary" style="background: rgba(255,255,255,0.1); color: #fff; border-color: rgba(255,255,255,0.2);">
                My Profile
            </a>
        </div>
    </div>
</div>

<!-- Stat Cards -->
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
            <div class="stat-value"><?php echo $allocatedCount; ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(2, 132, 199, 0.1); color: #0284C7;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Today's Lectures</div>
            <div class="stat-value"><?php echo count($todayLectures); ?> Period(s)</div>
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
            <div class="stat-label">Monthly Attendance</div>
            <div class="stat-value"><?php echo $attendancePct; ?>%</div>
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
            <div class="stat-label">Recent Leaves</div>
            <div class="stat-value"><?php echo count($recentLeaves); ?> Recorded</div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 3fr 2fr; gap: 24px; align-items: start;">
    <!-- Today's Lecture Schedule -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Today's Teaching Schedule (<?php echo $todayName; ?>)</h3>
                <span style="font-size: 13px; color: var(--text-muted);"><?php echo date('F j, Y'); ?></span>
            </div>
            <a href="<?php echo BASE_URL; ?>faculty/timetable.php" class="btn-action" style="font-size: 12px; height: auto; padding: 4px 10px;">
                Full Timetable &rarr;
            </a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Time Slot</th>
                        <th>Course</th>
                        <th>Class</th>
                        <th>Room</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($todayLectures)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                No lectures scheduled for today. Have a productive day!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($todayLectures as $slot): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--primary-dark); font-size: 13px;">
                                        <?php echo date('h:i A', strtotime($slot['start_time'])); ?> &ndash; <?php echo date('h:i A', strtotime($slot['end_time'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--text);">
                                        <?php echo escape($slot['subject_code']); ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--text-muted);">
                                        <?php echo escape($slot['subject_name']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo escape($slot['dept_code']); ?> Sem <?php echo (int)$slot['semester']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-warning" style="font-weight: 600;">
                                        Room <?php echo escape($slot['room_number']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Leave Requests -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Recent Leave Applications</h3>
                <span style="font-size: 13px; color: var(--text-muted);">Status tracking</span>
            </div>
            <a href="<?php echo BASE_URL; ?>faculty/leave.php" class="btn-action" style="font-size: 12px; height: auto; padding: 4px 10px;">
                Manage &rarr;
            </a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Type &amp; Period</th>
                        <th>Days</th>
                        <th style="text-align: right;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentLeaves)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                No leave applications submitted yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentLeaves as $l): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600; color: var(--text);">
                                        <?php echo escape($l['leave_type']); ?>
                                    </div>
                                    <div style="font-size: 11px; color: var(--text-muted);">
                                        <?php echo date('M j', strtotime($l['start_date'])); ?> &ndash; <?php echo date('M j, Y', strtotime($l['end_date'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo (int)$l['days_count']; ?></strong> <span style="font-size: 11px; color: var(--text-muted);">day(s)</span>
                                </td>
                                <td style="text-align: right;">
                                    <?php if ($l['status'] === 'Approved'): ?>
                                        <span class="badge badge-success">Approved</span>
                                    <?php elseif ($l['status'] === 'Pending'): ?>
                                        <span class="badge badge-warning">Pending</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Rejected</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
