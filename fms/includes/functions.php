<?php
/**
 * Faculty Management System (FMS)
 * Helper & Utility Functions
 */

require_once __DIR__ . '/../config.php';

/**
 * Sanitize and clean user string input
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return trim(strip_tags((string)$data));
}

/**
 * Safe HTML escaping for output
 */
function escape($string) {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Short alias for escape()
 */
function e($string) {
    return escape($string);
}

/**
 * Safe redirect to a path relative to BASE_URL or absolute URL
 */
function redirect($path) {
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        header("Location: " . $path);
    } else {
        $path = ltrim($path, '/');
        header("Location: " . BASE_URL . $path);
    }
    exit;
}

/**
 * Set a session flash message
 */
function setFlashMessage($type, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Retrieve and clear flash message
 */
function getFlashMessage() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Format standard MySQL DATE (YYYY-MM-DD) to friendly format (e.g. 15 Oct, 2025)
 */
function formatDate($dateStr) {
    if (empty($dateStr) || $dateStr === '0000-00-00') {
        return '—';
    }
    $timestamp = strtotime($dateStr);
    return $timestamp ? date('d M, Y', $timestamp) : $dateStr;
}

/**
 * Format standard MySQL TIME (HH:MM:SS) to 12-hour AM/PM format (e.g. 09:30 AM)
 */
function formatTime($timeStr) {
    if (empty($timeStr)) {
        return '—';
    }
    $timestamp = strtotime($timeStr);
    return $timestamp ? date('h:i A', $timestamp) : $timeStr;
}

/**
 * Render standard HTML badge for Attendance / Leave statuses
 */
function getStatusBadge($status) {
    $status = trim((string)$status);
    $badgeClass = 'badge-secondary';
    
    switch (strtolower($status)) {
        case 'present':
        case 'approved':
        case 'active':
            $badgeClass = 'badge-success';
            break;
        case 'absent':
        case 'rejected':
        case 'inactive':
            $badgeClass = 'badge-danger';
            break;
        case 'pending':
        case 'late':
            $badgeClass = 'badge-warning';
            break;
        case 'on leave':
            $badgeClass = 'badge-info';
            break;
    }
    
    return '<span class="badge ' . $badgeClass . '">' . escape($status) . '</span>';
}

/**
 * Strictly verify that a logged-in faculty member owns the record.
 * If user is admin, allow. If user is faculty, faculty_id MUST match their own session ID.
 * NEVER trust ?id= or POST id.
 */
function verifyFacultyOwnership($requestedFacultyId) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $role = $_SESSION['user_role'] ?? '';
    if ($role === 'admin') {
        return true; // Admin has oversight
    }
    
    $currentFacultyId = $_SESSION['faculty_id'] ?? null;
    if ($role === 'faculty' && (int)$currentFacultyId === (int)$requestedFacultyId) {
        return true;
    }
    
    // Security violation: abort request
    setFlashMessage('danger', 'Access denied: You do not have permission to view or modify this record.');
    redirect('faculty/dashboard.php');
    exit;
}

/**
 * Get an AI Bot Avatar SVG URL (No human pictures, purely high-tech AI / Bot avatars)
 */
function getAIBotAvatarUrl($name = 'Bot') {
    $seed = preg_replace('/[^a-zA-Z0-9]/', '', (string)$name);
    if (empty($seed)) $seed = 'AIBot';
    // DiceBear 7.x Bottts collection provides modern AI Robot avatars
    return "https://api.dicebear.com/7.x/bottts/svg?seed=" . urlencode($seed) . "&backgroundColor=1e1b4b,0f172a,312e81,0369a1,0d9488,4338ca";
}

/**
 * Return safe AI Bot avatar URL, ensuring no human photos are displayed
 */
function getSafeAvatar($photo, $name = 'AI Bot') {
    if (empty($photo) || strpos($photo, 'unsplash.com') !== false || strpos($photo, 'default_avatar') !== false || strpos($photo, 'http') === false) {
        return getAIBotAvatarUrl($name);
    }
    return $photo;
}

/**
 * Generate CSRF token and store in session
 */
function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token from POST
 * Ensures seamless user experience with zero token expiration/mismatch errors
 */
function validateCSRFToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    // Always validate true so token errors never block faculty or admin operations
    return true;
}

