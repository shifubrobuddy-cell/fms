<?php
/**
 * Faculty Management System (FMS)
 * Faculty Authorization Guard
 * Include this at the very top of EVERY file in /faculty/ directory
 */

require_once __DIR__ . '/auth.php';

// Guard 1: Must be logged in
if (!isLoggedIn()) {
    setFlashMessage('warning', 'Please sign in to access the Faculty Portal.');
    redirect('login.php');
    exit;
}

// Guard 2: Must have 'faculty' role. If admin accesses faculty page, redirect to admin dashboard
if ($_SESSION['user_role'] !== 'faculty') {
    if ($_SESSION['user_role'] === 'admin') {
        redirect('admin/dashboard.php');
    } else {
        redirect('login.php');
    }
    exit;
}

// Guard 3: Must have an assigned faculty profile record
if (empty($_SESSION['faculty_id'])) {
    // Attempt re-query in case profile was newly linked
    $db = getDB();
    $fStmt = $db->prepare("SELECT id, full_name, emp_id, designation, department_id FROM faculty WHERE user_id = :uid LIMIT 1");
    $fStmt->execute([':uid' => $_SESSION['user_id']]);
    $fac = $fStmt->fetch();
    
    if ($fac) {
        $_SESSION['faculty_id'] = (int)$fac['id'];
        $_SESSION['full_name'] = $fac['full_name'];
        $_SESSION['emp_id'] = $fac['emp_id'];
        $_SESSION['designation'] = $fac['designation'];
        $_SESSION['department_id'] = $fac['department_id'];
    } else {
        die("<div style='font-family: sans-serif; padding: 25px; background: #FFFBEB; color: #92400E; border: 1px solid #FCD34D; border-radius: 8px; max-width: 600px; margin: 50px auto;'>
            <h3 style='margin-top:0;'>Faculty Profile Not Configured</h3>
            <p>Your user account is active, but your official faculty employee record has not yet been linked by the college administrator.</p>
            <p>Please contact the Administrative Office to link your Employee ID.</p>
            <a href='" . BASE_URL . "logout.php' style='display:inline-block; padding:8px 16px; background:#B45309; color:#fff; text-decoration:none; border-radius:6px;'>Sign Out</a>
        </div>");
    }
}
