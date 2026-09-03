<?php
/**
 * Faculty Management System (FMS)
 * Faculty Sidebar Navigation Component
 */

$current_script = $_SERVER['PHP_SELF'] ?? '';
$active = $activeMenu ?? '';
$facName = $_SESSION['full_name'] ?? 'Faculty Member';
$facDept = $_SESSION['dept_name'] ?? 'Department';
$facEmpId = $_SESSION['emp_id'] ?? '';
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-logo-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                <path d="M6 12v5c3 3 9 3 12 0v-5"/>
            </svg>
        </div>
        <span>FMS &bull; Faculty Portal</span>
    </div>

    <ul class="sidebar-nav">
        <li class="nav-section-title">My Workspace</li>
        
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>faculty/dashboard.php" class="nav-link <?php echo ($active === 'dashboard' || strpos($current_script, 'dashboard.php') !== false) ? 'active' : ''; ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"/>
                    <rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/>
                    <rect x="3" y="14" width="7" height="7"/>
                </svg>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>faculty/profile.php" class="nav-link <?php echo ($active === 'profile' || strpos($current_script, 'profile.php') !== false) ? 'active' : ''; ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <span>My Profile</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>faculty/subjects.php" class="nav-link <?php echo ($active === 'subjects' || strpos($current_script, 'subjects.php') !== false) ? 'active' : ''; ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                </svg>
                <span>My Subjects</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>faculty/timetable.php" class="nav-link <?php echo ($active === 'timetable' || strpos($current_script, 'timetable.php') !== false) ? 'active' : ''; ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <span>My Timetable</span>
            </a>
        </li>

        <li class="nav-section-title">Attendance &amp; Leave</li>

        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>faculty/attendance.php" class="nav-link <?php echo ($active === 'attendance' || strpos($current_script, 'attendance.php') !== false) ? 'active' : ''; ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 11l3 3L22 4"/>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
                <span>Attendance Log</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>faculty/leave.php" class="nav-link <?php echo ($active === 'leave' || strpos($current_script, 'leave.php') !== false) ? 'active' : ''; ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10 9 9 9 8 9"/>
                </svg>
                <span>Apply &amp; Track Leave</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <div class="user-mini-card">
            <div class="user-avatar">
                <?php echo strtoupper(substr(escape($facName), 0, 2)); ?>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo escape($facName); ?></div>
                <div class="user-role"><?php echo escape($facEmpId); ?></div>
            </div>
            <a href="<?php echo BASE_URL; ?>logout.php" class="btn-action btn-action-danger" title="Sign Out" style="color: #F87171; border-color: rgba(255,255,255,0.15); background: rgba(0,0,0,0.2);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
            </a>
        </div>
    </div>
</aside>
