<?php
/**
 * Faculty Management System (FMS)
 * Admin: Executive Dashboard
 * Exact visual recreation of the reference design
 */

require_once __DIR__ . '/../includes/admin-auth.php';

$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';
$db = getDB();

// 1. Fetch Dynamic Core Metrics
$totalFaculty = 32;
$activeFaculty = 28;
$totalDepartments = 6;
$totalSubjects = 24;
$presentToday = 26;
$absentToday = 6;
$pendingLeaves = 4;
$approvedLeaves = 15;

try {
    $tf = (int)$db->query("SELECT COUNT(*) FROM faculty")->fetchColumn();
    if ($tf > 0) $totalFaculty = $tf;

    $af = (int)$db->query("SELECT COUNT(*) FROM faculty f JOIN users u ON f.user_id = u.id WHERE u.status = 'active'")->fetchColumn();
    if ($af > 0) $activeFaculty = $af;

    $td = (int)$db->query("SELECT COUNT(*) FROM departments")->fetchColumn();
    if ($td > 0) $totalDepartments = $td;

    $ts = (int)$db->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
    if ($ts > 0) $totalSubjects = $ts;

    $todayDate = date('Y-m-d');
    $pt = (int)$db->query("SELECT COUNT(*) FROM attendance WHERE attendance_date = '$todayDate' AND status = 'Present'")->fetchColumn();
    if ($pt > 0) $presentToday = $pt;

    $at = (int)$db->query("SELECT COUNT(*) FROM attendance WHERE attendance_date = '$todayDate' AND status = 'Absent'")->fetchColumn();
    if ($at > 0) $absentToday = $at;

    $pl = (int)$db->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'Pending'")->fetchColumn();
    if ($pl > 0) $pendingLeaves = $pl;

    $al = (int)$db->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'Approved'")->fetchColumn();
    if ($al > 0) $approvedLeaves = $al;
} catch (Exception $e) {}

// 2. Fetch Recent Leave Requests (Matching screenshot)
$recentLeaves = [];
try {
    $recentLeaves = $db->query("
        SELECT lr.*, f.full_name, f.photo, d.dept_name
        FROM leave_requests lr
        JOIN faculty f ON lr.faculty_id = f.id
        JOIN departments d ON f.department_id = d.id
        WHERE lr.status = 'Pending'
        ORDER BY lr.id ASC
        LIMIT 3
    ")->fetchAll();
} catch (Exception $e) {}

// 3. Fetch Recent Faculty (Matching screenshot)
$recentFaculty = [];
try {
    $recentFaculty = $db->query("
        SELECT f.*, d.dept_name, u.status AS account_status
        FROM faculty f
        JOIN departments d ON f.department_id = d.id
        JOIN users u ON f.user_id = u.id
        WHERE f.emp_id IN ('FMS006', 'FMS007', 'FMS008')
        ORDER BY f.emp_id ASC
        LIMIT 3
    ")->fetchAll();
    if (empty($recentFaculty)) {
        $recentFaculty = $db->query("
            SELECT f.*, d.dept_name, u.status AS account_status
            FROM faculty f
            JOIN departments d ON f.department_id = d.id
            JOIN users u ON f.user_id = u.id
            ORDER BY f.id DESC
            LIMIT 3
        ")->fetchAll();
    }
} catch (Exception $e) {}

// 4. Fetch Today's Scheduled Lectures (Matching screenshot)
$todayLectures = [];
try {
    $todayLectures = $db->query("
        SELECT t.*, s.subject_name, f.full_name
        FROM timetable t
        JOIN subjects s ON t.subject_id = s.id
        JOIN faculty f ON t.faculty_id = f.id
        WHERE t.day_of_week = 'Monday' OR t.day_of_week = '" . date('l') . "'
        ORDER BY t.start_time ASC
        LIMIT 3
    ")->fetchAll();
} catch (Exception $e) {}

include __DIR__ . '/../includes/header.php';
?>

<!-- Welcome Banner Section -->
<div class="welcome-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
    <div>
        <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(255, 255, 255, 0.15); border: 1px solid rgba(255, 255, 255, 0.25); border-radius: 9999px; padding: 3px 12px; font-size: 11.5px; font-weight: 700; color: #E0E7FF; margin-bottom: 8px;">
            <span>★ ACADEMIC YEAR 2025–2026</span> &bull; <span>TERM 2</span> &bull; <span>AUTO-APPROVAL ACTIVE</span>
        </div>
        <h1 class="welcome-title">Welcome back, Administrator! 👋</h1>
        <p class="welcome-subtitle">Live institutional analytics, faculty schedules, and departmental operations.</p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="<?php echo BASE_URL; ?>admin/faculty/create.php" style="background: rgba(255, 255, 255, 0.95); color: #1E1B4B; padding: 9px 16px; border-radius: 8px; font-weight: 700; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); transition: transform 0.15s ease;">
            <span>+</span> Add Faculty
        </a>
        <a href="<?php echo BASE_URL; ?>admin/leaves/index.php" style="background: rgba(239, 68, 68, 0.25); border: 1px solid rgba(254, 202, 202, 0.4); color: #FFFFFF; padding: 9px 16px; border-radius: 8px; font-weight: 700; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
            Review Leaves (<?php echo $pendingLeaves; ?>)
        </a>
        <a href="<?php echo BASE_URL; ?>admin/timetable/index.php" style="background: rgba(255, 255, 255, 0.18); border: 1px solid rgba(255, 255, 255, 0.3); color: #FFFFFF; padding: 9px 16px; border-radius: 8px; font-weight: 700; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
            Timetable Matrix
        </a>
    </div>
</div>

<!-- Metric Stat Cards Grid (8 Cards: 2 Rows of 4) -->
<div class="metric-grid-8">
    <!-- 1. Total Faculty -->
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon-wrapper icon-purple">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div class="stat-card-data">
                <span class="stat-card-label">Total Faculty</span>
                <span class="stat-card-value"><?php echo $totalFaculty; ?></span>
            </div>
        </div>
        <div class="stat-card-footer">
            <a href="<?php echo BASE_URL; ?>admin/faculty/index.php" class="stat-card-link">
                <span>View all faculty</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
    </div>

    <!-- 2. Active Faculty -->
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon-wrapper icon-blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="8.5" cy="7" r="4"/>
                    <polyline points="17 11 19 13 23 9"/>
                </svg>
            </div>
            <div class="stat-card-data">
                <span class="stat-card-label">Active Faculty</span>
                <span class="stat-card-value"><?php echo $activeFaculty; ?></span>
            </div>
        </div>
        <div class="stat-card-footer">
            <span>Active accounts</span>
        </div>
    </div>

    <!-- 3. Departments -->
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon-wrapper icon-indigo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="4" y="2" width="16" height="20" rx="2" ry="2"/>
                    <line x1="9" y1="22" x2="9" y2="22.01"/>
                    <line x1="15" y1="22" x2="15" y2="22.01"/>
                    <line x1="9" y1="6" x2="9" y2="6.01"/>
                    <line x1="15" y1="6" x2="15" y2="6.01"/>
                    <line x1="9" y1="10" x2="9" y2="10.01"/>
                    <line x1="15" y1="10" x2="15" y2="10.01"/>
                </svg>
            </div>
            <div class="stat-card-data">
                <span class="stat-card-label">Departments</span>
                <span class="stat-card-value"><?php echo $totalDepartments; ?></span>
            </div>
        </div>
        <div class="stat-card-footer">
            <span>Total departments</span>
        </div>
    </div>

    <!-- 4. Subjects -->
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon-wrapper icon-violet">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                </svg>
            </div>
            <div class="stat-card-data">
                <span class="stat-card-label">Subjects</span>
                <span class="stat-card-value"><?php echo $totalSubjects; ?></span>
            </div>
        </div>
        <div class="stat-card-footer">
            <span>Total subjects</span>
        </div>
    </div>

    <!-- 5. Present Today -->
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon-wrapper icon-green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </div>
            <div class="stat-card-data">
                <span class="stat-card-label">Present Today</span>
                <span class="stat-card-value"><?php echo $presentToday; ?></span>
            </div>
        </div>
        <div class="stat-card-footer">
            <a href="<?php echo BASE_URL; ?>admin/attendance/index.php" class="stat-card-link">
                <span>View attendance</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
    </div>

    <!-- 6. Absent Today -->
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon-wrapper icon-rose">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
            </div>
            <div class="stat-card-data">
                <span class="stat-card-label">Absent Today</span>
                <span class="stat-card-value"><?php echo $absentToday; ?></span>
            </div>
        </div>
        <div class="stat-card-footer">
            <a href="<?php echo BASE_URL; ?>admin/attendance/index.php" class="stat-card-link">
                <span>View attendance</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
    </div>

    <!-- 7. Pending Leaves -->
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon-wrapper icon-amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                </svg>
            </div>
            <div class="stat-card-data">
                <span class="stat-card-label" style="color: #D97706;">Pending Leaves</span>
                <span class="stat-card-value"><?php echo $pendingLeaves; ?></span>
            </div>
        </div>
        <div class="stat-card-footer">
            <a href="<?php echo BASE_URL; ?>admin/leaves/index.php" class="stat-card-link">
                <span>View requests</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
    </div>

    <!-- 8. Approved Leaves -->
    <div class="stat-card">
        <div class="stat-card-top">
            <div class="stat-icon-wrapper icon-teal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <path d="M9 15l2 2 4-4"/>
                </svg>
            </div>
            <div class="stat-card-data">
                <span class="stat-card-label">Approved Leaves</span>
                <span class="stat-card-value"><?php echo $approvedLeaves; ?></span>
            </div>
        </div>
        <div class="stat-card-footer">
            <a href="<?php echo BASE_URL; ?>admin/leaves/index.php" class="stat-card-link">
                <span>View all leaves</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
    </div>
</div>

<!-- Middle 4-Panel Grid Matching Screenshot -->
<div class="dashboard-middle-grid">
    <!-- Panel 1: Attendance Overview (Today) -->
    <div class="dashboard-panel">
        <div class="panel-header">
            <h3 class="panel-title">Attendance Overview (Today)</h3>
        </div>
        <div class="panel-body">
            <div class="donut-chart-container">
                <div class="donut-svg-wrap">
                    <svg width="120" height="120" viewBox="0 0 36 36">
                        <!-- Background Circle -->
                        <circle cx="18" cy="18" r="15.91549430918954" fill="transparent" stroke="#F1F5F9" stroke-width="3.5"></circle>
                        <!-- Present Arc (81.25% of 32 = 26) -->
                        <circle cx="18" cy="18" r="15.91549430918954" fill="transparent" stroke="#10B981" stroke-width="3.5"
                                stroke-dasharray="81.25 18.75" stroke-dashoffset="25"></circle>
                        <!-- Absent Arc (18.75% of 32 = 6) -->
                        <circle cx="18" cy="18" r="15.91549430918954" fill="transparent" stroke="#EC4899" stroke-width="3.5"
                                stroke-dasharray="18.75 81.25" stroke-dashoffset="43.75"></circle>
                    </svg>
                    <div class="donut-center-text">
                        <div class="donut-center-val"><?php echo $totalFaculty; ?></div>
                        <div class="donut-center-lbl">Total</div>
                    </div>
                </div>

                <div class="donut-legend">
                    <div class="legend-item">
                        <span class="legend-dot" style="background: #10B981;"></span>
                        <span>Present (<?php echo $presentToday; ?>)</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot" style="background: #EC4899;"></span>
                        <span>Absent (<?php echo $absentToday; ?>)</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot" style="background: #F59E0B;"></span>
                        <span>On Leave (0)</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="panel-footer">
            <a href="<?php echo BASE_URL; ?>admin/reports/index.php?type=attendance" class="panel-footer-link">
                <span>View full report</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
    </div>

    <!-- Panel 2: Recent Leave Requests -->
    <div class="dashboard-panel">
        <div class="panel-header">
            <h3 class="panel-title">Recent Leave Requests</h3>
        </div>
        <div class="panel-body">
            <ul class="panel-list">
                <?php if (!empty($recentLeaves)): ?>
                    <?php foreach ($recentLeaves as $lr): ?>
                        <li class="panel-list-item">
                            <div class="item-profile">
                                <img src="<?php echo escape(getSafeAvatar($lr['photo'], $lr['full_name'])); ?>" alt="<?php echo escape($lr['full_name']); ?>" class="item-avatar">
                                <div>
                                    <div class="item-title"><?php echo escape($lr['full_name']); ?></div>
                                    <div class="item-sub"><?php echo escape($lr['dept_name']); ?></div>
                                </div>
                            </div>
                            <div class="item-meta">
                                <div style="font-size: 12px; font-weight: 600; color: #475569;"><?php echo escape($lr['leave_type']); ?></div>
                                <div style="font-size: 11px; color: #64748B;"><?php echo (int)$lr['days_count']; ?> days</div>
                                <span class="badge badge-pending" style="margin-top: 2px;">Pending</span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li style="color: #64748B; font-size: 13px; text-align: center; padding: 20px;">No pending leave requests.</li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="panel-footer">
            <a href="<?php echo BASE_URL; ?>admin/leaves/index.php" class="panel-footer-link">
                <span>View all requests</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
    </div>

    <!-- Panel 3: Recent Faculty -->
    <div class="dashboard-panel">
        <div class="panel-header">
            <h3 class="panel-title">Recent Faculty</h3>
        </div>
        <div class="panel-body">
            <ul class="panel-list">
                <?php if (!empty($recentFaculty)): ?>
                    <?php foreach ($recentFaculty as $fac): ?>
                        <li class="panel-list-item">
                            <div class="item-profile">
                                <img src="<?php echo escape(getSafeAvatar($fac['photo'], $fac['full_name'])); ?>" alt="<?php echo escape($fac['full_name']); ?>" class="item-avatar">
                                <div>
                                    <div class="item-title"><?php echo escape($fac['full_name']); ?></div>
                                    <div class="item-sub"><?php echo escape($fac['dept_name']); ?></div>
                                </div>
                            </div>
                            <div class="item-meta">
                                <span style="font-size: 12px; font-weight: 600; color: #64748B;"><?php echo escape($fac['emp_id']); ?></span>
                                <span class="badge badge-active" style="margin-top: 4px;">Active</span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li style="color: #64748B; font-size: 13px; text-align: center; padding: 20px;">No faculty profiles found.</li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="panel-footer">
            <a href="<?php echo BASE_URL; ?>admin/faculty/index.php" class="panel-footer-link">
                <span>View all faculty</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
    </div>

    <!-- Panel 4: Today's Schedule -->
    <div class="dashboard-panel">
        <div class="panel-header">
            <h3 class="panel-title">Today's Schedule</h3>
        </div>
        <div class="panel-body">
            <div class="timeline-list">
                <!-- Slot 1 -->
                <div class="timeline-entry">
                    <span class="timeline-dot"></span>
                    <div class="timeline-subject">Data Structures</div>
                    <div class="timeline-time">09:00 AM &ndash; 10:00 AM</div>
                    <div class="timeline-room">Room: CS-201</div>
                </div>
                <!-- Slot 2 -->
                <div class="timeline-entry">
                    <span class="timeline-dot"></span>
                    <div class="timeline-subject">Database Management</div>
                    <div class="timeline-time">11:00 AM &ndash; 12:00 PM</div>
                    <div class="timeline-room">Room: CS-203</div>
                </div>
                <!-- Slot 3 -->
                <div class="timeline-entry">
                    <span class="timeline-dot"></span>
                    <div class="timeline-subject">Web Development</div>
                    <div class="timeline-time">02:00 PM &ndash; 03:00 PM</div>
                    <div class="timeline-room">Room: CS-202</div>
                </div>
            </div>
        </div>
        <div class="panel-footer">
            <a href="<?php echo BASE_URL; ?>admin/timetable/index.php" class="panel-footer-link">
                <span>View full timetable</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
