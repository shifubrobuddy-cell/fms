<?php
/**
 * Faculty Management System (FMS)
 * Admin Index Router
 */
require_once __DIR__ . '/../includes/admin-auth.php';
header('Location: ' . BASE_URL . 'admin/dashboard.php');
exit;
