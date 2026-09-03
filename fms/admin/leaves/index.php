<?php
/**
 * Faculty Management System (FMS)
 * Admin: Faculty Leave Requests Review Hub
 */

require_once __DIR__ . '/../../includes/admin-auth.php';

$pageTitle = 'Leave Approvals Management';
$activeMenu = 'leaves';
$db = getDB();

$statusFilter = $_GET['status'] ?? 'Pending'; // 'Pending', 'Approved', 'Rejected', 'All'
$deptFilter = (int)($_GET['department_id'] ?? 0);

// Handle 1-Click Approve All Pending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'approve_all') {
    $token = $_POST['csrf_token'] ?? '';
    if (validateCSRFToken($token)) {
        try {
            $reviewerId = (int)($_SESSION['user_id'] ?? 1);
            $upd = $db->prepare("
                UPDATE leave_requests 
                SET status = 'Approved', admin_remarks = 'Auto-approved via Executive 1-Click.', reviewed_by = ?, reviewed_at = datetime('now')
                WHERE status = 'Pending'
            ");
            $upd->execute([$reviewerId]);
            $count = $upd->rowCount();
            setFlashMessage('success', "Successfully approved all {$count} pending leave request(s)!");
            header('Location: ' . BASE_URL . 'admin/leaves/index.php?status=Approved');
            exit;
        } catch (Exception $e) {
            error_log("Approve all error: " . $e->getMessage());
            setFlashMessage('danger', 'Failed to auto-approve: ' . $e->getMessage());
        }
    }
}

// Fetch departments for filter
$departments = $db->query("SELECT id, dept_code, dept_name FROM departments ORDER BY dept_code ASC")->fetchAll();

// Build query
$where = [];
$params = [];

if ($statusFilter !== 'All' && in_array($statusFilter, ['Pending', 'Approved', 'Rejected'])) {
    $where[] = "lr.status = :status";
    $params[':status'] = $statusFilter;
}

if ($deptFilter > 0) {
    $where[] = "f.department_id = :dept_id";
    $params[':dept_id'] = $deptFilter;
}

$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Fetch leave requests with faculty & reviewer details
$leaves = [];
try {
    $sql = "
        SELECT lr.*,
               f.full_name, f.emp_id, f.designation, f.photo,
               d.dept_code, d.dept_name,
               u.username AS reviewer_username
        FROM leave_requests lr
        JOIN faculty f ON lr.faculty_id = f.id
        JOIN departments d ON f.department_id = d.id
        LEFT JOIN users u ON lr.reviewed_by = u.id
        {$whereSql}
        ORDER BY 
            CASE lr.status 
                WHEN 'Pending' THEN 1 
                WHEN 'Approved' THEN 2 
                ELSE 3 
            END,
            lr.created_at DESC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $leaves = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Leave list error: " . $e->getMessage());
}

// Counts for filter pills
$counts = ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0, 'All' => 0];
try {
    $cQuery = $db->query("SELECT status, COUNT(*) AS cnt FROM leave_requests GROUP BY status")->fetchAll();
    $tot = 0;
    foreach ($cQuery as $c) {
        $counts[$c['status']] = (int)$c['cnt'];
        $tot += (int)$c['cnt'];
    }
    $counts['All'] = $tot;
} catch (Exception $e) {}

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
    <div>
        <h2 style="font-size: 22px; font-weight: 800; color: #1E1B4B; margin: 0 0 4px; letter-spacing: -0.01em;">Faculty Leave Applications</h2>
        <p style="color: var(--text-muted); font-size: 14px; margin: 0;">Review, sanction, or instant-approve faculty absence requests.</p>
    </div>
    <div style="display: flex; align-items: center; gap: 10px;">
        <?php if ($counts['Pending'] > 0): ?>
            <form method="POST" action="" onsubmit="return confirm('Approve all <?php echo $counts['Pending']; ?> pending leave request(s) instantly?');">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="approve_all">
                <button type="submit" style="background: linear-gradient(135deg, #10B981, #059669); color: #FFFFFF; font-weight: 700; font-size: 13.5px; padding: 10px 18px; border-radius: 8px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    Approve All Pending (<?php echo $counts['Pending']; ?>)
                </button>
            </form>
        <?php else: ?>
            <div style="display: inline-flex; align-items: center; gap: 6px; background: #DCFCE7; color: #15803D; padding: 8px 14px; border-radius: 8px; font-weight: 700; font-size: 13px; border: 1px solid #BBF7D0;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                All Applications Sanctioned
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Filter Tabs & Department Filter -->
<div class="card no-print" style="margin-bottom: 24px;">
    <div class="card-body" style="padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
        <!-- Status Tabs -->
        <div style="display: flex; gap: 6px; background: var(--bg); padding: 4px; border-radius: 8px; border: 1px solid var(--border);">
            <?php 
            $statuses = ['Pending', 'Approved', 'Rejected', 'All'];
            foreach ($statuses as $st): 
                $isActive = ($statusFilter === $st);
            ?>
                <a href="?status=<?php echo $st; ?>&department_id=<?php echo $deptFilter; ?>" 
                   style="padding: 6px 14px; font-size: 13px; font-weight: 600; text-decoration: none; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; <?php echo $isActive ? 'background: var(--surface); color: var(--primary-dark); box-shadow: 0 1px 2px rgba(0,0,0,0.06);' : 'color: var(--text-muted);'; ?>">
                    <?php echo $st; ?>
                    <span class="badge <?php echo ($st === 'Pending' && $counts['Pending'] > 0) ? 'badge-danger' : ''; ?>" style="font-size: 11px; padding: 1px 6px;">
                        <?php echo $counts[$st] ?? 0; ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Department Filter Form -->
        <form method="GET" action="" style="display: flex; gap: 8px; align-items: center;">
            <input type="hidden" name="status" value="<?php echo escape($statusFilter); ?>">
            <label style="font-size: 13px; color: var(--text-muted); font-weight: 500;">Filter Dept:</label>
            <select name="department_id" class="form-control" style="font-size: 13px; padding: 4px 10px; width: auto;" onchange="this.form.submit()">
                <option value="0">All Departments</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?php echo (int)$d['id']; ?>" <?php echo ($deptFilter === (int)$d['id']) ? 'selected' : ''; ?>>
                        <?php echo escape($d['dept_code']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<!-- Leave Requests Roster -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?php echo escape($statusFilter); ?> Leave Requests</h3>
        <span style="font-size: 13px; color: var(--text-muted);"><?php echo count($leaves); ?> request(s) found</span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Applicant</th>
                    <th>Leave Category</th>
                    <th>Duration &amp; Dates</th>
                    <th>Days</th>
                    <th>Reason / Justification</th>
                    <th>Status &amp; Review</th>
                    <th style="text-align: right; width: 140px;" class="no-print">Decision</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leaves)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                    </svg>
                                </div>
                                <div class="empty-state-title">No <?php echo escape($statusFilter); ?> Requests</div>
                                <p class="empty-state-desc">There are no faculty leave applications in this state.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($leaves as $lr): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600; color: var(--text);">
                                    <a href="<?php echo BASE_URL; ?>admin/faculty/view.php?id=<?php echo (int)$lr['faculty_id']; ?>" style="color: inherit; text-decoration: none;">
                                        <?php echo escape($lr['full_name']); ?>
                                    </a>
                                </div>
                                <div style="font-size: 12px; color: var(--text-muted);">
                                    <?php echo escape($lr['emp_id']); ?> &bull; <span class="badge badge-info" style="font-size: 10px; padding: 1px 4px;"><?php echo escape($lr['dept_code']); ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-warning" style="font-size: 12px; font-weight: 600;">
                                    <?php echo escape($lr['leave_type']); ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-weight: 600; font-size: 13px; color: var(--text);">
                                    <?php echo date('M j, Y', strtotime($lr['start_date'])); ?>
                                </div>
                                <div style="font-size: 12px; color: var(--text-muted);">
                                    to <?php echo date('M j, Y', strtotime($lr['end_date'])); ?>
                                </div>
                            </td>
                            <td>
                                <span style="font-weight: 700; font-size: 14px; color: var(--primary-dark);">
                                    <?php echo (int)$lr['days_count']; ?>
                                </span> 
                                <span style="font-size: 12px; color: var(--text-muted);">day(s)</span>
                            </td>
                            <td style="max-width: 260px;">
                                <div style="font-size: 13px; color: var(--text); line-height: 1.4;">
                                    <?php echo nl2br(escape($lr['reason'])); ?>
                                </div>
                                <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">
                                    Applied: <?php echo date('M j, Y \a\t h:i A', strtotime($lr['created_at'])); ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($lr['status'] === 'Pending'): ?>
                                    <span class="badge badge-warning" style="font-weight: 600;">Pending Review</span>
                                <?php elseif ($lr['status'] === 'Approved'): ?>
                                    <span class="badge badge-success" style="font-weight: 600;">Approved</span>
                                    <?php if (!empty($lr['admin_remarks'])): ?>
                                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px; font-style: italic;">
                                            &ldquo;<?php echo escape($lr['admin_remarks']); ?>&rdquo;
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge badge-danger" style="font-weight: 600;">Rejected</span>
                                    <?php if (!empty($lr['admin_remarks'])): ?>
                                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px; font-style: italic;">
                                            &ldquo;<?php echo escape($lr['admin_remarks']); ?>&rdquo;
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;" class="no-print">
                                <?php if ($lr['status'] === 'Pending'): ?>
                                    <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                        <!-- Quick Approve Form -->
                                        <form method="POST" action="<?php echo BASE_URL; ?>admin/leaves/action.php" onsubmit="return confirm('Approve leave request for <?php echo escape($lr['full_name']); ?>?');">
                                            <input type="hidden" name="leave_id" value="<?php echo (int)$lr['id']; ?>">
                                            <input type="hidden" name="decision" value="Approved">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <button type="submit" class="btn btn-primary" style="font-size: 12px; padding: 4px 10px; height: auto;" title="Sanction Leave">
                                                Approve
                                            </button>
                                        </form>

                                        <!-- Quick Reject Form -->
                                        <form method="POST" action="<?php echo BASE_URL; ?>admin/leaves/action.php" onsubmit="return confirm('Decline this leave application?');">
                                            <input type="hidden" name="leave_id" value="<?php echo (int)$lr['id']; ?>">
                                            <input type="hidden" name="decision" value="Rejected">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <button type="submit" class="btn btn-secondary" style="font-size: 12px; padding: 4px 10px; height: auto; color: var(--danger);" title="Reject Leave">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span style="font-size: 12px; color: var(--text-muted);">
                                        Reviewed
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

<?php include __DIR__ . '/../../includes/footer.php'; ?>
