<?php
/**
 * Faculty Management System (FMS)
 * Admin: Daily Faculty Attendance Tracker & Bulk Marking Register
 */

require_once __DIR__ . '/../../includes/admin-auth.php';

$pageTitle = 'Faculty Attendance Tracker';
$activeMenu = 'attendance';
$db = getDB();

$selectedDate = $_GET['date'] ?? date('Y-m-d');
// Validate date format Y-m-d
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = date('Y-m-d');
}

$deptFilter = (int)($_GET['department_id'] ?? 0);

// Fetch departments for filter
$departments = $db->query("SELECT id, dept_code, dept_name FROM departments ORDER BY dept_code ASC")->fetchAll();

// Handle Save Attendance POST
$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        $errors[] = 'Invalid security session. Please submit again.';
    }

    $postDate = $_POST['attendance_date'] ?? $selectedDate;
    $facultyRecords = $_POST['attendance'] ?? []; // array of [status, in_time, out_time, remarks] indexed by faculty_id
    $currentAdminId = (int)($_SESSION['user_id'] ?? 1);

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $checkStmt = $db->prepare("SELECT id FROM attendance WHERE faculty_id = ? AND attendance_date = ?");
            $insertStmt = $db->prepare("
                INSERT INTO attendance (faculty_id, attendance_date, status, in_time, out_time, remarks, recorded_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $updateStmt = $db->prepare("
                UPDATE attendance 
                SET status = ?, in_time = ?, out_time = ?, remarks = ?, recorded_by = ?
                WHERE id = ?
            ");

            $savedCount = 0;
            foreach ($facultyRecords as $facId => $row) {
                $facId = (int)$facId;
                if ($facId <= 0) continue;

                $status = $row['status'] ?? 'Present';
                if (!in_array($status, ['Present', 'Absent', 'Late', 'On-Leave'])) {
                    $status = 'Present';
                }
                $inTime = !empty($row['in_time']) ? trim($row['in_time']) : null;
                $outTime = !empty($row['out_time']) ? trim($row['out_time']) : null;
                $remarks = trim($row['remarks'] ?? '');

                // Check if existing record
                $checkStmt->execute([$facId, $postDate]);
                $existingId = $checkStmt->fetchColumn();

                if ($existingId) {
                    $updateStmt->execute([$status, $inTime, $outTime, $remarks, $currentAdminId, $existingId]);
                } else {
                    $insertStmt->execute([$facId, $postDate, $status, $inTime, $outTime, $remarks, $currentAdminId]);
                }
                $savedCount++;
            }

            $db->commit();
            setFlashMessage('success', "Attendance records for {$savedCount} faculty member(s) on {$postDate} successfully saved.");
            header("Location: " . BASE_URL . "admin/attendance/index.php?date={$postDate}&department_id={$deptFilter}");
            exit;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Attendance Save Error: " . $e->getMessage());
            $errors[] = 'Failed to save attendance: ' . $e->getMessage();
        }
    }
}

// Fetch Faculty roster along with their attendance record on selectedDate
$facultyRoster = [];
try {
    $whereDept = $deptFilter > 0 ? "WHERE f.department_id = " . $deptFilter : "";
    $sql = "
        SELECT f.id AS faculty_id, f.full_name, f.emp_id, f.designation,
               d.dept_code, d.dept_name,
               a.id AS attendance_id, a.status, a.in_time, a.out_time, a.remarks
        FROM faculty f
        JOIN departments d ON f.department_id = d.id
        LEFT JOIN attendance a ON f.id = a.faculty_id AND a.attendance_date = :att_date
        {$whereDept}
        ORDER BY d.dept_code ASC, f.full_name ASC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([':att_date' => $selectedDate]);
    $facultyRoster = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Attendance Roster Error: " . $e->getMessage());
}

// Compute statistics for the day
$totalStaff = count($facultyRoster);
$presentCount = 0;
$absentCount = 0;
$lateCount = 0;
$onLeaveCount = 0;
$notMarkedCount = 0;

foreach ($facultyRoster as $row) {
    if (empty($row['status'])) {
        $notMarkedCount++;
    } elseif ($row['status'] === 'Present') {
        $presentCount++;
    } elseif ($row['status'] === 'Absent') {
        $absentCount++;
    } elseif ($row['status'] === 'Late') {
        $lateCount++;
    } elseif ($row['status'] === 'On-Leave') {
        $onLeaveCount++;
    }
}

$effectivePresent = $presentCount + $lateCount;
$attendancePercent = ($totalStaff > 0 && $notMarkedCount < $totalStaff) 
    ? round(($effectivePresent / ($totalStaff - $onLeaveCount > 0 ? ($totalStaff - $onLeaveCount) : 1)) * 100, 1) 
    : 0;

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-header">
    <div>
        <h2 style="font-size: 20px; font-weight: 700; color: var(--text); margin: 0 0 4px;">Faculty Daily Attendance Log</h2>
        <p style="color: var(--text-muted); font-size: 14px; margin: 0;">Record in/out times, presence, late arrivals, and approved leave absences.</p>
    </div>
    <div style="display: flex; gap: 10px;" class="no-print">
        <a href="<?php echo BASE_URL; ?>admin/reports/index.php" class="btn btn-secondary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="20" x2="18" y2="10"/>
                <line x1="12" y1="20" x2="12" y2="4"/>
                <line x1="6" y1="20" x2="6" y2="14"/>
            </svg>
            Attendance Reports
        </a>
        <button type="button" class="btn btn-secondary" onclick="window.print();">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"/>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                <rect x="6" y="14" width="12" height="8"/>
            </svg>
            Print Sheet
        </button>
    </div>
</div>

<!-- Date Selector & Department Filter Bar -->
<div class="card no-print" style="margin-bottom: 24px;">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" action="" style="display: grid; grid-template-columns: auto 1fr auto auto; gap: 16px; align-items: end;">
            <div>
                <label class="form-label" style="font-size: 12px; margin-bottom: 4px;">Attendance Date</label>
                <input type="date" 
                       name="date" 
                       class="form-control" 
                       value="<?php echo escape($selectedDate); ?>" 
                       max="<?php echo date('Y-m-d'); ?>"
                       onchange="this.form.submit()" 
                       style="font-weight: 600;">
            </div>

            <div>
                <label class="form-label" style="font-size: 12px; margin-bottom: 4px;">Department Roster</label>
                <select name="department_id" class="form-control" onchange="this.form.submit()">
                    <option value="0">All Faculty Members (College-Wide)</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?php echo (int)$d['id']; ?>" <?php echo ($deptFilter === (int)$d['id']) ? 'selected' : ''; ?>>
                            <?php echo escape($d['dept_code']); ?> — <?php echo escape($d['dept_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <button type="submit" class="btn btn-secondary" style="height: 40px;">
                    Load Roster
                </button>
            </div>

            <div>
                <a href="<?php echo BASE_URL; ?>admin/attendance/index.php?date=<?php echo date('Y-m-d'); ?>" class="btn btn-secondary" style="height: 40px;" title="Jump to today">
                    Today
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Daily Attendance Metric Badges -->
<div class="stats-grid" style="margin-bottom: 24px;">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(13, 148, 136, 0.1); color: var(--accent);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Total Faculty</div>
            <div class="stat-value"><?php echo $totalStaff; ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Present</div>
            <div class="stat-value" style="color: var(--success);"><?php echo $presentCount; ?></div>
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
            <div class="stat-label">Absent</div>
            <div class="stat-value" style="color: var(--danger);"><?php echo $absentCount; ?></div>
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
            <div class="stat-label">Late / On-Leave</div>
            <div class="stat-value"><?php echo $lateCount; ?> / <?php echo $onLeaveCount; ?></div>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul style="margin: 0; padding-left: 20px;">
            <?php foreach ($errors as $error): ?>
                <li><?php echo escape($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Daily Attendance Form Card -->
<div class="card">
    <div class="card-header" style="flex-wrap: wrap; gap: 12px;">
        <div>
            <h3 class="card-title">
                Roster for <?php echo date('l, F j, Y', strtotime($selectedDate)); ?>
            </h3>
            <span style="font-size: 13px; color: var(--text-muted);">
                <?php if ($notMarkedCount > 0): ?>
                    <span class="badge badge-warning"><?php echo $notMarkedCount; ?> unrecorded</span>
                <?php else: ?>
                    <span class="badge badge-success">All recorded</span>
                <?php endif; ?>
            </span>
        </div>

        <div class="no-print" style="display: flex; gap: 8px;">
            <button type="button" class="btn btn-secondary" onclick="markAll('Present');" style="font-size: 12px; padding: 6px 12px;">
                Mark All Present
            </button>
            <button type="button" class="btn btn-secondary" onclick="setDefaultTimes();" style="font-size: 12px; padding: 6px 12px;">
                Auto-fill 09:00 - 17:00
            </button>
        </div>
    </div>

    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="attendance_date" value="<?php echo escape($selectedDate); ?>">

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 240px;">Faculty Member</th>
                        <th style="width: 120px;">Department</th>
                        <th style="width: 280px;">Attendance Status</th>
                        <th style="width: 130px;">In-Time</th>
                        <th style="width: 130px;">Out-Time</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($facultyRoster)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                No faculty records found for the selected department.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($facultyRoster as $row): 
                            $fid = (int)$row['faculty_id'];
                            $curStatus = $row['status'] ?: 'Present';
                            $curIn = $row['in_time'] ?: ($curStatus === 'Present' ? '09:00' : '');
                            $curOut = $row['out_time'] ?: ($curStatus === 'Present' ? '17:00' : '');
                            $curRemarks = $row['remarks'] ?? '';
                        ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600; color: var(--text);">
                                        <?php echo escape($row['full_name']); ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--text-muted);">
                                        <?php echo escape($row['emp_id']); ?> &bull; <?php echo escape($row['designation']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo escape($row['dept_code']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 8px; font-size: 13px;">
                                        <label style="display: inline-flex; align-items: center; gap: 4px; cursor: pointer; color: var(--success); font-weight: 600;">
                                            <input type="radio" 
                                                   name="attendance[<?php echo $fid; ?>][status]" 
                                                   value="Present" 
                                                   class="status-radio status-present"
                                                   <?php echo ($curStatus === 'Present') ? 'checked' : ''; ?>>
                                            Present
                                        </label>

                                        <label style="display: inline-flex; align-items: center; gap: 4px; cursor: pointer; color: var(--danger); font-weight: 600;">
                                            <input type="radio" 
                                                   name="attendance[<?php echo $fid; ?>][status]" 
                                                   value="Absent" 
                                                   class="status-radio status-absent"
                                                   <?php echo ($curStatus === 'Absent') ? 'checked' : ''; ?>>
                                            Absent
                                        </label>

                                        <label style="display: inline-flex; align-items: center; gap: 4px; cursor: pointer; color: var(--warning); font-weight: 600;">
                                            <input type="radio" 
                                                   name="attendance[<?php echo $fid; ?>][status]" 
                                                   value="Late" 
                                                   class="status-radio status-late"
                                                   <?php echo ($curStatus === 'Late') ? 'checked' : ''; ?>>
                                            Late
                                        </label>

                                        <label style="display: inline-flex; align-items: center; gap: 4px; cursor: pointer; color: #6366F1; font-weight: 600;">
                                            <input type="radio" 
                                                   name="attendance[<?php echo $fid; ?>][status]" 
                                                   value="On-Leave" 
                                                   class="status-radio status-leave"
                                                   <?php echo ($curStatus === 'On-Leave') ? 'checked' : ''; ?>>
                                            Leave
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <input type="time" 
                                           name="attendance[<?php echo $fid; ?>][in_time]" 
                                           class="form-control time-in-field" 
                                           value="<?php echo escape($curIn); ?>" 
                                           style="font-size: 12px; height: 32px;">
                                </td>
                                <td>
                                    <input type="time" 
                                           name="attendance[<?php echo $fid; ?>][out_time]" 
                                           class="form-control time-out-field" 
                                           value="<?php echo escape($curOut); ?>" 
                                           style="font-size: 12px; height: 32px;">
                                </td>
                                <td>
                                    <input type="text" 
                                           name="attendance[<?php echo $fid; ?>][remarks]" 
                                           class="form-control" 
                                           placeholder="e.g. Approved OD, medical..." 
                                           value="<?php echo escape($curRemarks); ?>" 
                                           maxlength="100" 
                                           style="font-size: 12px; height: 32px;">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="no-print" style="padding: 16px 20px; background: #F8FAFC; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
            <div style="font-size: 13px; color: var(--text-muted);">
                Changes will take effect immediately in faculty attendance analytics.
            </div>
            <button type="submit" class="btn btn-primary" id="btn-save-attendance">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                Save Attendance Register
            </button>
        </div>
    </form>
</div>

<script>
function markAll(status) {
    const radios = document.querySelectorAll('.status-radio[value="' + status + '"]');
    radios.forEach(r => r.checked = true);
}

function setDefaultTimes() {
    document.querySelectorAll('.time-in-field').forEach(input => {
        if (!input.value) input.value = '09:00';
    });
    document.querySelectorAll('.time-out-field').forEach(input => {
        if (!input.value) input.value = '17:00';
    });
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
