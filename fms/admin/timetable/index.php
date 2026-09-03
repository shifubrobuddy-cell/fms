<?php
/**
 * Faculty Management System (FMS)
 * Admin: Timetable Schedule (Matches Screenshot Design)
 */

require_once __DIR__ . '/../../includes/admin-auth.php';

$pageTitle = 'Timetable';
$activeMenu = 'timetable';
$db = getDB();

$deptId = (int)($_GET['department_id'] ?? 0);
$facultyId = (int)($_GET['faculty_id'] ?? 0);

// Fetch departments & faculty for filters
$departments = [];
$facultyList = [];
try {
    $departments = $db->query("SELECT id, dept_code, dept_name FROM departments ORDER BY dept_name ASC")->fetchAll();
    $facultyList = $db->query("SELECT id, full_name, emp_id FROM faculty ORDER BY full_name ASC")->fetchAll();
} catch (Exception $e) {}

// Days & Time Slots matching matrix
$daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$timeSlots = [
    '09:00 AM - 10:00 AM',
    '10:00 AM - 11:00 AM',
    '11:00 AM - 12:00 PM',
    '12:00 PM - 01:00 PM', // Lunch
    '01:00 PM - 02:00 PM',
    '02:00 PM - 03:00 PM',
    '03:00 PM - 04:00 PM'
];

// Seeded/Rich timetable schedule matrix items
$scheduleMatrix = [
    '09:00 AM - 10:00 AM' => [
        'Monday'    => ['subject' => 'Data Structures', 'faculty' => 'Demo 1', 'room' => 'CS-201', 'color' => 'slot-purple'],
        'Tuesday'   => ['subject' => 'Database Systems', 'faculty' => 'Demo 2', 'room' => 'CS-203', 'color' => 'slot-blue'],
        'Wednesday' => ['subject' => 'Operating Systems', 'faculty' => 'Demo 3', 'room' => 'CS-104', 'color' => 'slot-rose'],
        'Thursday'  => ['subject' => 'Data Structures', 'faculty' => 'Demo 1', 'room' => 'CS-201', 'color' => 'slot-purple'],
        'Friday'    => ['subject' => 'Computer Networks', 'faculty' => 'Demo 4', 'room' => 'CS-202', 'color' => 'slot-green'],
        'Saturday'  => ['subject' => 'Library / Seminar', 'faculty' => 'Faculty Team', 'room' => 'Hall A', 'color' => 'slot-amber'],
    ],
    '10:00 AM - 11:00 AM' => [
        'Monday'    => ['subject' => 'Object Oriented Prog', 'faculty' => 'Demo 5', 'room' => 'CS-202', 'color' => 'slot-rose'],
        'Tuesday'   => ['subject' => 'Software Engg', 'faculty' => 'Demo 6', 'room' => 'CS-301', 'color' => 'slot-green'],
        'Wednesday' => ['subject' => 'Web Development', 'faculty' => 'Demo 7', 'room' => 'Lab L-01', 'color' => 'slot-purple'],
        'Thursday'  => ['subject' => 'Database Systems', 'faculty' => 'Demo 2', 'room' => 'CS-203', 'color' => 'slot-blue'],
        'Friday'    => ['subject' => 'Object Oriented Prog', 'faculty' => 'Demo 5', 'room' => 'CS-202', 'color' => 'slot-rose'],
        'Saturday'  => ['subject' => 'Doubt Session', 'faculty' => 'Mentor Faculty', 'room' => 'CS-201', 'color' => 'slot-blue'],
    ],
    '11:00 AM - 12:00 PM' => [
        'Monday'    => ['subject' => 'Database Systems', 'faculty' => 'Demo 2', 'room' => 'CS-203', 'color' => 'slot-blue'],
        'Tuesday'   => ['subject' => 'Computer Networks', 'faculty' => 'Demo 4', 'room' => 'CS-202', 'color' => 'slot-green'],
        'Wednesday' => ['subject' => 'Data Structures', 'faculty' => 'Demo 1', 'room' => 'CS-201', 'color' => 'slot-purple'],
        'Thursday'  => ['subject' => 'Software Engg', 'faculty' => 'Demo 6', 'room' => 'CS-301', 'color' => 'slot-green'],
        'Friday'    => ['subject' => 'Web Development', 'faculty' => 'Demo 7', 'room' => 'Lab L-01', 'color' => 'slot-purple'],
        'Saturday'  => ['subject' => 'Project Review', 'faculty' => 'Dept Panel', 'room' => 'Hall B', 'color' => 'slot-amber'],
    ],
    '12:00 PM - 01:00 PM' => 'LUNCH',
    '01:00 PM - 02:00 PM' => [
        'Monday'    => ['subject' => 'Web Development', 'faculty' => 'Demo 7', 'room' => 'Lab L-01', 'color' => 'slot-purple'],
        'Tuesday'   => ['subject' => 'Data Structures Lab', 'faculty' => 'Demo 1', 'room' => 'Lab L-02', 'color' => 'slot-blue'],
        'Wednesday' => ['subject' => 'Database Lab', 'faculty' => 'Demo 2', 'room' => 'Lab L-03', 'color' => 'slot-green'],
        'Thursday'  => ['subject' => 'OS Lab Session', 'faculty' => 'Demo 3', 'room' => 'Lab L-02', 'color' => 'slot-rose'],
        'Friday'    => ['subject' => 'Networks Lab', 'faculty' => 'Demo 4', 'room' => 'Lab L-01', 'color' => 'slot-amber'],
        'Saturday'  => null,
    ],
    '02:00 PM - 03:00 PM' => [
        'Monday'    => ['subject' => 'Software Engg', 'faculty' => 'Demo 6', 'room' => 'CS-301', 'color' => 'slot-green'],
        'Tuesday'   => ['subject' => 'Data Structures Lab', 'faculty' => 'Demo 1', 'room' => 'Lab L-02', 'color' => 'slot-blue'],
        'Wednesday' => ['subject' => 'Database Lab', 'faculty' => 'Demo 2', 'room' => 'Lab L-03', 'color' => 'slot-green'],
        'Thursday'  => ['subject' => 'OS Lab Session', 'faculty' => 'Demo 3', 'room' => 'Lab L-02', 'color' => 'slot-rose'],
        'Friday'    => ['subject' => 'Networks Lab', 'faculty' => 'Demo 4', 'room' => 'Lab L-01', 'color' => 'slot-amber'],
        'Saturday'  => null,
    ],
    '03:00 PM - 04:00 PM' => [
        'Monday'    => ['subject' => 'Operating Systems', 'faculty' => 'Demo 3', 'room' => 'CS-104', 'color' => 'slot-rose'],
        'Tuesday'   => ['subject' => 'Tutorial Class', 'faculty' => 'Demo 8', 'room' => 'CS-201', 'color' => 'slot-amber'],
        'Wednesday' => ['subject' => 'Mentorship Hour', 'faculty' => 'All Faculty', 'room' => 'Dept Office', 'color' => 'slot-purple'],
        'Thursday'  => ['subject' => 'Extra Practice', 'faculty' => 'TA Team', 'room' => 'Lab L-01', 'color' => 'slot-blue'],
        'Friday'    => ['subject' => 'Technical Seminar', 'faculty' => 'Guest Speaker', 'room' => 'Auditorium', 'color' => 'slot-green'],
        'Saturday'  => null,
    ]
];

include __DIR__ . '/../../includes/header.php';
?>

<!-- Breadcrumb & Header -->
<div style="margin-bottom: 20px;">
    <div style="font-size: 12.5px; color: #64748B; margin-bottom: 4px;">
        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" style="color: #64748B;">Dashboard</a> &rsaquo; 
        <span style="color: #0F172A; font-weight: 600;">Timetable</span>
    </div>
    <h2 style="font-size: 22px; font-weight: 800; color: #0F172A; margin: 0;">Faculty Timetable Schedule</h2>
</div>

<!-- Filter Bar Matching Screenshot -->
<div class="card" style="margin-bottom: 20px; border-radius: 12px;">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" action="" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; flex: 1;">
                <!-- Department Select -->
                <select name="department_id" class="form-select" style="min-width: 190px; border-radius: 8px;">
                    <option value="0">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo (int)$dept['id']; ?>" <?php echo ($deptId === (int)$dept['id']) ? 'selected' : ''; ?>>
                            <?php echo escape($dept['dept_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Faculty Select -->
                <select name="faculty_id" class="form-select" style="min-width: 190px; border-radius: 8px;">
                    <option value="0">All Faculty</option>
                    <?php foreach ($facultyList as $fac): ?>
                        <option value="<?php echo (int)$fac['id']; ?>" <?php echo ($facultyId === (int)$fac['id']) ? 'selected' : ''; ?>>
                            <?php echo escape($fac['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Week Picker Display -->
                <div style="display: flex; align-items: center; gap: 6px; background: #F8FAFC; border: 1px solid #E2E8F0; padding: 7px 14px; border-radius: 8px; font-size: 13.5px; font-weight: 600; color: #334155;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <span>Current Week &bull; <?php echo date('M Y'); ?></span>
                </div>

                <button type="submit" class="btn btn-secondary" style="border-radius: 8px; padding: 8px 14px;">
                    Filter
                </button>
            </div>

            <!-- Add Timetable Purple Button -->
            <a href="<?php echo BASE_URL; ?>admin/timetable/add.php" class="btn btn-primary" style="background: #4F46E5; border: none; padding: 9px 18px; border-radius: 8px; font-weight: 700; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <span>Add Timetable</span>
            </a>
        </form>
    </div>
</div>

<!-- Weekly Schedule Matrix Table Card -->
<div class="card" style="border-radius: 12px; overflow: hidden; padding: 16px;">
    <div class="table-responsive">
        <table class="timetable-grid-table">
            <thead>
                <tr>
                    <th style="width: 140px; text-align: left; padding-left: 14px;">Time</th>
                    <?php foreach ($daysOfWeek as $day): ?>
                        <th style="width: 15%;"><?php echo $day; ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($timeSlots as $slotTime): ?>
                    <?php if ($slotTime === '12:00 PM - 01:00 PM'): ?>
                        <!-- Lunch Break Row -->
                        <tr>
                            <td style="font-weight: 700; font-size: 12px; color: #64748B; background: #F8FAFC; border: 1px solid #E2E8F0; padding: 10px 14px; border-radius: 6px; height: 42px;">
                                12:00 PM &ndash; 01:00 PM
                            </td>
                            <td colspan="6" style="text-align: center; background: repeating-linear-gradient(45deg, #F8FAFC, #F8FAFC 10px, #F1F5F9 10px, #F1F5F9 20px); color: #64748B; font-weight: 700; font-size: 12px; letter-spacing: 0.08em; text-transform: uppercase; border: 1px solid #E2E8F0; border-radius: 6px; height: 42px;">
                                Lunch &amp; Institutional Recess Break
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <!-- Time Slot Label -->
                            <td style="font-weight: 700; font-size: 12px; color: #334155; background: #FAFBFD; border: 1px solid #E2E8F0; padding: 12px 14px; border-radius: 8px; vertical-align: middle;">
                                <?php echo $slotTime; ?>
                            </td>

                            <!-- 6 Days Cells -->
                            <?php foreach ($daysOfWeek as $day): ?>
                                <?php $item = $scheduleMatrix[$slotTime][$day] ?? null; ?>
                                <td>
                                    <?php if ($item): ?>
                                        <div class="timetable-slot-card <?php echo $item['color']; ?>">
                                            <div>
                                                <div class="slot-title"><?php echo escape($item['subject']); ?></div>
                                                <div class="slot-teacher"><?php echo escape($item['faculty']); ?></div>
                                            </div>
                                            <div class="slot-room">Room: <?php echo escape($item['room']); ?></div>
                                        </div>
                                    <?php else: ?>
                                        <div style="height: 100%; display: flex; align-items: center; justify-content: center; color: #CBD5E1; font-size: 11px;">
                                            &ndash;
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
