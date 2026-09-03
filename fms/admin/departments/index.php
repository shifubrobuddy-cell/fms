<?php
/**
 * Faculty Management System (FMS)
 * Admin: Departments Directory & Overview
 */

require_once __DIR__ . '/../../includes/admin-auth.php';

$pageTitle = 'Department Management';
$activeMenu = 'departments';
$db = getDB();

// Search filter
$search = trim($_GET['search'] ?? '');

try {
    if ($search !== '') {
        $stmt = $db->prepare("
            SELECT d.*, 
                   (SELECT COUNT(*) FROM faculty WHERE department_id = d.id) AS faculty_count,
                   (SELECT COUNT(*) FROM subjects WHERE department_id = d.id) AS subject_count
            FROM departments d
            WHERE d.dept_name LIKE :s1 OR d.dept_code LIKE :s2 OR d.description LIKE :s3
            ORDER BY d.dept_code ASC
        ");
        $term = "%{$search}%";
        $stmt->execute([':s1' => $term, ':s2' => $term, ':s3' => $term]);
    } else {
        $stmt = $db->query("
            SELECT d.*, 
                   (SELECT COUNT(*) FROM faculty WHERE department_id = d.id) AS faculty_count,
                   (SELECT COUNT(*) FROM subjects WHERE department_id = d.id) AS subject_count
            FROM departments d
            ORDER BY d.dept_code ASC
        ");
    }
    $departments = $stmt->fetchAll();

    // Aggregates
    $totalDepts = count($departments);
    $totalFaculty = (int)$db->query("SELECT COUNT(*) FROM faculty")->fetchColumn();
    $totalSubjects = (int)$db->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
} catch (Exception $e) {
    error_log("Departments Index Error: " . $e->getMessage());
    $departments = [];
    $totalDepts = 0;
    $totalFaculty = 0;
    $totalSubjects = 0;
}

include __DIR__ . '/../../includes/header.php';
?>

<!-- Action Header -->
<div class="content-header">
    <div>
        <h2 style="font-size: 20px; font-weight: 700; color: var(--text); margin: 0 0 4px;">Academic Departments</h2>
        <p style="color: var(--text-muted); font-size: 14px; margin: 0;">Configure university departments, curriculum disciplines, and faculty allocations.</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="<?php echo BASE_URL; ?>admin/departments/add.php" class="btn btn-primary" id="btn-add-dept">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Add Department
        </a>
    </div>
</div>

<!-- Stat Metric Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(13, 148, 136, 0.1); color: var(--accent);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 21h18"/>
                <path d="M5 21V7l8-4v18"/>
                <path d="M19 21V11l-6-3"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Active Departments</div>
            <div class="stat-value"><?php echo $totalDepts; ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(15, 32, 39, 0.08); color: var(--primary-dark);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Allocated Faculty</div>
            <div class="stat-value"><?php echo $totalFaculty; ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(2, 132, 199, 0.1); color: #0284C7;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Courses / Subjects</div>
            <div class="stat-value"><?php echo $totalSubjects; ?></div>
        </div>
    </div>
</div>

<!-- Search & Filter Card -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" action="" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 260px; position: relative;">
                <input type="text" 
                       name="search" 
                       class="form-control" 
                       placeholder="Search by code (e.g. CSE), department name, or description..." 
                       value="<?php echo escape($search); ?>">
            </div>
            <button type="submit" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                Search
            </button>
            <?php if ($search !== ''): ?>
                <a href="<?php echo BASE_URL; ?>admin/departments/index.php" class="btn btn-secondary">
                    Clear Filter
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Department Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Departments (<?php echo count($departments); ?>)</h3>
        <span style="font-size: 13px; color: var(--text-muted);">Institutional directory</span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 100px;">Code</th>
                    <th>Department Name</th>
                    <th>Description</th>
                    <th style="text-align: center; width: 120px;">Faculty</th>
                    <th style="text-align: center; width: 120px;">Subjects</th>
                    <th style="text-align: right; width: 140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($departments)): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 21h18"/>
                                        <path d="M5 21V7l8-4v18"/>
                                        <path d="M19 21V11l-6-3"/>
                                    </svg>
                                </div>
                                <div class="empty-state-title">No Departments Found</div>
                                <p class="empty-state-desc">
                                    <?php echo ($search !== '') ? 'No departments matched your search criteria.' : 'No departments have been added yet. Click Add Department above to create one.'; ?>
                                </p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($departments as $dept): ?>
                        <tr>
                            <td>
                                <span class="badge badge-info" style="font-size: 13px; font-weight: 700; padding: 4px 8px;">
                                    <?php echo escape($dept['dept_code']); ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: var(--text);">
                                    <?php echo escape($dept['dept_name']); ?>
                                </div>
                            </td>
                            <td style="color: var(--text-muted); font-size: 13px; max-width: 320px;">
                                <?php echo escape($dept['description'] ?? '—'); ?>
                            </td>
                            <td style="text-align: center;">
                                <a href="<?php echo BASE_URL; ?>admin/faculty/index.php?department_id=<?php echo (int)$dept['id']; ?>" 
                                   class="badge badge-success" 
                                   title="View faculty in <?php echo escape($dept['dept_code']); ?>">
                                    <?php echo (int)$dept['faculty_count']; ?> Faculty
                                </a>
                            </td>
                            <td style="text-align: center;">
                                <a href="<?php echo BASE_URL; ?>admin/subjects/index.php?department_id=<?php echo (int)$dept['id']; ?>" 
                                   class="badge badge-warning" 
                                   title="View subjects in <?php echo escape($dept['dept_code']); ?>">
                                    <?php echo (int)$dept['subject_count']; ?> Courses
                                </a>
                            </td>
                            <td style="text-align: right;">
                                <div class="table-actions" style="justify-content: flex-end;">
                                    <a href="<?php echo BASE_URL; ?>admin/departments/edit.php?id=<?php echo (int)$dept['id']; ?>" 
                                       class="btn-action" 
                                       title="Edit Department">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </a>
                                    <form method="POST" 
                                          action="<?php echo BASE_URL; ?>admin/departments/delete.php" 
                                          style="display: inline;"
                                          onsubmit="return confirm('Are you sure you want to delete department \'<?php echo escape($dept['dept_code']); ?>\'? This cannot be undone.');">
                                        <input type="hidden" name="id" value="<?php echo (int)$dept['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <button type="submit" class="btn-action btn-action-danger" title="Delete Department">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"/>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
