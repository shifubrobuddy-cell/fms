<?php
/**
 * Faculty Management System (FMS)
 * Admin: Delete Subject Handler
 */

require_once __DIR__ . '/../../includes/admin-auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'admin/subjects/index.php');
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($token)) {
    setFlashMessage('danger', 'Security session invalid. Please try again.');
    header('Location: ' . BASE_URL . 'admin/subjects/index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    setFlashMessage('danger', 'Invalid subject reference.');
    header('Location: ' . BASE_URL . 'admin/subjects/index.php');
    exit;
}

$db = getDB();

try {
    $stmt = $db->prepare("SELECT * FROM subjects WHERE id = ?");
    $stmt->execute([$id]);
    $subject = $stmt->fetch();

    if (!$subject) {
        setFlashMessage('danger', 'Subject not found.');
        header('Location: ' . BASE_URL . 'admin/subjects/index.php');
        exit;
    }

    $db->beginTransaction();

    // Remove faculty subject allocations
    $delFS = $db->prepare("DELETE FROM faculty_subjects WHERE subject_id = ?");
    $delFS->execute([$id]);

    // Remove timetable slots
    $delTT = $db->prepare("DELETE FROM timetable WHERE subject_id = ?");
    $delTT->execute([$id]);

    // Delete subject
    $delSub = $db->prepare("DELETE FROM subjects WHERE id = ?");
    $delSub->execute([$id]);

    $db->commit();

    setFlashMessage('success', "Course '{$subject['subject_code']} — {$subject['subject_name']}' has been deleted.");
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Delete Subject Error: " . $e->getMessage());
    setFlashMessage('danger', 'Failed to delete course: ' . $e->getMessage());
}

header('Location: ' . BASE_URL . 'admin/subjects/index.php');
exit;
