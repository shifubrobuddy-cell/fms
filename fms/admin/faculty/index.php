<?php
/**
 * Faculty Management System (FMS)
 * Admin: Faculty Directory (Matches Screenshot Design)
 */

require_once __DIR__ . '/../../includes/admin-auth.php';

$pageTitle = 'Faculty';
$activeMenu = 'faculty';
$db = getDB();

// Filters & Search Parameters
$search = trim($_GET['search'] ?? '');
$departmentId = (int)($_GET['department_id'] ?? 0);
$status = trim($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 5; // Matches screenshot "Showing 1 to 5 of 32 entries"
$offset = ($page - 1) * $perPage;

// Fetch departments for filter dropdown
$departments = [];
try {
    $deptStmt = $db->query("SELECT id, dept_code, dept_name FROM departments ORDER BY dept_name ASC");
    $departments = $deptStmt->fetchAll();
} catch (Exception $e) {}

// Build query conditions
$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(f.full_name LIKE :s1 OR f.emp_id LIKE :s2 OR f.email LIKE :s3 OR f.phone LIKE :s4)";
    $term = "%{$search}%";
    $params[':s1'] = $term;
    $params[':s2'] = $term;
    $params[':s3'] = $term;
    $params[':s4'] = $term;
}

if ($departmentId > 0) {
    $where[] = "f.department_id = :dept_id";
    $params[':dept_id'] = $departmentId;
}

if ($status !== '') {
    $where[] = "u.status = :status";
    $params[':status'] = $status;
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Count total records for pagination
$totalRecords = 0;
try {
    $countSql = "
        SELECT COUNT(*) 
        FROM faculty f 
        JOIN departments d ON f.department_id = d.id 
        JOIN users u ON f.user_id = u.id 
        {$whereClause}
    ";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = (int)$countStmt->fetchColumn();
} catch (Exception $e) {}

$totalPages = max(1, ceil($totalRecords / $perPage));
if ($page > $totalPages && $totalRecords > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

// Fetch faculty records ordered by emp_id ASC (FMS001, FMS002, etc. as in screenshot)
$facultyList = [];
try {
    $sql = "
        SELECT f.*, 
               d.dept_code, 
               d.dept_name, 
               u.username, 
               u.status AS account_status
        FROM faculty f
        JOIN departments d ON f.department_id = d.id
        JOIN users u ON f.user_id = u.id
        {$whereClause}
        ORDER BY f.emp_id ASC, f.id ASC
        LIMIT {$perPage} OFFSET {$offset}
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $facultyList = $stmt->fetchAll();
} catch (Exception $e) {}

include __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumb & Header -->
<div style="margin-bottom: 20px;">
    <div style="font-size: 12.5px; color: #64748B; margin-bottom: 4px;">
        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" style="color: #64748B;">Dashboard</a> &rsaquo; 
        <span style="color: #0F172A; font-weight: 600;">Faculty</span>
    </div>
    <h2 style="font-size: 22px; font-weight: 800; color: #0F172A; margin: 0;">Faculty List</h2>
</div>

<!-- Filter Bar Matching Screenshot -->
<div class="card" style="margin-bottom: 20px; border-radius: 12px;">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" action="" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; flex: 1;">
                <!-- Search Input -->
                <div style="position: relative; min-width: 260px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94A3B8;">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input 
                        type="text" 
                        name="search" 
                        class="form-control" 
                        style="padding-left: 36px; width: 100%; border-radius: 8px;" 
                        placeholder="Search by name, email or ID..." 
                        value="<?php echo escape($search); ?>"
                    >
                </div>

                <!-- Department Select -->
                <select name="department_id" class="form-select" style="min-width: 170px; border-radius: 8px;" onchange="this.form.submit()">
                    <option value="0">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo (int)$dept['id']; ?>" <?php echo ($departmentId === (int)$dept['id']) ? 'selected' : ''; ?>>
                            <?php echo escape($dept['dept_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Status Select -->
                <select name="status" class="form-select" style="min-width: 130px; border-radius: 8px;" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="active" <?php echo ($status === 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo ($status === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                </select>

                <button type="submit" class="btn btn-secondary" style="border-radius: 8px; padding: 8px 14px;">
                    Filter
                </button>
                <?php if ($search !== '' || $departmentId > 0 || $status !== ''): ?>
                    <a href="<?php echo BASE_URL; ?>admin/faculty/index.php" class="btn btn-secondary" style="border-radius: 8px; color: #64748B;">Reset</a>
                <?php endif; ?>
            </div>

            <!-- Add Faculty Purple Button -->
            <a href="<?php echo BASE_URL; ?>admin/faculty/add.php" class="btn btn-primary" style="background: #4F46E5; border: none; padding: 9px 18px; border-radius: 8px; font-weight: 700; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <span>Add Faculty</span>
            </a>
        </form>
    </div>
</div>

<!-- Faculty Table Card -->
<div class="card" style="border-radius: 12px; overflow: hidden;">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 60px;">Photo</th>
                    <th style="width: 110px;">Employee ID</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th style="width: 90px;">Status</th>
                    <th style="text-align: right; width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($facultyList)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px; color: #64748B;">
                            No faculty records found matching your filters.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($facultyList as $fac): ?>
                        <tr>
                            <td>
                                <img 
                                    src="<?php echo escape(getSafeAvatar($fac['photo'], $fac['full_name'])); ?>" 
                                    alt="<?php echo escape($fac['full_name']); ?>" 
                                    style="width: 38px; height: 38px; border-radius: 10px; object-fit: cover; border: 1px solid #E2E8F0; background: #1E293B;"
                                >
                            </td>
                            <td style="font-weight: 700; color: #0F172A;">
                                <?php echo escape($fac['emp_id']); ?>
                            </td>
                            <td style="font-weight: 700; color: #0F172A;">
                                <?php echo escape($fac['full_name']); ?>
                            </td>
                            <td style="color: #475569;">
                                <?php echo escape($fac['dept_name']); ?>
                            </td>
                            <td style="color: #475569;">
                                <?php echo escape($fac['designation']); ?>
                            </td>
                            <td style="color: #64748B;">
                                <?php echo escape($fac['email']); ?>
                            </td>
                            <td style="color: #64748B;">
                                <?php echo escape($fac['phone']); ?>
                            </td>
                            <td>
                                <?php if ($fac['account_status'] === 'active'): ?>
                                    <span class="badge badge-active">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-inactive">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px; align-items: center;">
                                    <a href="<?php echo BASE_URL; ?>admin/faculty/view.php?id=<?php echo (int)$fac['id']; ?>" class="btn-icon-action" title="View Details">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>admin/faculty/edit.php?id=<?php echo (int)$fac['id']; ?>" class="btn-icon-action" title="Edit Profile" style="color: #2563EB;">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>admin/faculty/delete.php?id=<?php echo (int)$fac['id']; ?>" class="btn-icon-action" title="Delete Faculty" style="color: #EF4444;" onclick="return confirm('Are you sure you want to remove <?php echo escape(addslashes($fac['full_name'])); ?>?');">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"/>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Matching Screenshot "Showing 1 to 5 of 32 entries" -->
    <div class="pagination-wrapper">
        <div>
            Showing <?php echo min($offset + 1, $totalRecords); ?> to <?php echo min($offset + $perPage, $totalRecords); ?> of <?php echo $totalRecords; ?> entries
        </div>
        <div class="pagination-pages">
            <!-- Prev -->
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search); ?>&department_id=<?php echo $departmentId; ?>&status=<?php echo urlencode($status); ?>" class="page-btn">&lsaquo;</a>
            <?php else: ?>
                <span class="page-btn" style="opacity: 0.4; cursor: not-allowed;">&lsaquo;</span>
            <?php endif; ?>

            <!-- Numbered Pages -->
            <?php for ($p = 1; $p <= min(3, $totalPages); $p++): ?>
                <a href="?page=<?php echo $p; ?>&search=<?php echo urlencode($search); ?>&department_id=<?php echo $departmentId; ?>&status=<?php echo urlencode($status); ?>" class="page-btn <?php echo ($p === $page) ? 'active' : ''; ?>">
                    <?php echo $p; ?>
                </a>
            <?php endfor; ?>

            <?php if ($totalPages > 4): ?>
                <span style="padding: 0 4px; color: #94A3B8;">...</span>
                <a href="?page=<?php echo $totalPages; ?>&search=<?php echo urlencode($search); ?>&department_id=<?php echo $departmentId; ?>&status=<?php echo urlencode($status); ?>" class="page-btn <?php echo ($page === $totalPages) ? 'active' : ''; ?>">
                    <?php echo $totalPages; ?>
                </a>
            <?php endif; ?>

            <!-- Next -->
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search); ?>&department_id=<?php echo $departmentId; ?>&status=<?php echo urlencode($status); ?>" class="page-btn">&rsaquo;</a>
            <?php else: ?>
                <span class="page-btn" style="opacity: 0.4; cursor: not-allowed;">&rsaquo;</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
