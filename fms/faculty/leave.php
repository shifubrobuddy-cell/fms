<?php
/**
 * Faculty Management System (FMS)
 * Faculty: Leave Application & Sanction Tracking Portal
 */

require_once __DIR__ . '/../includes/faculty-auth.php';

$pageTitle = 'Apply & Track Leave';
$activeMenu = 'leave';
$db = getDB();

$facultyId = (int)$_SESSION['faculty_id'];

$errors = [];
$leaveType = 'Casual Leave';
$startDate = date('Y-m-d');
$endDate = date('Y-m-d');
$reason = '';

// Handle Application Submission POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'apply_leave') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        $errors[] = 'Invalid security session token. Please try again.';
    }

    $leaveType = trim($_POST['leave_type'] ?? 'Casual Leave');
    $startDate = trim($_POST['start_date'] ?? '');
    $endDate = trim($_POST['end_date'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    $allowedTypes = ['Casual Leave', 'Sick Leave', 'Earned Leave', 'Duty Leave (OD)'];
    if (!in_array($leaveType, $allowedTypes)) {
        $errors[] = 'Please choose a valid leave category.';
    }

    if (empty($startDate) || empty($endDate)) {
        $errors[] = 'Please specify both leave commencement and conclusion dates.';
    } elseif (strtotime($startDate) > strtotime($endDate)) {
        $errors[] = 'Leave end date cannot be earlier than start date.';
    }

    if (empty($reason)) {
        $errors[] = 'Please specify the reason for absence.';
    }

    if (empty($errors)) {
        // Calculate days count
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $interval = $start->diff($end);
        $daysCount = (int)$interval->days + 1;

        try {
            $stmt = $db->prepare("
                INSERT INTO leave_requests (faculty_id, leave_type, start_date, end_date, days_count, reason, status, admin_remarks, reviewed_by, reviewed_at)
                VALUES (?, ?, ?, ?, ?, ?, 'Approved', 'Auto-approved upon submission.', 1, datetime('now'))
            ");
            $stmt->execute([$facultyId, $leaveType, $startDate, $endDate, $daysCount, $reason]);

            setFlashMessage('success', "Leave application for {$daysCount} day(s) has been automatically approved!");
            header('Location: ' . BASE_URL . 'faculty/leave.php');
            exit;
        } catch (Exception $e) {
            error_log("Apply leave error: " . $e->getMessage());
            $errors[] = 'Failed to submit application: ' . $e->getMessage();
        }
    }
}

// Handle Cancel Pending Application POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'cancel_leave') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        setFlashMessage('danger', 'Security session invalid.');
        header('Location: ' . BASE_URL . 'faculty/leave.php');
        exit;
    }

    $cancelId = (int)($_POST['leave_id'] ?? 0);
    if ($cancelId > 0) {
        try {
            // Strictly check ownership and pending state
            $cStmt = $db->prepare("DELETE FROM leave_requests WHERE id = ? AND faculty_id = ? AND status = 'Pending'");
            $cStmt->execute([$cancelId, $facultyId]);

            if ($cStmt->rowCount() > 0) {
                setFlashMessage('success', 'Pending leave application cancelled.');
            } else {
                setFlashMessage('danger', 'Only pending leave requests can be withdrawn.');
            }
        } catch (Exception $e) {
            error_log("Cancel leave error: " . $e->getMessage());
            setFlashMessage('danger', 'Failed to withdraw application.');
        }
    }
    header('Location: ' . BASE_URL . 'faculty/leave.php');
    exit;
}

// Fetch all applications for this faculty
$leaveHistory = [];
try {
    $histStmt = $db->prepare("
        SELECT lr.*, u.username AS reviewer_name
        FROM leave_requests lr
        LEFT JOIN users u ON lr.reviewed_by = u.id
        WHERE lr.faculty_id = ?
        ORDER BY lr.created_at DESC
    ");
    $histStmt->execute([$facultyId]);
    $leaveHistory = $histStmt->fetchAll();
} catch (Exception $e) {
    error_log("Leave history error: " . $e->getMessage());
}

include __DIR__ . '/../includes/header.php';
?>

<div class="content-header">
    <div>
        <h2 style="font-size: 20px; font-weight: 700; color: var(--text); margin: 0 0 4px;">Leave Applications &amp; Balances</h2>
        <p style="color: var(--text-muted); font-size: 14px; margin: 0;">Submit requisition for planned absence and review administrative sanctions.</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: minmax(320px, 380px) 1fr; gap: 24px; align-items: start;">
    <!-- Apply for Leave Form -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Apply for Leave</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" style="margin-bottom: 16px; font-size: 13px;">
                    <ul style="margin: 0; padding-left: 18px;">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo escape($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="action_type" value="apply_leave">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <div class="form-group">
                    <label for="leave_type" class="form-label">
                        Leave Classification <span style="color: var(--danger);">*</span>
                    </label>
                    <select id="leave_type" name="leave_type" class="form-control" required>
                        <option value="Casual Leave" <?php echo ($leaveType === 'Casual Leave') ? 'selected' : ''; ?>>Casual Leave (CL)</option>
                        <option value="Sick Leave" <?php echo ($leaveType === 'Sick Leave') ? 'selected' : ''; ?>>Sick Leave / Medical (SL)</option>
                        <option value="Earned Leave" <?php echo ($leaveType === 'Earned Leave') ? 'selected' : ''; ?>>Earned Leave (EL)</option>
                        <option value="Duty Leave (OD)" <?php echo ($leaveType === 'Duty Leave (OD)') ? 'selected' : ''; ?>>On Duty / Conference (OD)</option>
                    </select>
                </div>

                <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="form-group">
                        <label for="start_date" class="form-label">
                            Commencing <span style="color: var(--danger);">*</span>
                        </label>
                        <input type="date" 
                               id="start_date" 
                               name="start_date" 
                               class="form-control" 
                               value="<?php echo escape($startDate); ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="end_date" class="form-label">
                            Concluding <span style="color: var(--danger);">*</span>
                        </label>
                        <input type="date" 
                               id="end_date" 
                               name="end_date" 
                               class="form-control" 
                               value="<?php echo escape($endDate); ?>" 
                               required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reason" class="form-label">
                        Reason &amp; Duty Adjustments <span style="color: var(--danger);">*</span>
                    </label>
                    <textarea id="reason" 
                              name="reason" 
                              class="form-control" 
                              rows="3" 
                              placeholder="Describe justification or faculty colleague covering lecture duties..." 
                              required><?php echo escape($reason); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"/>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                    Submit Leave Requisition
                </button>
            </form>
        </div>
    </div>

    <!-- Application History Register -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">My Application History</h3>
                <span style="font-size: 13px; color: var(--text-muted);"><?php echo count($leaveHistory); ?> application(s)</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Leave Type</th>
                        <th>Duration</th>
                        <th style="text-align: center;">Days</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th style="text-align: right; width: 80px;" class="no-print">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leaveHistory)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                You have not submitted any leave applications yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($leaveHistory as $row): 
                            $badgeClass = 'badge-warning';
                            if ($row['status'] === 'Approved') $badgeClass = 'badge-success';
                            elseif ($row['status'] === 'Rejected') $badgeClass = 'badge-danger';
                        ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600; color: var(--text); font-size: 13px;">
                                        <?php echo escape($row['leave_type']); ?>
                                    </div>
                                    <div style="font-size: 11px; color: var(--text-muted);">
                                        Applied: <?php echo date('M j, Y', strtotime($row['created_at'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 13px; font-weight: 500;">
                                        <?php echo date('M j', strtotime($row['start_date'])); ?> &ndash; <?php echo date('M j, Y', strtotime($row['end_date'])); ?>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span style="font-weight: 700; color: var(--primary-dark);">
                                        <?php echo (int)$row['days_count']; ?>
                                    </span>
                                </td>
                                <td style="max-width: 220px;">
                                    <div style="font-size: 12px; color: var(--text); line-height: 1.3;">
                                        <?php echo nl2br(escape($row['reason'])); ?>
                                    </div>
                                    <?php if (!empty($row['admin_remarks'])): ?>
                                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px; font-style: italic;">
                                            Note: <?php echo escape($row['admin_remarks']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $badgeClass; ?>" style="font-weight: 600;">
                                        <?php echo escape($row['status']); ?>
                                    </span>
                                </td>
                                <td style="text-align: right;" class="no-print">
                                    <?php if ($row['status'] === 'Pending'): ?>
                                        <form method="POST" action="" onsubmit="return confirm('Withdraw this pending leave application?');">
                                            <input type="hidden" name="action_type" value="cancel_leave">
                                            <input type="hidden" name="leave_id" value="<?php echo (int)$row['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <button type="submit" class="btn-action btn-action-danger" title="Withdraw Requisition">
                                                Cancel
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 11px;">
                                            Finalized
                                        </span>
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
