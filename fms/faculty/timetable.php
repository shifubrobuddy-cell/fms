<?php
/**
 * Faculty Management System (FMS)
 * Faculty: Personal Weekly Lecture Timetable Schedule
 */

require_once __DIR__ . '/../includes/faculty-auth.php';

$pageTitle = 'My Lecture Schedule';
$activeMenu = 'timetable';
$db = getDB();

$facultyId = (int)$_SESSION['faculty_id'];

$daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Fetch slots for this faculty
$slots = [];
try {
    $sql = "
        SELECT t.*, 
               s.subject_code, s.subject_name, s.credits,
               d.dept_code, d.dept_name
        FROM timetable t
        JOIN subjects s ON t.subject_id = s.id
        JOIN departments d ON t.department_id = d.id
        WHERE t.faculty_id = :fac_id
        ORDER BY t.start_time ASC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([':fac_id' => $facultyId]);
    $slots = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Faculty Timetable Error: " . $e->getMessage());
}

// Group slots by Day
$daySlots = [];
foreach ($daysOfWeek as $day) {
    $daySlots[$day] = [];
}
foreach ($slots as $slot) {
    if (isset($daySlots[$slot['day_of_week']])) {
        $daySlots[$slot['day_of_week']][] = $slot;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="content-header">
    <div>
        <h2 style="font-size: 20px; font-weight: 700; color: var(--text); margin: 0 0 4px;">My Weekly Teaching Timetable</h2>
        <p style="color: var(--text-muted); font-size: 14px; margin: 0;">Assigned instructional lectures across weekdays for <?php echo escape(ACADEMIC_YEAR); ?>.</p>
    </div>
    <div class="no-print">
        <button type="button" class="btn btn-secondary" onclick="window.print();">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"/>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                <rect x="6" y="14" width="12" height="8"/>
            </svg>
            Print My Schedule
        </button>
    </div>
</div>

<!-- Print Header -->
<div class="print-only" style="margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px;">
    <h2 style="margin: 0; font-size: 18px; text-transform: uppercase;"><?php echo escape(APP_NAME); ?></h2>
    <div style="font-size: 14px; margin-top: 4px;">
        Instructor: <strong><?php echo escape($_SESSION['full_name']); ?></strong> (<?php echo escape($_SESSION['emp_id']); ?>) &bull; Academic Year <?php echo escape(ACADEMIC_YEAR); ?>
    </div>
</div>

<!-- Weekly Grid Layout -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px;">
    <?php foreach ($daysOfWeek as $day): ?>
        <div class="card" style="display: flex; flex-direction: column;">
            <div class="card-header" style="background: #F8FAFC; padding: 12px 16px; border-bottom: 2px solid var(--border);">
                <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                    <div style="font-weight: 700; color: var(--text); font-size: 15px;">
                        <?php echo $day; ?>
                    </div>
                    <span class="badge <?php echo !empty($daySlots[$day]) ? 'badge-info' : ''; ?>" style="font-size: 11px;">
                        <?php echo count($daySlots[$day]); ?> Lecture(s)
                    </span>
                </div>
            </div>

            <div class="card-body" style="padding: 12px; flex: 1; display: flex; flex-direction: column; gap: 10px; background: #FAFBFD;">
                <?php if (empty($daySlots[$day])): ?>
                    <div style="text-align: center; color: var(--text-muted); font-size: 13px; padding: 24px 0; margin: auto;">
                        No lectures scheduled
                    </div>
                <?php else: ?>
                    <?php foreach ($daySlots[$day] as $slot): ?>
                        <div style="background: var(--surface); border: 1px solid var(--border); border-left: 4px solid var(--primary-light); border-radius: 6px; padding: 10px 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
                                <div style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700; font-size: 12px; color: var(--primary-dark);">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"/>
                                        <polyline points="12 6 12 12 16 14"/>
                                    </svg>
                                    <?php echo date('h:i A', strtotime($slot['start_time'])); ?> &ndash; <?php echo date('h:i A', strtotime($slot['end_time'])); ?>
                                </div>
                                <span class="badge badge-warning" style="font-size: 11px; padding: 1px 6px;">
                                    Room <?php echo escape($slot['room_number']); ?>
                                </span>
                            </div>

                            <div style="margin-top: 6px;">
                                <div style="font-weight: 700; color: var(--text); font-size: 14px;">
                                    <?php echo escape($slot['subject_code']); ?>
                                </div>
                                <div style="font-size: 13px; color: var(--text-muted); line-height: 1.3;">
                                    <?php echo escape($slot['subject_name']); ?>
                                </div>
                            </div>

                            <div style="margin-top: 8px; padding-top: 6px; border-top: 1px dashed var(--border); font-size: 12px; color: var(--text-muted);">
                                Class: <strong style="color: var(--text);"><?php echo escape($slot['dept_code']); ?> Sem <?php echo (int)$slot['semester']; ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
