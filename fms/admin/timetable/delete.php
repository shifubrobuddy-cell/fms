<?php
/**
 * Faculty Management System (FMS)
 * Admin: Delete Timetable Slot Handler
 */

require_once __DIR__ . '/../../includes/admin-auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'admin/timetable/index.php');
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($token)) {
    setFlashMessage('danger', 'Security session invalid.');
    header('Location: ' . BASE_URL . 'admin/timetable/index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    setFlashMessage('danger', 'Invalid timetable slot.');
    header('Location: ' . BASE_URL . 'admin/timetable/index.php');
    exit;
}

$db = getDB();

try {
    $stmt = $db->prepare("DELETE FROM timetable WHERE id = ?");
    $stmt->execute([$id]);

    setFlashMessage('success', 'Lecture slot removed from timetable schedule.');
} catch (Exception $e) {
    error_log("Delete Timetable Error: " . $e->getMessage());
    setFlashMessage('danger', 'Failed to remove slot: ' . $e->getMessage());
}

$redirectBack = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . 'admin/timetable/index.php');
header('Location: ' . $redirectBack);
exit;
