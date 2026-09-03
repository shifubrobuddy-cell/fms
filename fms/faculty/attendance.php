<?php
/**
 * Faculty Management System (FMS)
 * Faculty: Personal Attendance History & Monthly Log
 */

require_once __DIR__ . '/../includes/faculty-auth.php';

$pageTitle = 'My Attendance Log';
$activeMenu = 'attendance';
$db = getDB();

$facultyId = (int)$_SESSION['faculty_id'];
$selectedMonth = $_GET['month'] ?? date('Y-m');

if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
    $selectedMonth = date('Y-m');
}

$startDate = $selectedMonth . '-01';
$endDate = date('Y-m-t', strtotime($startDate));

// Fetch attendance records for this month
$records = [];
try {
    $stmt = $db->prepare("
        SELECT * FROM attendance 
        WHERE faculty_id = ? AND attendance_date BETWEEN ? AND ?
        ORDER BY attendance_date DESC
    ");
    $stmt->execute([$facultyId, $startDate, $endDate]);
    $records = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Faculty Attendance Error: " . $e->getMessage());
}

// Compute statistics
$presentCount = 0;
$lateCount = 0;
$absentCount = 0;
$leaveCount = 0;
$totalDays = count($records);

foreach ($records as $r) {
    if ($r['status'] === 'Present') $presentCount++;
    elseif ($r['status'] === 'Late') $lateCount++;
    elseif ($r['status'] === 'Absent') $absentCount++;
    elseif ($r['status'] === 'On-Leave') $leaveCount++;
}

$effectivePresent = $presentCount + $lateCount;
$attendancePct = ($totalDays > 0) ? round(($effectivePresent / $totalDays) * 100, 1) : 100;

include __DIR__ . '/../includes/header.php';
?>

<div class="content-header">
    <div>
        <h2 style="font-size: 20px; font-weight: 700; color: var(--text); margin: 0 0 4px;">My Attendance Log</h2>
        <p style="color: var(--text-muted); font-size: 14px; margin: 0;">Monthly presence register, biometric punch timings, and approved leave records.</p>
    </div>
    <div class="no-print">
        <button type="button" class="btn btn-secondary" onclick="window.print();">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"/>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                <rect x="6" y="14" width="12" height="8"/>
            </svg>
            Print Monthly Slip
        </button>
    </div>
</div>

<!-- Month Filter Bar -->
<div class="card no-print" style="margin-bottom: 24px;">
    <div class="card-body" style="padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
        <div style="font-weight: 600; color: var(--text);">
            Viewing Period: <?php echo date('F Y', strtotime($startDate)); ?>
        </div>

        <form method="GET" action="" style="display: flex; gap: 10px; align-items: center;">
            <label style="font-size: 13px; color: var(--text-muted);">Select Month:</label>
            <input type="month" 
                   name="month" 
                   class="form-control" 
                   value="<?php echo escape($selectedMonth); ?>" 
                   max="<?php echo date('Y-m'); ?>"
                   style="font-size: 13px; padding: 4px 8px; width: auto;"
                   onchange="this.form.submit()">
            <button type="submit" class="btn btn-secondary" style="height: 36px; font-size: 13px;">
                View Log
            </button>
        </form>
    </div>
</div>

<!-- Attendance Metrics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Present Days</div>
            <div class="stat-value" style="color: var(--success);"><?php echo $presentCount; ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--warning);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Late Arrivals</div>
            <div class="stat-value" style="color: var(--warning);"><?php echo $lateCount; ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: var(--danger);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Absences / Leaves</div>
            <div class="stat-value"><?php echo $absentCount; ?> / <?php echo $leaveCount; ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(13, 148, 136, 0.1); color: var(--accent);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Monthly Rate</div>
            <div class="stat-value"><?php echo $attendancePct; ?>%</div>
        </div>
    </div>
</div>

<!-- Attendance Record Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Day-by-Day Activity Register</h3>
        <span style="font-size: 13px; color: var(--text-muted);"><?php echo $totalDays; ?> recorded session(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 140px;">Date</th>
                    <th style="width: 110px;">Day</th>
                    <th style="width: 130px;">Status</th>
                    <th style="width: 110px;">In-Time</th>
                    <th style="width: 110px;">Out-Time</th>
                    <th>Official Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">
                            No attendance records posted for <?php echo date('F Y', strtotime($startDate)); ?>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $row): 
                        $badgeClass = 'badge-success';
                        if ($row['status'] === 'Absent') $badgeClass = 'badge-danger';
                        elseif ($row['status'] === 'Late') $badgeClass = 'badge-warning';
                        elseif ($row['status'] === 'On-Leave') $badgeClass = 'badge-info';
                    ?>
                        <tr>
                            <td>
                                <strong style="font-size: 13px; color: var(--text);">
                                    <?php echo date('M j, Y', strtotime($row['attendance_date'])); ?>
                                </strong>
                            </td>
                            <td>
                                <span style="font-size: 13px; color: var(--text-muted);">
                                    <?php echo date('l', strtotime($row['attendance_date'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $badgeClass; ?>" style="font-weight: 600;">
                                    <?php echo escape($row['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo !empty($row['in_time']) ? date('h:i A', strtotime($row['in_time'])) : '<span style="color:var(--text-muted);">&ndash;</span>'; ?>
                            </td>
                            <td>
                                <?php echo !empty($row['out_time']) ? date('h:i A', strtotime($row['out_time'])) : '<span style="color:var(--text-muted);">&ndash;</span>'; ?>
                            </td>
                            <td>
                                <?php if (!empty($row['remarks'])): ?>
                                    <span style="font-size: 13px; color: var(--text);"><?php echo escape($row['remarks']); ?></span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 12px;">Regular duty</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
