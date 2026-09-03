<?php
/**
 * Faculty Management System (FMS)
 * Admin: Delete Faculty Member Handler
 */

require_once __DIR__ . '/../../includes/admin-auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'admin/faculty/index.php');
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($token)) {
    setFlashMessage('danger', 'Security token invalid or expired. Please try again.');
    header('Location: ' . BASE_URL . 'admin/faculty/index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    setFlashMessage('danger', 'Invalid faculty reference.');
    header('Location: ' . BASE_URL . 'admin/faculty/index.php');
    exit;
}

$db = getDB();

try {
    // Fetch faculty & user
    $stmt = $db->prepare("SELECT f.*, u.username FROM faculty f JOIN users u ON f.user_id = u.id WHERE f.id = ?");
    $stmt->execute([$id]);
    $faculty = $stmt->fetch();

    if (!$faculty) {
        setFlashMessage('danger', 'Faculty member not found.');
        header('Location: ' . BASE_URL . 'admin/faculty/index.php');
        exit;
    }

    $userId = (int)$faculty['user_id'];
    $photo = $faculty['photo'];
    $fullName = $faculty['full_name'];
    $empId = $faculty['emp_id'];

    $db->beginTransaction();

    // Remove photo from uploads if not default
    if ($photo && $photo !== 'default_avatar.png') {
        $photoPath = __DIR__ . '/../../assets/images/uploads/' . $photo;
        if (file_exists($photoPath)) {
            @unlink($photoPath);
        }
    }

    // Delete related records explicitly
    $delFS = $db->prepare("DELETE FROM faculty_subjects WHERE faculty_id = ?");
    $delFS->execute([$id]);

    $delTT = $db->prepare("DELETE FROM timetable WHERE faculty_id = ?");
    $delTT->execute([$id]);

    $delAtt = $db->prepare("DELETE FROM attendance WHERE faculty_id = ?");
    $delAtt->execute([$id]);

    $delLeave = $db->prepare("DELETE FROM leave_requests WHERE faculty_id = ?");
    $delLeave->execute([$id]);

    $delFac = $db->prepare("DELETE FROM faculty WHERE id = ?");
    $delFac->execute([$id]);

    $delUser = $db->prepare("DELETE FROM users WHERE id = ?");
    $delUser->execute([$userId]);

    $db->commit();

    setFlashMessage('success', "Faculty member '{$fullName}' ({$empId}) and their portal credentials have been permanently deleted.");
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Delete Faculty Error: " . $e->getMessage());
    setFlashMessage('danger', 'Failed to delete faculty member: ' . $e->getMessage());
}

header('Location: ' . BASE_URL . 'admin/faculty/index.php');
exit;
