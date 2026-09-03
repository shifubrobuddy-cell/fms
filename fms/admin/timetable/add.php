<?php
/**
 * Faculty Management System (FMS)
 * Admin: Add Timetable Lecture Slot with Overlap Conflict Checks
 */

require_once __DIR__ . '/../../includes/admin-auth.php';

$pageTitle = 'Schedule Lecture Slot';
$activeMenu = 'timetable';
$db = getDB();

$departmentId = (int)($_GET['department_id'] ?? 0);
$semester = (int)($_GET['semester'] ?? 1);
$facultyId = (int)($_GET['faculty_id'] ?? 0);
$dayOfWeek = $_GET['day'] ?? 'Monday';
$startTime = '09:00';
$endTime = '10:00';
$roomNumber = 'Lab-101';
$subjectId = 0;

// Fetch departments
$departments = $db->query("SELECT id, dept_code, dept_name FROM departments ORDER BY dept_code ASC")->fetchAll();
if ($departmentId <= 0 && !empty($departments)) {
    $departmentId = (int)$departments[0]['id'];
}

// Fetch faculty
$facultyList = $db->query("SELECT id, full_name, emp_id, department_id FROM faculty ORDER BY full_name ASC")->fetchAll();

// Fetch subjects
$subjectsList = $db->query("SELECT id, subject_code, subject_name, semester, department_id FROM subjects ORDER BY department_id ASC, semester ASC, subject_code ASC")->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        $errors[] = 'Invalid security session. Please try again.';
    }

    $departmentId = (int)($_POST['department_id'] ?? 0);
    $semester = (int)($_POST['semester'] ?? 1);
    $facultyId = (int)($_POST['faculty_id'] ?? 0);
    $subjectId = (int)($_POST['subject_id'] ?? 0);
    $dayOfWeek = trim($_POST['day_of_week'] ?? 'Monday');
    $startTime = trim($_POST['start_time'] ?? '');
    $endTime = trim($_POST['end_time'] ?? '');
    $roomNumber = strtoupper(trim($_POST['room_number'] ?? ''));

    // Basic validation
    if ($departmentId <= 0) $errors[] = 'Please select a department.';
    if ($semester < 1 || $semester > 8) $errors[] = 'Semester must be between 1 and 8.';
    if ($facultyId <= 0) $errors[] = 'Please choose a teaching faculty member.';
    if ($subjectId <= 0) $errors[] = 'Please select a subject.';
    if (!in_array($dayOfWeek, ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'])) {
        $errors[] = 'Invalid day of week selected.';
    }
    if (empty($startTime) || empty($endTime)) {
        $errors[] = 'Start time and end time are required.';
    } elseif (strtotime($startTime) >= strtotime($endTime)) {
        $errors[] = 'Lecture end time must be after the start time.';
    }
    if (empty($roomNumber)) {
        $errors[] = 'Room / Laboratory number is required.';
    }

    // Advanced Schedule Conflict Checks
    if (empty($errors)) {
        try {
            // 1. Faculty Conflict Check
            $fConflict = $db->prepare("
                SELECT t.*, s.subject_code, d.dept_code 
                FROM timetable t
                JOIN subjects s ON t.subject_id = s.id
                JOIN departments d ON t.department_id = d.id
                WHERE t.faculty_id = ? AND t.day_of_week = ? 
                  AND (t.start_time < ? AND t.end_time > ?)
                LIMIT 1
            ");
            $fConflict->execute([$facultyId, $dayOfWeek, $endTime, $startTime]);
            $cf = $fConflict->fetch();
            if ($cf) {
                $errors[] = "Faculty Conflict: This instructor is already assigned to lecture '{$cf['subject_code']}' ({$cf['dept_code']} Sem {$cf['semester']}) on {$dayOfWeek} between {$cf['start_time']} and {$cf['end_time']}.";
            }

            // 2. Room Conflict Check
            $rConflict = $db->prepare("
                SELECT t.*, s.subject_code, f.full_name
                FROM timetable t
                JOIN subjects s ON t.subject_id = s.id
                JOIN faculty f ON t.faculty_id = f.id
                WHERE t.room_number = ? AND t.day_of_week = ? 
                  AND (t.start_time < ? AND t.end_time > ?)
                LIMIT 1
            ");
            $rConflict->execute([$roomNumber, $dayOfWeek, $endTime, $startTime]);
            $cr = $rConflict->fetch();
            if ($cr) {
                $errors[] = "Room Conflict: Room '{$roomNumber}' is already booked for '{$cr['subject_code']}' taught by {$cr['full_name']} at this time.";
            }

            // 3. Class/Semester Conflict Check
            $cConflict = $db->prepare("
                SELECT t.*, s.subject_code
                FROM timetable t
                JOIN subjects s ON t.subject_id = s.id
                WHERE t.department_id = ? AND t.semester = ? AND t.day_of_week = ? 
                  AND (t.start_time < ? AND t.end_time > ?)
                LIMIT 1
            ");
            $cConflict->execute([$departmentId, $semester, $dayOfWeek, $endTime, $startTime]);
            $cc = $cConflict->fetch();
            if ($cc) {
                $errors[] = "Class Conflict: Semester {$semester} already has another session scheduled ('{$cc['subject_code']}') during this time window.";
            }
        } catch (Exception $e) {
            $errors[] = 'Conflict validation query failed: ' . $e->getMessage();
        }
    }

    // Insert slot
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                INSERT INTO timetable (faculty_id, subject_id, department_id, day_of_week, start_time, end_time, room_number, semester)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$facultyId, $subjectId, $departmentId, $dayOfWeek, $startTime, $endTime, $roomNumber, $semester]);

            setFlashMessage('success', "Timetable lecture slot successfully scheduled.");
            header("Location: " . BASE_URL . "admin/timetable/index.php?department_id={$departmentId}&semester={$semester}");
            exit;
        } catch (Exception $e) {
            error_log("Timetable Insert Error: " . $e->getMessage());
            $errors[] = 'Failed to create slot: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-header">
    <div>
        <h2 style="font-size: 20px; font-weight: 700; color: var(--text); margin: 0 0 4px;">Schedule Lecture Slot</h2>
        <p style="color: var(--text-muted); font-size: 14px; margin: 0;">Add a new lecture period with real-time room and instructor clash detection.</p>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>admin/timetable/index.php?department_id=<?php echo $departmentId; ?>&semester=<?php echo $semester; ?>" class="btn btn-secondary">
            &larr; Back to Timetable
        </a>
    </div>
</div>

<div class="card" style="max-width: 740px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title">Lecture Slot Information</h3>
        <span style="font-size: 13px; color: var(--text-muted);"><span style="color: var(--danger);">*</span> Required fields</span>
    </div>
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo escape($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                <div class="form-group">
                    <label for="department_id" class="form-label">
                        Department <span style="color: var(--danger);">*</span>
                    </label>
                    <select id="department_id" name="department_id" class="form-control" required>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?php echo (int)$d['id']; ?>" <?php echo ($departmentId === (int)$d['id']) ? 'selected' : ''; ?>>
                                <?php echo escape($d['dept_code']); ?> — <?php echo escape($d['dept_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="semester" class="form-label">
                        Semester <span style="color: var(--danger);">*</span>
                    </label>
                    <select id="semester" name="semester" class="form-control" required>
                        <?php for ($s = 1; $s <= 8; $s++): ?>
                            <option value="<?php echo $s; ?>" <?php echo ($semester === $s) ? 'selected' : ''; ?>>
                                Semester <?php echo $s; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="subject_id" class="form-label">
                        Curriculum Subject <span style="color: var(--danger);">*</span>
                    </label>
                    <select id="subject_id" name="subject_id" class="form-control" required>
                        <option value="">-- Choose Subject --</option>
                        <?php foreach ($subjectsList as $sub): ?>
                            <option value="<?php echo (int)$sub['id']; ?>" <?php echo ($subjectId === (int)$sub['id']) ? 'selected' : ''; ?>>
                                [Sem <?php echo (int)$sub['semester']; ?>] <?php echo escape($sub['subject_code']); ?> — <?php echo escape($sub['subject_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="faculty_id" class="form-label">
                        Lecturer / Instructor <span style="color: var(--danger);">*</span>
                    </label>
                    <select id="faculty_id" name="faculty_id" class="form-control" required>
                        <option value="">-- Choose Faculty Member --</option>
                        <?php foreach ($facultyList as $f): ?>
                            <option value="<?php echo (int)$f['id']; ?>" <?php echo ($facultyId === (int)$f['id']) ? 'selected' : ''; ?>>
                                <?php echo escape($f['full_name']); ?> (<?php echo escape($f['emp_id']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="day_of_week" class="form-label">
                        Day of Week <span style="color: var(--danger);">*</span>
                    </label>
                    <select id="day_of_week" name="day_of_week" class="form-control" required>
                        <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day): ?>
                            <option value="<?php echo $day; ?>" <?php echo ($dayOfWeek === $day) ? 'selected' : ''; ?>>
                                <?php echo $day; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="room_number" class="form-label">
                        Room / Hall / Lab <span style="color: var(--danger);">*</span>
                    </label>
                    <input type="text" 
                           id="room_number" 
                           name="room_number" 
                           class="form-control" 
                           placeholder="e.g. 204 or Lab-B" 
                           value="<?php echo escape($roomNumber); ?>" 
                           required 
                           style="text-transform: uppercase;">
                </div>

                <div class="form-group">
                    <label for="start_time" class="form-label">
                        Start Time <span style="color: var(--danger);">*</span>
                    </label>
                    <input type="time" 
                           id="start_time" 
                           name="start_time" 
                           class="form-control" 
                           value="<?php echo escape($startTime); ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="end_time" class="form-label">
                        End Time <span style="color: var(--danger);">*</span>
                    </label>
                    <input type="time" 
                           id="end_time" 
                           name="end_time" 
                           class="form-control" 
                           value="<?php echo escape($endTime); ?>" 
                           required>
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border);">
                <a href="<?php echo BASE_URL; ?>admin/timetable/index.php" class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Confirm &amp; Schedule Slot
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
