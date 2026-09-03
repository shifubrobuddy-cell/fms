<?php
/**
 * Faculty Management System (FMS)
 * Clean Database Seeder
 * Populates data with Admin, Demo 1, Demo 2, and clean demo faculty
 * Default password for all demo accounts: password123
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
$db = getDB();

echo "Starting FMS database seeding...\n";

// Disable foreign keys temporarily
$db->exec("PRAGMA foreign_keys = OFF;");

// Clear existing tables
$tables = ['timetable', 'attendance', 'leave_requests', 'faculty_subjects', 'subjects', 'faculty', 'users', 'departments'];
foreach ($tables as $t) {
    $db->exec("DELETE FROM $t;");
    $db->exec("DELETE FROM sqlite_sequence WHERE name='$t';");
}

// 1. Departments (6 departments)
$depts = [
    ['CS', 'Computer Science', 'Department of Computer Science and Applications'],
    ['IT', 'Information Technology', 'Department of Information Technology'],
    ['MGMT', 'Management', 'School of Business & Management Studies'],
    ['COMM', 'Commerce', 'Department of Commerce and Financial Studies'],
    ['MATH', 'Mathematics', 'Department of Pure and Applied Mathematics'],
    ['ECE', 'Electronics', 'Department of Electronics & Communication Engineering'],
];

$deptStmt = $db->prepare("INSERT INTO departments (dept_code, dept_name, description) VALUES (?, ?, ?)");
$deptIds = [];
foreach ($depts as $d) {
    $deptStmt->execute($d);
    $deptIds[$d[0]] = (int)$db->lastInsertId();
}
echo "Inserted 6 departments.\n";

// 2. Admin User
$defaultPass = password_hash('password123', PASSWORD_BCRYPT);
$db->prepare("INSERT INTO users (username, password, role, status) VALUES ('admin', ?, 'admin', 'active')")->execute([$defaultPass]);
$adminUserId = (int)$db->lastInsertId();
echo "Created Admin user (username: admin, password: password123).\n";

// 3. Faculty Members (featuring Affan Sir, Shagufta, Ummul, Sofia, Mahwish Momin)
$primaryFaculty = [
    [
        'emp_id' => 'FMS001',
        'username' => 'affan',
        'full_name' => 'Affan Sir',
        'dept' => 'CS',
        'designation' => 'Head of Department & Professor',
        'email' => 'affan.sir@fms.edu',
        'qualification' => 'Ph.D. in Computer Science & Engineering',
        'status' => 'active'
    ],
    [
        'emp_id' => 'FMS002',
        'username' => 'shagufta',
        'full_name' => 'Shagufta',
        'dept' => 'IT',
        'designation' => 'Associate Professor',
        'email' => 'shagufta@fms.edu',
        'qualification' => 'M.Tech in Information Technology',
        'status' => 'active'
    ],
    [
        'emp_id' => 'FMS003',
        'username' => 'ummul',
        'full_name' => 'Ummul',
        'dept' => 'CS',
        'designation' => 'Assistant Professor',
        'email' => 'ummul@fms.edu',
        'qualification' => 'M.Sc. in Computer Science & AI',
        'status' => 'active'
    ],
    [
        'emp_id' => 'FMS004',
        'username' => 'sofia',
        'full_name' => 'Sofia',
        'dept' => 'ECE',
        'designation' => 'Assistant Professor',
        'email' => 'sofia@fms.edu',
        'qualification' => 'M.Tech in Electronics & Embedded Systems',
        'status' => 'active'
    ],
    [
        'emp_id' => 'FMS005',
        'username' => 'mahwish',
        'full_name' => 'Mahwish Momin',
        'dept' => 'CS',
        'designation' => 'Assistant Professor & Project Mentor',
        'email' => 'mahwish.momin@fms.edu',
        'qualification' => 'M.Tech, Ph.D. Scholar (Guide)',
        'status' => 'active'
    ],
];

$deptKeys = ['CS', 'IT', 'MGMT', 'COMM', 'MATH', 'ECE'];
$designations = ['Professor', 'Associate Professor', 'Assistant Professor', 'Senior Lecturer'];

$facultyList = [];
for ($i = 1; $i <= 32; $i++) {
    $empId = 'FMS' . str_pad($i, 3, '0', STR_PAD_LEFT);
    if ($i <= count($primaryFaculty)) {
        $p = $primaryFaculty[$i - 1];
        $username = $p['username'];
        $fullName = $p['full_name'];
        $deptKey = $p['dept'];
        $desig = $p['designation'];
        $email = $p['email'];
        $qual = $p['qualification'];
        $status = $p['status'];
    } else {
        $username = 'demo' . $i;
        $fullName = 'Faculty Member ' . $i;
        $deptKey = $deptKeys[($i - 1) % count($deptKeys)];
        $desig = $designations[($i - 1) % count($designations)];
        $email = 'faculty' . $i . '@fms.edu';
        $qual = ($i % 2 === 1) ? 'Ph.D. in Computer Science' : 'M.Tech / Post Graduate';
        $status = in_array($i, [12, 20, 28]) ? 'inactive' : 'active';
    }

    // Modern AI Bot Avatar (strictly vector AI Bot, no human photos)
    $avatar = getAIBotAvatarUrl($fullName);

    $facultyList[] = [
        'emp_id' => $empId,
        'username' => $username,
        'full_name' => $fullName,
        'dept' => $deptKey,
        'designation' => $desig,
        'email' => $email,
        'phone' => '98765' . str_pad($i, 5, '0', STR_PAD_LEFT),
        'qualification' => $qual,
        'status' => $status,
        'photo' => $avatar
    ];
}

$userStmt = $db->prepare("INSERT INTO users (username, password, role, status) VALUES (?, ?, 'faculty', ?)");
$facStmt = $db->prepare("
    INSERT INTO faculty (user_id, department_id, emp_id, full_name, email, phone, designation, qualification, joining_date, photo)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$facultyIds = [];
$facIdx = 0;
foreach ($facultyList as $f) {
    $facIdx++;
    $userStmt->execute([$f['username'], $defaultPass, $f['status']]);
    $userId = (int)$db->lastInsertId();

    $deptId = $deptIds[$f['dept']];
    $joinYear = 2020 + ($facIdx % 5);
    $joiningDate = "$joinYear-07-15";

    $facStmt->execute([
        $userId,
        $deptId,
        $f['emp_id'],
        $f['full_name'],
        $f['email'],
        $f['phone'],
        $f['designation'],
        $f['qualification'],
        $joiningDate,
        $f['photo']
    ]);

    $facultyIds[$f['emp_id']] = (int)$db->lastInsertId();
}
echo "Inserted 32 faculty members (28 Active, 4 Inactive).\n";

// 4. Subjects (24 Subjects)
$subjectsData = [
    // Computer Science
    ['CS201', 'Data Structures & Algorithms', 'CS', 3, 4],
    ['CS202', 'Database Management Systems', 'CS', 4, 4],
    ['CS203', 'Web Application Development', 'CS', 4, 3],
    ['CS204', 'Object Oriented Programming', 'CS', 3, 4],
    ['CS205', 'Software Engineering Principles', 'CS', 5, 3],
    ['CS206', 'Operating Systems & Architecture', 'CS', 4, 4],
    ['CS301', 'Computer Networks & Protocols', 'CS', 5, 4],
    ['CS302', 'Artificial Intelligence Core', 'CS', 6, 4],

    // Information Technology
    ['IT201', 'Cloud Computing & Virtualization', 'IT', 5, 4],
    ['IT202', 'Cyber Security Essentials', 'IT', 6, 4],
    ['IT203', 'Big Data Analytics', 'IT', 7, 3],
    ['IT204', 'Internet of Things (IoT)', 'IT', 6, 3],

    // Management
    ['MGT101', 'Organizational Behavior', 'MGMT', 1, 3],
    ['MGT102', 'Financial Management', 'MGMT', 2, 4],
    ['MGT201', 'Marketing Research & Analytics', 'MGMT', 3, 3],
    ['MGT202', 'Strategic Operations Management', 'MGMT', 4, 3],

    // Commerce
    ['COM101', 'Corporate Accounting', 'COMM', 2, 4],
    ['COM102', 'Business Taxation Laws', 'COMM', 4, 3],
    ['COM201', 'Investment & Portfolio Analysis', 'COMM', 5, 4],
    ['COM202', 'International Business Trade', 'COMM', 6, 3],

    // Mathematics
    ['MTH101', 'Linear Algebra & Calculus', 'MATH', 1, 4],
    ['MTH102', 'Discrete Mathematics & Graph Theory', 'MATH', 2, 4],

    // ECE
    ['ECE201', 'Digital Signal Processing', 'ECE', 4, 4],
    ['ECE202', 'Microprocessors & Microcontrollers', 'ECE', 5, 4],
];

$subStmt = $db->prepare("INSERT INTO subjects (subject_code, subject_name, department_id, semester, credits) VALUES (?, ?, ?, ?, ?)");
$subjectIds = [];
foreach ($subjectsData as $s) {
    $deptId = $deptIds[$s[2]];
    $subStmt->execute([$s[0], $s[1], $deptId, $s[3], $s[4]]);
    $subjectIds[$s[0]] = (int)$db->lastInsertId();
}
echo "Inserted 24 subjects.\n";

// 5. Faculty Course Allocations
$allocStmt = $db->prepare("INSERT INTO faculty_subjects (faculty_id, subject_id, academic_year) VALUES (?, ?, '2025-2026')");
$allocStmt->execute([$facultyIds['FMS001'], $subjectIds['CS201']]); // Demo 1 -> Data Structures
$allocStmt->execute([$facultyIds['FMS002'], $subjectIds['CS202']]); // Demo 2 -> Database Management
$allocStmt->execute([$facultyIds['FMS005'], $subjectIds['CS203']]); // Demo 5 -> Web Development
$allocStmt->execute([$facultyIds['FMS006'], $subjectIds['CS204']]); // Demo 6 -> OOPs
$allocStmt->execute([$facultyIds['FMS003'], $subjectIds['CS205']]); // Demo 3 -> Software Engg
$allocStmt->execute([$facultyIds['FMS006'], $subjectIds['CS206']]); // Demo 6 -> OS
echo "Allocated core courses.\n";

// 6. Today's Attendance (Total 32: 26 Present, 6 Absent)
$attStmt = $db->prepare("
    INSERT INTO attendance (faculty_id, attendance_date, status, in_time, out_time, remarks, recorded_by)
    VALUES (?, ?, ?, ?, ?, ?, 1)
");

$today = date('Y-m-d');
$facAll = $db->query("SELECT id FROM faculty ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);

$i = 0;
foreach ($facAll as $fid) {
    $i++;
    // First 26 Present, remaining 6 Absent
    if ($i <= 26) {
        $status = 'Present';
        $inTime = '08:45:00';
        $outTime = '17:00:00';
        $remark = 'Regular punching recorded';
    } else {
        $status = 'Absent';
        $inTime = null;
        $outTime = null;
        $remark = 'Unplanned absence recorded';
    }
    $attStmt->execute([$fid, $today, $status, $inTime, $outTime, $remark]);
}
echo "Seeded today's attendance: 26 Present, 6 Absent (Total 32).\n";

// 7. Leave Requests (4 Pending, 15 Approved)
$leaveStmt = $db->prepare("
    INSERT INTO leave_requests (faculty_id, leave_type, start_date, end_date, days_count, reason, status, admin_remarks, reviewed_by, reviewed_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

// 4 Pending Leaves
$pendingList = [
    [$facultyIds['FMS002'], 'Casual Leave', date('Y-m-d', strtotime('+1 day')), date('Y-m-d', strtotime('+2 days')), 2, 'Attending academic symposium', 'Pending', null, null, null],
    [$facultyIds['FMS003'], 'Sick Leave', date('Y-m-d'), date('Y-m-d', strtotime('+2 days')), 3, 'Viral fever, medical certificate attached', 'Pending', null, null, null],
    [$facultyIds['FMS004'], 'Earned Leave', date('Y-m-d', strtotime('+3 days')), date('Y-m-d', strtotime('+7 days')), 5, 'Family commitment and personal travel', 'Pending', null, null, null],
    [$facultyIds['FMS006'], 'Casual Leave', date('Y-m-d', strtotime('+4 days')), date('Y-m-d', strtotime('+4 days')), 1, 'Doctor appointment and health checkup', 'Pending', null, null, null]
];

foreach ($pendingList as $pl) {
    $leaveStmt->execute($pl);
}

// 15 Approved Leaves:
for ($k = 1; $k <= 15; $k++) {
    $facIdx = ($k % 25) + 1;
    $fid = $facAll[$facIdx];
    $pastDays = $k * 3;
    $startDate = date('Y-m-d', strtotime("-$pastDays days"));
    $endDate = date('Y-m-d', strtotime("-".($pastDays - 2)." days"));
    $leaveStmt->execute([
        $fid,
        ($k % 2 === 0) ? 'Casual Leave' : 'Medical Leave',
        $startDate,
        $endDate,
        2,
        'Approved institutional leave application',
        'Approved',
        'Sanctioned by Dean Academic Affairs',
        1,
        date('Y-m-d H:i:s', strtotime("-$pastDays days"))
    ]);
}
echo "Seeded 4 Pending and 15 Approved leaves.\n";

// 8. Timetable Schedule (Matching Today's Schedule & Weekly grid)
$ttStmt = $db->prepare("
    INSERT INTO timetable (faculty_id, subject_id, department_id, day_of_week, start_time, end_time, room_number, semester)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

foreach ($days as $day) {
    // 09:00 AM - 10:00 AM: Data Structures (Demo 1) | CS-201
    $ttStmt->execute([$facultyIds['FMS001'], $subjectIds['CS201'], $deptIds['CS'], $day, '09:00:00', '10:00:00', 'CS-201', 3]);
    
    // 10:00 AM - 11:00 AM: OOPs (Demo 6) | CS-202
    $ttStmt->execute([$facultyIds['FMS006'], $subjectIds['CS204'], $deptIds['CS'], $day, '10:00:00', '11:00:00', 'CS-202', 3]);
    
    // 11:00 AM - 12:00 PM: Database Management (Demo 2) | CS-203
    $ttStmt->execute([$facultyIds['FMS002'], $subjectIds['CS202'], $deptIds['CS'], $day, '11:00:00', '12:00:00', 'CS-203', 4]);
    
    // 12:00 PM - 01:00 PM: Software Engg (Demo 3) | CS-302
    $ttStmt->execute([$facultyIds['FMS003'], $subjectIds['CS205'], $deptIds['CS'], $day, '12:00:00', '13:00:00', 'CS-302', 5]);
    
    // 02:00 PM - 03:00 PM: Web Development (Demo 5) | CS-202
    $ttStmt->execute([$facultyIds['FMS005'], $subjectIds['CS203'], $deptIds['CS'], $day, '14:00:00', '15:00:00', 'CS-202', 4]);

    // 03:00 PM - 04:00 PM: Operating Systems (Demo 6) | CS-301
    $ttStmt->execute([$facultyIds['FMS006'], $subjectIds['CS206'], $deptIds['CS'], $day, '15:00:00', '16:00:00', 'CS-301', 4]);
}

// Re-enable foreign keys
$db->exec("PRAGMA foreign_keys = ON;");

echo "Seeding completed successfully!\n";
