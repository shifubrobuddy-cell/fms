<?php
/**
 * Faculty Management System (FMS)
 * Admin: Reports & Academic Analytics Engine
 * Supports On-Screen Dashboard, CSV Data Export, and Printable Transcripts
 */

require_once __DIR__ . '/../../includes/admin-auth.php';

$pageTitle = 'Institutional Reports & Analytics';
$activeMenu = 'reports';
$db = getDB();

$reportType = $_GET['report'] ?? 'workload'; // 'workload', 'attendance', 'department', 'leaves'
$deptFilter = (int)($_GET['department_id'] ?? 0);
$selectedMonth = $_GET['month'] ?? date('Y-m'); // YYYY-MM

// CSV Export Trigger
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    handleCSVExport($db, $reportType, $deptFilter, $selectedMonth);
    exit;
}

// Fetch departments for filter
$departments = $db->query("SELECT id, dept_code, dept_name FROM departments ORDER BY dept_code ASC")->fetchAll();

// Execute report query based on active tab
$reportData = [];

try {
    if ($reportType === 'workload') {
        // Faculty Teaching Load Report
        $whereDept = $deptFilter > 0 ? "WHERE f.department_id = " . $deptFilter : "";
        $sql = "
            SELECT f.id, f.full_name, f.emp_id, f.designation,
                   d.dept_code, d.dept_name,
                   COUNT(DISTINCT fs.subject_id) AS allocated_subjects_count,
                   COALESCE(SUM(s.credits), 0) AS total_credits_handled,
                   (
                       SELECT COUNT(*) 
                       FROM timetable t 
                       WHERE t.faculty_id = f.id
                   ) AS weekly_lecture_hours
            FROM faculty f
            JOIN departments d ON f.department_id = d.id
            LEFT JOIN faculty_subjects fs ON f.id = fs.faculty_id
            LEFT JOIN subjects s ON fs.subject_id = s.id
            {$whereDept}
            GROUP BY f.id
            ORDER BY d.dept_code ASC, f.full_name ASC
        ";
        $reportData = $db->query($sql)->fetchAll();

    } elseif ($reportType === 'attendance') {
        // Attendance Performance by Month
        $startDate = $selectedMonth . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $whereDept = $deptFilter > 0 ? "AND f.department_id = " . $deptFilter : "";
        $sql = "
            SELECT f.id, f.full_name, f.emp_id, f.designation,
                   d.dept_code, d.dept_name,
                   SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) AS present_count,
                   SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) AS late_count,
                   SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) AS absent_count,
                   SUM(CASE WHEN a.status = 'On-Leave' THEN 1 ELSE 0 END) AS leave_count,
                   COUNT(a.id) AS total_recorded_days
            FROM faculty f
            JOIN departments d ON f.department_id = d.id
            LEFT JOIN attendance a ON f.id = a.faculty_id AND a.attendance_date BETWEEN '{$startDate}' AND '{$endDate}'
            WHERE 1=1 {$whereDept}
            GROUP BY f.id
            ORDER BY d.dept_code ASC, f.full_name ASC
        ";
        $reportData = $db->query($sql)->fetchAll();

    } elseif ($reportType === 'department') {
        // Department Aggregate Metrics
        $sql = "
            SELECT d.id, d.dept_code, d.dept_name,
                   COUNT(DISTINCT f.id) AS faculty_count,
                   COUNT(DISTINCT s.id) AS subjects_count,
                   COALESCE(SUM(s.credits), 0) AS total_department_credits,
                   (
                       SELECT COUNT(*) 
                       FROM timetable t 
                       WHERE t.department_id = d.id
                   ) AS scheduled_lectures
            FROM departments d
            LEFT JOIN faculty f ON d.id = f.department_id
            LEFT JOIN subjects s ON d.id = s.department_id
            GROUP BY d.id
            ORDER BY d.dept_code ASC
        ";
        $reportData = $db->query($sql)->fetchAll();

    } elseif ($reportType === 'leaves') {
        // Leave Sanctions Summary
        $whereDept = $deptFilter > 0 ? "WHERE f.department_id = " . $deptFilter : "";
        $sql = "
            SELECT f.id, f.full_name, f.emp_id, f.designation,
                   d.dept_code, d.dept_name,
                   SUM(CASE WHEN lr.status = 'Approved' THEN lr.days_count ELSE 0 END) AS approved_days,
                   SUM(CASE WHEN lr.status = 'Pending' THEN 1 ELSE 0 END) AS pending_requests_count,
                   SUM(CASE WHEN lr.status = 'Rejected' THEN 1 ELSE 0 END) AS rejected_requests_count,
                   COUNT(lr.id) AS total_applications
            FROM faculty f
            JOIN departments d ON f.department_id = d.id
            LEFT JOIN leave_requests lr ON f.id = lr.faculty_id
            {$whereDept}
            GROUP BY f.id
            ORDER BY d.dept_code ASC, f.full_name ASC
        ";
        $reportData = $db->query($sql)->fetchAll();
    }
} catch (Exception $e) {
    error_log("Reports Error: " . $e->getMessage());
}

/**
 * Handle CSV File Generation and Instant Download
 */
function handleCSVExport($db, $reportType, $deptFilter, $selectedMonth) {
    $filename = "FMS_Report_" . ucfirst($reportType) . "_" . date('Ymd_His') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $out = fopen('php://output', 'w');

    if ($reportType === 'workload') {
        fputcsv($out, ['Emp ID', 'Faculty Name', 'Department', 'Designation', 'Courses Assigned', 'Credits Handled', 'Weekly Hours']);
        $whereDept = $deptFilter > 0 ? "WHERE f.department_id = " . $deptFilter : "";
        $sql = "
            SELECT f.emp_id, f.full_name, d.dept_code, f.designation,
                   COUNT(DISTINCT fs.subject_id) AS allocated_subjects_count,
                   COALESCE(SUM(s.credits), 0) AS total_credits_handled,
                   (SELECT COUNT(*) FROM timetable t WHERE t.faculty_id = f.id) AS weekly_lecture_hours
            FROM faculty f
            JOIN departments d ON f.department_id = d.id
            LEFT JOIN faculty_subjects fs ON f.id = fs.faculty_id
            LEFT JOIN subjects s ON fs.subject_id = s.id
            {$whereDept}
            GROUP BY f.id
            ORDER BY d.dept_code ASC, f.full_name ASC
        ";
        foreach ($db->query($sql)->fetchAll() as $r) {
            fputcsv($out, [$r['emp_id'], $r['full_name'], $r['dept_code'], $r['designation'], $r['allocated_subjects_count'], $r['total_credits_handled'], $r['weekly_lecture_hours']]);
        }
    } elseif ($reportType === 'attendance') {
        fputcsv($out, ['Emp ID', 'Faculty Name', 'Department', 'Designation', 'Month', 'Present Days', 'Late Days', 'Absent Days', 'Leave Days', 'Total Recorded', 'Attendance %']);
        $startDate = $selectedMonth . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        $whereDept = $deptFilter > 0 ? "AND f.department_id = " . $deptFilter : "";
        $sql = "
            SELECT f.emp_id, f.full_name, d.dept_code, f.designation,
                   SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) AS present_count,
                   SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) AS late_count,
                   SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) AS absent_count,
                   SUM(CASE WHEN a.status = 'On-Leave' THEN 1 ELSE 0 END) AS leave_count,
                   COUNT(a.id) AS total_recorded_days
            FROM faculty f
            JOIN departments d ON f.department_id = d.id
            LEFT JOIN attendance a ON f.id = a.faculty_id AND a.attendance_date BETWEEN '{$startDate}' AND '{$endDate}'
            WHERE 1=1 {$whereDept}
            GROUP BY f.id
            ORDER BY d.dept_code ASC, f.full_name ASC
        ";
        foreach ($db->query($sql)->fetchAll() as $r) {
            $total = (int)$r['total_recorded_days'];
            $pct = $total > 0 ? round((((int)$r['present_count'] + (int)$r['late_count']) / $total) * 100, 1) : 0;
            fputcsv($out, [$r['emp_id'], $r['full_name'], $r['dept_code'], $r['designation'], $selectedMonth, $r['present_count'], $r['late_count'], $r['absent_count'], $r['leave_count'], $total, $pct . '%']);
        }
    } elseif ($reportType === 'department') {
        fputcsv($out, ['Department Code', 'Department Name', 'Faculty Count', 'Subjects Count', 'Total Credits', 'Weekly Lectures']);
        $sql = "
            SELECT d.dept_code, d.dept_name,
                   COUNT(DISTINCT f.id) AS faculty_count,
                   COUNT(DISTINCT s.id) AS subjects_count,
                   COALESCE(SUM(s.credits), 0) AS total_department_credits,
                   (SELECT COUNT(*) FROM timetable t WHERE t.department_id = d.id) AS scheduled_lectures
            FROM departments d
            LEFT JOIN faculty f ON d.id = f.department_id
            LEFT JOIN subjects s ON d.id = s.department_id
            GROUP BY d.id
            ORDER BY d.dept_code ASC
        ";
        foreach ($db->query($sql)->fetchAll() as $r) {
            fputcsv($out, [$r['dept_code'], $r['dept_name'], $r['faculty_count'], $r['subjects_count'], $r['total_department_credits'], $r['scheduled_lectures']]);
        }
    } elseif ($reportType === 'leaves') {
        fputcsv($out, ['Emp ID', 'Faculty Name', 'Department', 'Approved Days', 'Pending Requests', 'Rejected Requests', 'Total Applications']);
        $whereDept = $deptFilter > 0 ? "WHERE f.department_id = " . $deptFilter : "";
        $sql = "
            SELECT f.emp_id, f.full_name, d.dept_code,
                   SUM(CASE WHEN lr.status = 'Approved' THEN lr.days_count ELSE 0 END) AS approved_days,
                   SUM(CASE WHEN lr.status = 'Pending' THEN 1 ELSE 0 END) AS pending_requests_count,
                   SUM(CASE WHEN lr.status = 'Rejected' THEN 1 ELSE 0 END) AS rejected_requests_count,
                   COUNT(lr.id) AS total_applications
            FROM faculty f
            JOIN departments d ON f.department_id = d.id
            LEFT JOIN leave_requests lr ON f.id = lr.faculty_id
            {$whereDept}
            GROUP BY f.id
            ORDER BY d.dept_code ASC, f.full_name ASC
        ";
        foreach ($db->query($sql)->fetchAll() as $r) {
            fputcsv($out, [$r['emp_id'], $r['full_name'], $r['dept_code'], $r['approved_days'] ?? 0, $r['pending_requests_count'], $r['rejected_requests_count'], $r['total_applications']]);
        }
    }
    fclose($out);
    exit;
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-header">
    <div>
        <h2 style="font-size: 20px; font-weight: 700; color: var(--text); margin: 0 0 4px;">Institutional Reports &amp; Analytics</h2>
        <p style="color: var(--text-muted); font-size: 14px; margin: 0;">Comprehensive audit logs, workload matrices, attendance sheets, and tabular data exports.</p>
    </div>
    <div style="display: flex; gap: 10px;" class="no-print">
        <a href="?report=<?php echo $reportType; ?>&department_id=<?php echo $deptFilter; ?>&month=<?php echo $selectedMonth; ?>&export=csv" class="btn btn-secondary" title="Export current report to CSV">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            Export to CSV
        </a>
        <button type="button" class="btn btn-primary" onclick="window.print();">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"/>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                <rect x="6" y="14" width="12" height="8"/>
            </svg>
            Print Formal Report
        </button>
    </div>
</div>

<!-- Report Navigation Tabs & Filters -->
<div class="card no-print" style="margin-bottom: 24px;">
    <div class="card-body" style="padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <!-- Report Types -->
        <div style="display: flex; gap: 6px; background: var(--bg); padding: 4px; border-radius: 8px; border: 1px solid var(--border); flex-wrap: wrap;">
            <a href="?report=workload&department_id=<?php echo $deptFilter; ?>" 
               style="padding: 6px 14px; font-size: 13px; font-weight: 600; text-decoration: none; border-radius: 6px; <?php echo ($reportType === 'workload') ? 'background: var(--surface); color: var(--primary-dark); box-shadow: 0 1px 2px rgba(0,0,0,0.06);' : 'color: var(--text-muted);'; ?>">
               Faculty Workload
            </a>
            <a href="?report=attendance&department_id=<?php echo $deptFilter; ?>&month=<?php echo $selectedMonth; ?>" 
               style="padding: 6px 14px; font-size: 13px; font-weight: 600; text-decoration: none; border-radius: 6px; <?php echo ($reportType === 'attendance') ? 'background: var(--surface); color: var(--primary-dark); box-shadow: 0 1px 2px rgba(0,0,0,0.06);' : 'color: var(--text-muted);'; ?>">
               Attendance Performance
            </a>
            <a href="?report=department" 
               style="padding: 6px 14px; font-size: 13px; font-weight: 600; text-decoration: none; border-radius: 6px; <?php echo ($reportType === 'department') ? 'background: var(--surface); color: var(--primary-dark); box-shadow: 0 1px 2px rgba(0,0,0,0.06);' : 'color: var(--text-muted);'; ?>">
               Department Distribution
            </a>
            <a href="?report=leaves&department_id=<?php echo $deptFilter; ?>" 
               style="padding: 6px 14px; font-size: 13px; font-weight: 600; text-decoration: none; border-radius: 6px; <?php echo ($reportType === 'leaves') ? 'background: var(--surface); color: var(--primary-dark); box-shadow: 0 1px 2px rgba(0,0,0,0.06);' : 'color: var(--text-muted);'; ?>">
               Leave Utilization
            </a>
        </div>

        <!-- Filter Form -->
        <form method="GET" action="" style="display: flex; gap: 10px; align-items: center;">
            <input type="hidden" name="report" value="<?php echo escape($reportType); ?>">

            <?php if ($reportType === 'attendance'): ?>
                <input type="month" 
                       name="month" 
                       class="form-control" 
                       value="<?php echo escape($selectedMonth); ?>" 
                       style="font-size: 13px; padding: 4px 8px; width: auto;" 
                       onchange="this.form.submit()">
            <?php endif; ?>

            <?php if ($reportType !== 'department'): ?>
                <select name="department_id" class="form-control" style="font-size: 13px; padding: 4px 10px; width: auto;" onchange="this.form.submit()">
                    <option value="0">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?php echo (int)$d['id']; ?>" <?php echo ($deptFilter === (int)$d['id']) ? 'selected' : ''; ?>>
                            <?php echo escape($d['dept_code']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Print Header (Visible on print only) -->
<div class="print-only" style="margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px;">
    <h2 style="margin: 0; font-size: 20px; text-transform: uppercase;"><?php echo escape(APP_NAME); ?></h2>
    <div style="font-size: 14px; margin-top: 4px;">
        Institutional Report: <?php echo ucfirst(escape($reportType)); ?> Audit &bull; Academic Year <?php echo escape(ACADEMIC_YEAR); ?>
    </div>
    <div style="font-size: 12px; color: #555;">Generated on <?php echo date('F j, Y \a\t h:i A'); ?></div>
</div>

<!-- Report Table Container -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <?php 
                if ($reportType === 'workload') echo "Faculty Workload & Teaching Hours Analysis";
                elseif ($reportType === 'attendance') echo "Monthly Attendance Summary for " . date('F Y', strtotime($selectedMonth . '-01'));
                elseif ($reportType === 'department') echo "Departmental Staffing & Curriculum Overview";
                elseif ($reportType === 'leaves') echo "Faculty Absence & Leave Utilization Summary";
            ?>
        </h3>
        <span style="font-size: 13px; color: var(--text-muted);"><?php echo count($reportData); ?> row(s) calculated</span>
    </div>

    <div class="table-responsive">
        <?php if ($reportType === 'workload'): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Faculty Member</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th style="text-align: center;">Assigned Courses</th>
                        <th style="text-align: center;">Total Credits</th>
                        <th style="text-align: center;">Weekly Lecture Hours</th>
                        <th style="text-align: right;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData as $row): 
                        $hours = (int)$row['weekly_lecture_hours'];
                        $loadStatus = ($hours >= 16) ? 'badge-danger' : (($hours >= 8) ? 'badge-success' : 'badge-warning');
                        $loadLabel = ($hours >= 16) ? 'High Load' : (($hours >= 8) ? 'Optimal' : 'Light Load');
                    ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600; color: var(--text);">
                                    <?php echo escape($row['full_name']); ?>
                                </div>
                                <div style="font-size: 12px; color: var(--text-muted);">
                                    <?php echo escape($row['emp_id']); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-info"><?php echo escape($row['dept_code']); ?></span>
                            </td>
                            <td><?php echo escape($row['designation']); ?></td>
                            <td style="text-align: center; font-weight: 600;">
                                <?php echo (int)$row['allocated_subjects_count']; ?>
                            </td>
                            <td style="text-align: center; font-weight: 600;">
                                <?php echo (int)$row['total_credits_handled']; ?> Cr
                            </td>
                            <td style="text-align: center; font-weight: 700; color: var(--primary-dark);">
                                <?php echo $hours; ?> hrs/week
                            </td>
                            <td style="text-align: right;">
                                <span class="badge <?php echo $loadStatus; ?>">
                                    <?php echo $loadLabel; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif ($reportType === 'attendance'): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Faculty Member</th>
                        <th>Dept</th>
                        <th style="text-align: center; color: var(--success);">Present</th>
                        <th style="text-align: center; color: var(--warning);">Late</th>
                        <th style="text-align: center; color: var(--danger);">Absent</th>
                        <th style="text-align: center; color: #6366F1;">On Leave</th>
                        <th style="text-align: center;">Working Days</th>
                        <th style="text-align: right;">Attendance %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData as $row): 
                        $present = (int)$row['present_count'];
                        $late = (int)$row['late_count'];
                        $absent = (int)$row['absent_count'];
                        $leave = (int)$row['leave_count'];
                        $total = (int)$row['total_recorded_days'];
                        $pct = ($total > 0) ? round((($present + $late) / $total) * 100, 1) : 0;
                        $pctBadge = ($pct >= 85) ? 'badge-success' : (($pct >= 75) ? 'badge-warning' : 'badge-danger');
                    ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600; color: var(--text);">
                                    <?php echo escape($row['full_name']); ?>
                                </div>
                                <div style="font-size: 12px; color: var(--text-muted);">
                                    <?php echo escape($row['emp_id']); ?>
                                </div>
                            </td>
                            <td><span class="badge badge-info"><?php echo escape($row['dept_code']); ?></span></td>
                            <td style="text-align: center; font-weight: 600; color: var(--success);"><?php echo $present; ?></td>
                            <td style="text-align: center; font-weight: 600; color: var(--warning);"><?php echo $late; ?></td>
                            <td style="text-align: center; font-weight: 600; color: var(--danger);"><?php echo $absent; ?></td>
                            <td style="text-align: center; font-weight: 600; color: #6366F1;"><?php echo $leave; ?></td>
                            <td style="text-align: center; font-weight: 600;"><?php echo $total; ?></td>
                            <td style="text-align: right;">
                                <span class="badge <?php echo $pctBadge; ?>" style="font-size: 12px;">
                                    <?php echo $pct; ?>%
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif ($reportType === 'department'): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Department Title</th>
                        <th style="text-align: center;">Active Faculty</th>
                        <th style="text-align: center;">Total Subjects</th>
                        <th style="text-align: center;">Curriculum Credits</th>
                        <th style="text-align: center;">Weekly Scheduled Slots</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData as $row): ?>
                        <tr>
                            <td>
                                <span class="badge badge-info" style="font-weight: 700;">
                                    <?php echo escape($row['dept_code']); ?>
                                </span>
                            </td>
                            <td style="font-weight: 600; color: var(--text);">
                                <?php echo escape($row['dept_name']); ?>
                            </td>
                            <td style="text-align: center; font-weight: 700;"><?php echo (int)$row['faculty_count']; ?></td>
                            <td style="text-align: center; font-weight: 700;"><?php echo (int)$row['subjects_count']; ?></td>
                            <td style="text-align: center; font-weight: 600;"><?php echo (int)$row['total_department_credits']; ?> Cr</td>
                            <td style="text-align: center; font-weight: 700; color: var(--primary-dark);">
                                <?php echo (int)$row['scheduled_lectures']; ?> slots
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif ($reportType === 'leaves'): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Faculty Member</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th style="text-align: center;">Approved Leave Days</th>
                        <th style="text-align: center;">Pending Requests</th>
                        <th style="text-align: center;">Rejected Requests</th>
                        <th style="text-align: right;">Total Applications</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData as $row): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600; color: var(--text);">
                                    <?php echo escape($row['full_name']); ?>
                                </div>
                                <div style="font-size: 12px; color: var(--text-muted);">
                                    <?php echo escape($row['emp_id']); ?>
                                </div>
                            </td>
                            <td><span class="badge badge-info"><?php echo escape($row['dept_code']); ?></span></td>
                            <td><?php echo escape($row['designation']); ?></td>
                            <td style="text-align: center; font-weight: 700; color: var(--primary-dark);">
                                <?php echo (int)($row['approved_days'] ?? 0); ?> days
                            </td>
                            <td style="text-align: center;">
                                <?php if ((int)$row['pending_requests_count'] > 0): ?>
                                    <span class="badge badge-warning"><?php echo (int)$row['pending_requests_count']; ?> Pending</span>
                                <?php else: ?>
                                    0
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;"><?php echo (int)$row['rejected_requests_count']; ?></td>
                            <td style="text-align: right; font-weight: 600;"><?php echo (int)$row['total_applications']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
