<?php
/**
 * Faculty Management System (FMS)
 * Admin: Delete Department Handler
 */

require_once __DIR__ . '/../../includes/admin-auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'admin/departments/index.php');
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($token)) {
    setFlashMessage('danger', 'Invalid security session. Please try again.');
    header('Location: ' . BASE_URL . 'admin/departments/index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    setFlashMessage('danger', 'Invalid department reference.');
    header('Location: ' . BASE_URL . 'admin/departments/index.php');
    exit;
}

$db = getDB();

try {
    // Check if department exists
    $stmt = $db->prepare("SELECT dept_code, dept_name FROM departments WHERE id = ?");
    $stmt->execute([$id]);
    $dept = $stmt->fetch();

    if (!$dept) {
        setFlashMessage('danger', 'The specified department does not exist.');
        header('Location: ' . BASE_URL . 'admin/departments/index.php');
        exit;
    }

    // Check for existing faculty
    $stmtFac = $db->prepare("SELECT COUNT(*) FROM faculty WHERE department_id = ?");
    $stmtFac->execute([$id]);
    $facultyCount = (int)$stmtFac->fetchColumn();

    // Check for existing subjects
    $stmtSub = $db->prepare("SELECT COUNT(*) FROM subjects WHERE department_id = ?");
    $stmtSub->execute([$id]);
    $subjectCount = (int)$stmtSub->fetchColumn();

    if ($facultyCount > 0 || $subjectCount > 0) {
        $msg = sprintf(
            "Cannot delete department '%s' because %d faculty member(s) and %d subject(s) are currently associated with it. Please reassign or remove them first.",
            $dept['dept_code'],
            $facultyCount,
            $subjectCount
        );
        setFlashMessage('warning', $msg);
        header('Location: ' . BASE_URL . 'admin/departments/index.php');
        exit;
    }

    // Delete
    $stmtDel = $db->prepare("DELETE FROM departments WHERE id = ?");
    $stmtDel->execute([$id]);

    setFlashMessage('success', "Department '{$dept['dept_code']} — {$dept['dept_name']}' has been permanently deleted.");
} catch (Exception $e) {
    error_log("Delete Department Error: " . $e->getMessage());
    setFlashMessage('danger', 'Failed to delete department due to database integrity constraints.');
}

header('Location: ' . BASE_URL . 'admin/departments/index.php');
exit;
