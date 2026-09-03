<?php
/**
 * Faculty Management System (FMS)
 * Faculty Index Router
 */
require_once __DIR__ . '/../includes/faculty-auth.php';
header('Location: ' . BASE_URL . 'faculty/dashboard.php');
exit;
