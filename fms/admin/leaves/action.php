<?php
/**
 * Faculty Management System (FMS)
 * Admin: Process Leave Application Decision (Approve / Reject)
 */

require_once __DIR__ . '/../../includes/admin-auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'admin/leaves/index.php');
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($token)) {
    setFlashMessage('danger', 'Security token invalid. Please try again.');
    header('Location: ' . BASE_URL . 'admin/leaves/index.php');
    exit;
}

$leaveId = (int)($_POST['leave_id'] ?? 0);
$decision = trim($_POST['decision'] ?? '');
$adminRemarks = trim($_POST['admin_remarks'] ?? '');
$reviewerId = (int)($_SESSION['user_id'] ?? 1);

if ($leaveId <= 0 || !in_array($decision, ['Approved', 'Rejected'])) {
    setFlashMessage('danger', 'Invalid leave decision parameters.');
    header('Location: ' . BASE_URL . 'admin/leaves/index.php');
    exit;
}

$db = getDB();

try {
    // Fetch leave record
    $stmt = $db->prepare("SELECT * FROM leave_requests WHERE id = ?");
    $stmt->execute([$leaveId]);
    $leave = $stmt->fetch();

    if (!$leave) {
        setFlashMessage('danger', 'Leave request not found.');
        header('Location: ' . BASE_URL . 'admin/leaves/index.php');
        exit;
    }

    $db->beginTransaction();

    // Update status
    $now = date('Y-m-d H:i:s');
    $upd = $db->prepare("
        UPDATE leave_requests 
        SET status = ?, admin_remarks = ?, reviewed_by = ?, reviewed_at = ?
        WHERE id = ?
    ");
    $upd->execute([$decision, $adminRemarks ?: ($decision === 'Approved' ? 'Leave sanctioned.' : 'Request declined.'), $reviewerId, $now, $leaveId]);

    // If Approved, sync into attendance table as 'On-Leave'
    if ($decision === 'Approved') {
        $startDate = new DateTime($leave['start_date']);
        $endDate = new DateTime($leave['end_date']);
        $endDate->modify('+1 day'); // include end date

        $period = new DatePeriod($startDate, new DateInterval('P1D'), $endDate);

        $chkAtt = $db->prepare("SELECT id FROM attendance WHERE faculty_id = ? AND attendance_date = ?");
        $insAtt = $db->prepare("INSERT INTO attendance (faculty_id, attendance_date, status, remarks, recorded_by) VALUES (?, ?, 'On-Leave', ?, ?)");
        $updAtt = $db->prepare("UPDATE attendance SET status = 'On-Leave', remarks = ?, recorded_by = ? WHERE id = ?");

        $leaveRemark = "Sanctioned " . $leave['leave_type'] . " Leave";

        foreach ($period as $dt) {
            $dayStr = $dt->format('Y-m-d');
            // Skip Sundays if desired or record all
            $chkAtt->execute([$leave['faculty_id'], $dayStr]);
            $attId = $chkAtt->fetchColumn();

            if ($attId) {
                $updAtt->execute([$leaveRemark, $reviewerId, $attId]);
            } else {
                $insAtt->execute([$leave['faculty_id'], $dayStr, $leaveRemark, $reviewerId]);
            }
        }
    }

    $db->commit();
    setFlashMessage('success', "Leave request #{$leaveId} marked as {$decision}.");
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Leave Decision Error: " . $e->getMessage());
    setFlashMessage('danger', 'Failed to update leave request: ' . $e->getMessage());
}

$redirectBack = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . 'admin/leaves/index.php');
header('Location: ' . $redirectBack);
exit;
