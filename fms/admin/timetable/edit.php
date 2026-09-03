<?php
/**
 * Faculty Management System (FMS)
 * Admin: Edit Timetable Lecture Slot
 */

require_once __DIR__ . '/../../includes/admin-auth.php';

$pageTitle = 'Edit Timetable Slot';
$activeMenu = 'timetable';
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlashMessage('danger', 'Invalid timetable slot reference.');
    header('Location: ' . BASE_URL . 'admin/timetable/index.php');
    exit;
}

// Fetch existing slot
try {
    $stmt = $db->prepare("SELECT * FROM timetable WHERE id = ?");
    $stmt->execute([$id]);
    $slot = $stmt->fetch();

    if (!$slot) {
        setFlashMessage('danger', 'Timetable slot not found.');
        header('Location: ' . BASE_URL . 'admin/timetable/index.php');
        exit;
    }
} catch (Exception $e) {
    setFlashMessage('danger', 'Error loading slot.');
    header('Location: ' . BASE_URL . 'admin/timetable/index.php');
    exit;
}

// Fetch departments, faculty, subjects
$departments = $db->query("SELECT id, dept_code, dept_name FROM departments ORDER BY dept_code ASC")->fetchAll();
$facultyList = $db->query("SELECT id, full_name, emp_id, department_id FROM faculty ORDER BY full_name ASC")->fetchAll();
$subjectsList = $db->query("SELECT id, subject_code, subject_name, semester, department_id FROM subjects ORDER BY department_id ASC, semester ASC, subject_code ASC")->fetchAll();

$errors = [];
$departmentId = (int)$slot['department_id'];
$semester = (int)$slot['semester'];
$facultyId = (int)$slot['faculty_id'];
$subjectId = (int)$slot['subject_id'];
$dayOfWeek = $slot['day_of_week'];
$startTime = $slot['start_time'];
$endTime = $slot['end_time'];
$roomNumber = $slot['room_number'];

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

    // Overlap conflict checks excluding current slot
    if (empty($errors)) {
        try {
            // 1. Faculty Conflict
            $fConflict = $db->prepare("
                SELECT t.*, s.subject_code, d.dept_code 
                FROM timetable t
                JOIN subjects s ON t.subject_id = s.id
                JOIN departments d ON t.department_id = d.id
                WHERE t.faculty_id = ? AND t.day_of_week = ? 
                  AND (t.start_time < ? AND t.end_time > ?)
                  AND t.id != ?
                LIMIT 1
            ");
            $fConflict->execute([$facultyId, $dayOfWeek, $endTime, $startTime, $id]);
            $cf = $fConflict->fetch();
            if ($cf) {
                $errors[] = "Faculty Conflict: This instructor is already teaching '{$cf['subject_code']}' on {$dayOfWeek} from {$cf['start_time']} to {$cf['end_time']}.";
            }

            // 2. Room Conflict
            $rConflict = $db->prepare("
                SELECT t.*, s.subject_code, f.full_name
                FROM timetable t
                JOIN subjects s ON t.subject_id = s.id
                JOIN faculty f ON t.faculty_id = f.id
                WHERE t.room_number = ? AND t.day_of_week = ? 
                  AND (t.start_time < ? AND t.end_time > ?)
                  AND t.id != ?
                LIMIT 1
            ");
            $rConflict->execute([$roomNumber, $dayOfWeek, $endTime, $startTime, $id]);
            $cr = $rConflict->fetch();
            if ($cr) {
                $errors[] = "Room Conflict: Room '{$roomNumber}' is occupied by {$cr['full_name']} ('{$cr['subject_code']}') during this slot.";
            }

            // 3. Class Conflict
            $cConflict = $db->prepare("
                SELECT t.*, s.subject_code
                FROM timetable t
                JOIN subjects s ON t.subject_id = s.id
                WHERE t.department_id = ? AND t.semester = ? AND t.day_of_week = ? 
                  AND (t.start_time < ? AND t.end_time > ?)
                  AND t.id != ?
                LIMIT 1
            ");
            $cConflict->execute([$departmentId, $semester, $dayOfWeek, $endTime, $startTime, $id]);
            $cc = $cConflict->fetch();
            if ($cc) {
                $errors[] = "Class Conflict: This class already has another lecture ('{$cc['subject_code']}') scheduled at this time.";
            }
        } catch (Exception $e) {
            $errors[] = 'Conflict validation error: ' . $e->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            $updateStmt = $db->prepare("
                UPDATE timetable 
                SET faculty_id = ?, subject_id = ?, department_id = ?, day_of_week = ?, start_time = ?, end_time = ?, room_number = ?, semester = ?
                WHERE id = ?
            ");
            $updateStmt->execute([$facultyId, $subjectId, $departmentId, $dayOfWeek, $startTime, $endTime, $roomNumber, $semester, $id]);

            setFlashMessage('success', "Timetable lecture slot updated successfully.");
            header("Location: " . BASE_URL . "admin/timetable/index.php?department_id={$departmentId}&semester={$semester}");
            exit;
        } catch (Exception $e) {
            error_log("Timetable Update Error: " . $e->getMessage());
            $errors[] = 'Failed to update slot: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="content-header">
    <div>
        <h2 style="font-size: 20px; font-weight: 700; color: var(--text); margin: 0 0 4px;">Edit Timetable Slot</h2>
        <p style="color: var(--text-muted); font-size: 14px; margin: 0;">Adjust period timing, venue, or instructor assignment.</p>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>admin/timetable/index.php?department_id=<?php echo $departmentId; ?>&semester=<?php echo $semester; ?>" class="btn btn-secondary">
            &larr; Back to Timetable
        </a>
    </div>
</div>

<div class="card" style="max-width: 740px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title">Modify Lecture Period</h3>
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
                    Save Slot Changes
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
