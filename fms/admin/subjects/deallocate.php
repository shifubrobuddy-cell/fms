<?php
/**
 * Faculty Management System (FMS)
 * Admin: Deallocate Course from Faculty
 */

require_once __DIR__ . '/../../includes/admin-auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'admin/subjects/allocate.php');
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($token)) {
    setFlashMessage('danger', 'Invalid security token.');
    header('Location: ' . BASE_URL . 'admin/subjects/allocate.php');
    exit;
}

$facultyId = (int)($_POST['faculty_id'] ?? 0);
$subjectId = (int)($_POST['subject_id'] ?? 0);

if ($facultyId <= 0 || $subjectId <= 0) {
    setFlashMessage('danger', 'Invalid allocation parameters.');
    header('Location: ' . BASE_URL . 'admin/subjects/allocate.php');
    exit;
}

$db = getDB();

try {
    $stmt = $db->prepare("DELETE FROM faculty_subjects WHERE faculty_id = ? AND subject_id = ?");
    $stmt->execute([$facultyId, $subjectId]);

    setFlashMessage('success', 'Faculty course allocation unlinked successfully.');
} catch (Exception $e) {
    error_log("Deallocation Error: " . $e->getMessage());
    setFlashMessage('danger', 'Failed to remove allocation: ' . $e->getMessage());
}

$redirectBack = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . 'admin/subjects/allocate.php');
header('Location: ' . $redirectBack);
exit;
