<?php
/**
 * Faculty Management System (FMS)
 * Admin: Add New Faculty Member
 * Stepper-based professional registration interface matching screenshot
 */

require_once __DIR__ . '/../../includes/admin-auth.php';

$pageTitle = 'Faculty';
$activeMenu = 'faculty';
$db = getDB();

// Fetch departments for dropdown
$departments = [];
try {
    $deptStmt = $db->query("SELECT id, dept_code, dept_name FROM departments ORDER BY dept_name ASC");
    $departments = $deptStmt->fetchAll();
} catch (Exception $e) {}

$errors = [];
$firstName = '';
$middleName = '';
$lastName = '';
$gender = 'Male';
$dob = '1985-05-15';
$email = '';
$phone = '';
$altPhone = '';
$address = '';
$city = 'Mumbai';
$state = 'Maharashtra';
$postalCode = '400001';
$empId = '';
$departmentId = 0;
$designation = 'Assistant Professor';
$qualification = 'Ph.D. in Computer Science';
$joiningDate = date('Y-m-d');
$username = '';
$password = '';

// Generate next suggested Emp ID
try {
    $maxEmp = (int)$db->query("SELECT COUNT(*) FROM faculty")->fetchColumn() + 1;
    $empId = sprintf("FMS%03d", $maxEmp);
} catch (Exception $e) {
    $empId = 'FMS033';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        $errors[] = 'Security token expired. Please reload and try again.';
    }

    $firstName = trim($_POST['first_name'] ?? '');
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $gender = trim($_POST['gender'] ?? 'Male');
    $dob = trim($_POST['dob'] ?? '');
    
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $altPhone = trim($_POST['alt_phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $postalCode = trim($_POST['postal_code'] ?? '');

    $empId = strtoupper(trim($_POST['emp_id'] ?? ''));
    $departmentId = (int)($_POST['department_id'] ?? 0);
    $designation = trim($_POST['designation'] ?? '');
    $qualification = trim($_POST['qualification'] ?? '');
    $joiningDate = trim($_POST['joining_date'] ?? '');

    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    // Synthesize full name
    $fullName = trim("Dr. " . $firstName . ' ' . ($middleName ? $middleName . ' ' : '') . $lastName);

    // Validations
    if ($firstName === '' || $lastName === '') {
        $errors[] = 'First Name and Last Name are required.';
    }
    if ($empId === '') {
        $errors[] = 'Employee ID is required.';
    }
    if ($departmentId <= 0) {
        $errors[] = 'Please select a department.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if ($phone === '') {
        $errors[] = 'Phone contact number is required.';
    }
    if ($username === '') {
        $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $firstName . '.' . $lastName));
    }
    if ($password === '') {
        $password = 'Faculty@123';
    }

    // Default AI Bot avatar (strictly vector AI Bot, no human pictures)
    $photoUrl = getAIBotAvatarUrl($fullName);

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // 1. Create User account
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $userStmt = $db->prepare("INSERT INTO users (username, password, role, status) VALUES (?, ?, 'faculty', 'active')");
            $userStmt->execute([$username, $passwordHash]);
            $userId = (int)$db->lastInsertId();

            // 2. Create Faculty record
            $facStmt = $db->prepare("
                INSERT INTO faculty (
                    user_id, department_id, emp_id, full_name, email, phone, 
                    designation, qualification, joining_date, photo
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $facStmt->execute([
                $userId, $departmentId, $empId, $fullName, $email, $phone,
                $designation, $qualification, $joiningDate, $photoUrl
            ]);
            $facultyId = (int)$db->lastInsertId();

            $db->commit();

            setFlashMessage('success', "Faculty member {$fullName} ({$empId}) registered successfully.");
            header('Location: ' . BASE_URL . 'admin/faculty/index.php');
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Failed to register faculty profile: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumb & Header -->
<div style="margin-bottom: 24px;">
    <div style="font-size: 12.5px; color: #64748B; margin-bottom: 4px;">
        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" style="color: #64748B;">Dashboard</a> &rsaquo; 
        <a href="<?php echo BASE_URL; ?>admin/faculty/index.php" style="color: #64748B;">Faculty</a> &rsaquo;
        <span style="color: #0F172A; font-weight: 600;">Add Faculty</span>
    </div>
    <h2 style="font-size: 22px; font-weight: 800; color: #0F172A; margin: 0;">Add Faculty</h2>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger" style="margin-bottom: 20px; background: #FEE2E2; color: #B91C1C; padding: 14px 18px; border-radius: 8px; border: 1px solid #FECACA;">
        <ul style="margin: 0; padding-left: 20px;">
            <?php foreach ($errors as $err): ?>
                <li><?php echo escape($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- 2-Column Stepper Form Layout Matching Screenshot -->
<div style="display: grid; grid-template-columns: 260px 1fr; gap: 24px; align-items: start;">
    <!-- Left Column: Stepper -->
    <div class="card" style="border-radius: 12px; padding: 16px;">
        <div style="display: flex; flex-direction: column; gap: 6px;">
            <!-- Step 1: Active -->
            <div style="display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 8px; background: #EEF2FF; color: #4F46E5; font-weight: 700;">
                <div style="width: 28px; height: 28px; border-radius: 50%; background: #4F46E5; color: #FFFFFF; display: flex; align-items: center; justify-content: center; font-size: 12px;">1</div>
                <span style="font-size: 13.5px;">Personal Information</span>
            </div>

            <!-- Step 2 -->
            <div style="display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 8px; color: #64748B; font-weight: 500;">
                <div style="width: 28px; height: 28px; border-radius: 50%; background: #F1F5F9; color: #64748B; display: flex; align-items: center; justify-content: center; font-size: 12px;">2</div>
                <span style="font-size: 13.5px;">Contact Information</span>
            </div>

            <!-- Step 3 -->
            <div style="display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 8px; color: #64748B; font-weight: 500;">
                <div style="width: 28px; height: 28px; border-radius: 50%; background: #F1F5F9; color: #64748B; display: flex; align-items: center; justify-content: center; font-size: 12px;">3</div>
                <span style="font-size: 13.5px;">Professional Information</span>
            </div>

            <!-- Step 4 -->
            <div style="display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 8px; color: #64748B; font-weight: 500;">
                <div style="width: 28px; height: 28px; border-radius: 50%; background: #F1F5F9; color: #64748B; display: flex; align-items: center; justify-content: center; font-size: 12px;">4</div>
                <span style="font-size: 13.5px;">Account Information</span>
            </div>

            <!-- Step 5 -->
            <div style="display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 8px; color: #64748B; font-weight: 500;">
                <div style="width: 28px; height: 28px; border-radius: 50%; background: #F1F5F9; color: #64748B; display: flex; align-items: center; justify-content: center; font-size: 12px;">5</div>
                <span style="font-size: 13.5px;">Other Details</span>
            </div>
        </div>
    </div>

    <!-- Right Column: Form Fields -->
    <div class="card" style="border-radius: 12px;">
        <div class="card-body" style="padding: 28px;">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <!-- 1. Personal Information -->
                <h3 style="font-size: 16px; font-weight: 700; color: #0F172A; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 1px solid #E2E8F0;">
                    Personal Information
                </h3>
                
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label class="form-label">First Name <span style="color: #EF4444;">*</span></label>
                        <input type="text" name="first_name" class="form-control" placeholder="Enter first name" value="<?php echo escape($firstName ?: 'Rajesh'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control" placeholder="Enter middle name" value="<?php echo escape($middleName); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name <span style="color: #EF4444;">*</span></label>
                        <input type="text" name="last_name" class="form-control" placeholder="Enter last name" value="<?php echo escape($lastName ?: 'Verma'); ?>" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                    <div class="form-group">
                        <label class="form-label">Gender <span style="color: #EF4444;">*</span></label>
                        <div style="display: flex; gap: 20px; align-items: center; padding-top: 6px;">
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 13.5px; cursor: pointer;">
                                <input type="radio" name="gender" value="Male" <?php echo ($gender === 'Male') ? 'checked' : ''; ?>> Male
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 13.5px; cursor: pointer;">
                                <input type="radio" name="gender" value="Female" <?php echo ($gender === 'Female') ? 'checked' : ''; ?>> Female
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 13.5px; cursor: pointer;">
                                <input type="radio" name="gender" value="Other" <?php echo ($gender === 'Other') ? 'checked' : ''; ?>> Other
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date of Birth <span style="color: #EF4444;">*</span></label>
                        <input type="date" name="dob" class="form-control" value="<?php echo escape($dob); ?>" required>
                    </div>
                </div>

                <!-- 2. Contact Information -->
                <h3 style="font-size: 16px; font-weight: 700; color: #0F172A; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 1px solid #E2E8F0;">
                    Contact Information
                </h3>

                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label class="form-label">Email <span style="color: #EF4444;">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="name@college.edu" value="<?php echo escape($email ?: 'rajesh.verma@fms.local'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone <span style="color: #EF4444;">*</span></label>
                        <input type="tel" name="phone" class="form-control" placeholder="e.g. 9876543210" value="<?php echo escape($phone ?: '9876543210'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Alternate Phone</label>
                        <input type="tel" name="alt_phone" class="form-control" placeholder="Optional" value="<?php echo escape($altPhone); ?>">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" class="form-control" placeholder="Residential address" value="<?php echo escape($address ?: '42 Academic Enclave'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">City <span style="color: #EF4444;">*</span></label>
                        <input type="text" name="city" class="form-control" value="<?php echo escape($city); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">State <span style="color: #EF4444;">*</span></label>
                        <input type="text" name="state" class="form-control" value="<?php echo escape($state); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Postal Code <span style="color: #EF4444;">*</span></label>
                        <input type="text" name="postal_code" class="form-control" value="<?php echo escape($postalCode); ?>" required>
                    </div>
                </div>

                <!-- 3. Professional Information -->
                <h3 style="font-size: 16px; font-weight: 700; color: #0F172A; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 1px solid #E2E8F0;">
                    Professional Information
                </h3>

                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label class="form-label">Employee ID <span style="color: #EF4444;">*</span></label>
                        <input type="text" name="emp_id" class="form-control" value="<?php echo escape($empId); ?>" required style="font-weight: 700;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Department <span style="color: #EF4444;">*</span></label>
                        <select name="department_id" class="form-select" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?php echo (int)$d['id']; ?>" <?php echo ($departmentId === (int)$d['id'] || $d['dept_code'] === 'CSE') ? 'selected' : ''; ?>>
                                    <?php echo escape($d['dept_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Designation <span style="color: #EF4444;">*</span></label>
                        <select name="designation" class="form-select" required>
                            <option value="Professor" <?php echo ($designation === 'Professor') ? 'selected' : ''; ?>>Professor</option>
                            <option value="Associate Professor" <?php echo ($designation === 'Associate Professor') ? 'selected' : ''; ?>>Associate Professor</option>
                            <option value="Assistant Professor" <?php echo ($designation === 'Assistant Professor') ? 'selected' : ''; ?>>Assistant Professor</option>
                            <option value="Lecturer" <?php echo ($designation === 'Lecturer') ? 'selected' : ''; ?>>Lecturer</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                    <div class="form-group">
                        <label class="form-label">Highest Qualification <span style="color: #EF4444;">*</span></label>
                        <input type="text" name="qualification" class="form-control" value="<?php echo escape($qualification); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date of Joining <span style="color: #EF4444;">*</span></label>
                        <input type="date" name="joining_date" class="form-control" value="<?php echo escape($joiningDate); ?>" required>
                    </div>
                </div>

                <!-- 4. Account Information -->
                <h3 style="font-size: 16px; font-weight: 700; color: #0F172A; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 1px solid #E2E8F0;">
                    Account Information
                </h3>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                    <div class="form-group">
                        <label class="form-label">Username <span style="color: #EF4444;">*</span></label>
                        <input type="text" name="username" class="form-control" placeholder="portal.username" value="<?php echo escape($username ?: 'rajesh.verma'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Initial Password <span style="color: #EF4444;">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Faculty@123" value="<?php echo escape($password ?: 'Faculty@123'); ?>" required>
                    </div>
                </div>

                <!-- Form Bottom Actions -->
                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 18px; border-top: 1px solid #E2E8F0;">
                    <a href="<?php echo BASE_URL; ?>admin/faculty/index.php" class="btn btn-secondary" style="padding: 10px 20px; border-radius: 8px;">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary" style="background: #4F46E5; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 700; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);">
                        Save Faculty
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
