<?php
/**
 * Faculty Management System (FMS)
 * Admin Sidebar Navigation Component
 * Exactly matches the reference design hierarchy
 */

$current_script = $_SERVER['PHP_SELF'] ?? '';
$active = $activeMenu ?? '';

// Fetch pending leave count for navigation badge
$pendingLeaveCount = 0;
try {
    $db = getDB();
    $stmt = $db->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'Pending'");
    $pendingLeaveCount = (int)$stmt->fetchColumn();
} catch (Exception $e) {}
?>
<aside class="sidebar" id="appSidebar">
    <!-- Brand Logo -->
    <div class="sidebar-brand">
        <div class="sidebar-logo-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                <path d="M6 12v5c3 3 9 3 12 0v-5"/>
            </svg>
        </div>
        <div class="sidebar-brand-text">
            <span class="sidebar-brand-title">FMS</span>
            <span class="sidebar-brand-subtitle">Faculty Management System</span>
        </div>
    </div>

    <!-- Navigation Groups -->
    <ul class="sidebar-nav">
        <!-- MAIN -->
        <li class="nav-section-title">MAIN</li>
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="nav-link <?php echo ($active === 'dashboard' || strpos($current_script, 'dashboard.php') !== false) ? 'active' : ''; ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                <span>Dashboard</span>
            </a>
        </li>

        <!-- MANAGEMENT -->
        <li class="nav-section-title">MANAGEMENT</li>
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>admin/faculty/index.php" class="nav-link <?php echo ($active === 'faculty' || strpos($current_script, '/faculty/') !== false) ? 'active' : ''; ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <span>Faculty</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>admin/departments/index.php" class="nav-link <?php echo ($active === 'departments' || strpos($current_script, '/departments/') !== false) ? 'active' : ''; ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="4" y="2" width="16" height="20" rx="2" ry="2"/>
                    <line x1="9" y1="22" x2="9" y2="22.01"/>
                    <line x1="15" y1="22" x2="15" y2="22.01"/>
                    <line x1="9" y1="6" x2="9" y2="6.01"/>
                    <line x1="15" y1="6" x2="15" y2="6.01"/>
                    <line x1="9" y1="10" x2="9" y2="10.01"/>
                    <line x1="15" y1="10" x2="15" y2="10.01"/>
                    <line x1="9" y1="14" x2="9" y2="14.01"/>
                    <line x1="15" y1="14" x2="15" y2="14.01"/>
                </svg>
                <span>Departments</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>admin/subjects/index.php" class="nav-link <?php echo ($active === 'subjects' || strpos($current_script, '/subjects/') !== false) ? 'active' : ''; ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                </svg>
                <span>Subjects</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>admin/subjects/index.php" class="nav-link <?php echo ($active === 'assign_subjects') ? 'active' : ''; ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="8.5" cy="7" r="4"/>
                    <polyline points="17 11 19 13 23 9"/>
                </svg>
                <span>Assign Subjects</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>admin/timetable/index.php" class="nav-link <?php echo ($active === 'timetable' || strpos($current_script, '/timetable/') !== false) ? 'active' : ''; ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <span>Timetable</span>
            </a>
        </li>

        <!-- ATTENDANCE & LEAVE -->
        <li class="nav-section-title">ATTENDANCE &amp; LEAVE</li>
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>admin/attendance/index.php" class="nav-link <?php echo ($active === 'attendance' || strpos($current_script, '/attendance/') !== false) ? 'active' : ''; ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
                <span>Attendance</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>admin/leaves/index.php" class="nav-link <?php echo ($active === 'leaves' || strpos($current_script, '/leaves/') !== false) ? 'active' : ''; ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
                <span>Leave Requests</span>
                <?php if ($pendingLeaveCount > 0): ?>
                    <span class="nav-badge"><?php echo $pendingLeaveCount; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <!-- REPORTS -->
        <li class="nav-section-title">REPORTS</li>
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>admin/reports/index.php?type=faculty" class="nav-link <?php echo ($active === 'faculty_reports') ? 'active' : ''; ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 20V10"/>
                    <path d="M12 20V4"/>
                    <path d="M6 20v-6"/>
                </svg>
                <span>Faculty Reports</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>admin/reports/index.php?type=attendance" class="nav-link <?php echo ($active === 'attendance_reports') ? 'active' : ''; ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                </svg>
                <span>Attendance Reports</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>admin/reports/index.php?type=leaves" class="nav-link <?php echo ($active === 'leave_reports') ? 'active' : ''; ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <path d="M14 2v6h6"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                </svg>
                <span>Leave Reports</span>
            </a>
        </li>

        <!-- ACCOUNT -->
        <li class="nav-section-title">ACCOUNT</li>
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="nav-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <span>Profile</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="nav-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                <span>Change Password</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>logout.php" class="nav-link" style="color: #F87171;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#F87171" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</aside>
