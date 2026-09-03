<?php
/**
 * Faculty Management System (FMS)
 * Logout Controller
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

performLogout();

// Redirect with logged out flash parameter
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
setFlashMessage('info', 'You have been securely signed out.');
redirect('login.php');
