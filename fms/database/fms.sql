-- ==========================================================
-- Faculty Management System (FMS) - Database Schema
-- Database: faculty_management_system
-- Target: MySQL 5.7+ / MariaDB 10.3+ (InnoDB, utf8mb4)
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `faculty_management_system` 
  DEFAULT CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE `faculty_management_system`;

-- Disable foreign key checks during import
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------
-- 1. TABLE: departments
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `dept_code` VARCHAR(10) NOT NULL UNIQUE,
  `dept_name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 2. TABLE: users
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'faculty') NOT NULL DEFAULT 'faculty',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 3. TABLE: faculty
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `faculty`;
CREATE TABLE `faculty` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `department_id` INT NOT NULL,
  `emp_id` VARCHAR(20) NOT NULL UNIQUE,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL,
  `designation` VARCHAR(100) NOT NULL,
  `qualification` VARCHAR(100) NOT NULL,
  `joining_date` DATE NOT NULL,
  `photo` VARCHAR(255) DEFAULT 'default_avatar.png',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_faculty_dept` (`department_id`),
  INDEX `idx_faculty_emp_id` (`emp_id`),
  CONSTRAINT `fk_faculty_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_faculty_department` FOREIGN KEY (`department_id`) 
    REFERENCES `departments` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 4. TABLE: subjects
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `subjects`;
CREATE TABLE `subjects` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `subject_code` VARCHAR(20) NOT NULL UNIQUE,
  `subject_name` VARCHAR(100) NOT NULL,
  `department_id` INT NOT NULL,
  `semester` INT NOT NULL,
  `credits` INT NOT NULL DEFAULT 4,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_subjects_dept` (`department_id`),
  INDEX `idx_subjects_semester` (`semester`),
  CONSTRAINT `fk_subjects_department` FOREIGN KEY (`department_id`) 
    REFERENCES `departments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 5. TABLE: faculty_subjects (Many-to-Many)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `faculty_subjects`;
CREATE TABLE `faculty_subjects` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `faculty_id` INT NOT NULL,
  `subject_id` INT NOT NULL,
  `academic_year` VARCHAR(20) NOT NULL DEFAULT '2025-2026',
  UNIQUE KEY `unique_faculty_subject_year` (`faculty_id`, `subject_id`, `academic_year`),
  INDEX `idx_fs_faculty` (`faculty_id`),
  INDEX `idx_fs_subject` (`subject_id`),
  CONSTRAINT `fk_fs_faculty` FOREIGN KEY (`faculty_id`) 
    REFERENCES `faculty` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fs_subject` FOREIGN KEY (`subject_id`) 
    REFERENCES `subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 6. TABLE: attendance
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `attendance`;
CREATE TABLE `attendance` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `faculty_id` INT NOT NULL,
  `attendance_date` DATE NOT NULL,
  `status` ENUM('Present', 'Absent', 'On Leave', 'Late') NOT NULL DEFAULT 'Present',
  `in_time` TIME DEFAULT NULL,
  `out_time` TIME DEFAULT NULL,
  `remarks` VARCHAR(255) DEFAULT NULL,
  `recorded_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_faculty_attendance_date` (`faculty_id`, `attendance_date`),
  INDEX `idx_attendance_date` (`attendance_date`),
  INDEX `idx_attendance_status` (`status`),
  CONSTRAINT `fk_attendance_faculty` FOREIGN KEY (`faculty_id`) 
    REFERENCES `faculty` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_attendance_recorder` FOREIGN KEY (`recorded_by`) 
    REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 7. TABLE: leave_requests
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `leave_requests`;
CREATE TABLE `leave_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `faculty_id` INT NOT NULL,
  `leave_type` ENUM('Casual Leave', 'Sick Leave', 'Duty Leave', 'Earned Leave', 'Maternity/Paternity Leave') NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `days_count` INT NOT NULL DEFAULT 1,
  `reason` TEXT NOT NULL,
  `status` ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
  `admin_remarks` TEXT DEFAULT NULL,
  `reviewed_by` INT DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_leave_faculty` (`faculty_id`),
  INDEX `idx_leave_status` (`status`),
  CONSTRAINT `fk_leave_faculty` FOREIGN KEY (`faculty_id`) 
    REFERENCES `faculty` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_leave_reviewer` FOREIGN KEY (`reviewed_by`) 
    REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 8. TABLE: timetable
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `timetable`;
CREATE TABLE `timetable` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `faculty_id` INT NOT NULL,
  `subject_id` INT NOT NULL,
  `department_id` INT NOT NULL,
  `day_of_week` ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `room_number` VARCHAR(30) NOT NULL,
  `semester` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_room_day_slot` (`room_number`, `day_of_week`, `start_time`),
  UNIQUE KEY `unique_faculty_day_slot` (`faculty_id`, `day_of_week`, `start_time`),
  INDEX `idx_timetable_day` (`day_of_week`),
  CONSTRAINT `fk_tt_faculty` FOREIGN KEY (`faculty_id`) 
    REFERENCES `faculty` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tt_subject` FOREIGN KEY (`subject_id`) 
    REFERENCES `subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tt_department` FOREIGN KEY (`department_id`) 
    REFERENCES `departments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================================
-- SEED DATA
-- Default Passwords:
-- Admin: username: 'admin' | password: 'admin123'
-- Faculty: username: '<username>' | password: 'password123'
-- Hashes generated using PHP password_hash(..., PASSWORD_BCRYPT)
-- ==========================================================

-- 1. Departments (3 departments)
INSERT INTO `departments` (`id`, `dept_code`, `dept_name`, `description`) VALUES
(1, 'BCA', 'Computer Applications', 'Bachelor of Computer Applications & IT Studies'),
(2, 'CS', 'Computer Science & Engineering', 'Department of Computer Science & Systems Engineering'),
(3, 'IT', 'Information Technology', 'Department of Software Engineering & Information Systems');

-- 2. Users (1 Admin + 5 Faculty)
-- 'admin123' hash: $2y$10$N9qo8uLOickgx2ZMRZoMyeMiMwiT/hYv/GXAzJLiUmBNwmYD4kh02
-- 'password123' hash: $2y$10$N9qo8uLOickgx2ZMRZoMyeTfmMdmN8eFKxR3xiDBAJqt.vWQw1.JW
INSERT INTO `users` (`id`, `username`, `password`, `role`, `status`) VALUES
(1, 'admin', '$2y$10$N9qo8uLOickgx2ZMRZoMyeMiMwiT/hYv/GXAzJLiUmBNwmYD4kh02', 'admin', 'active'),
(2, 'dr.sharma', '$2y$10$N9qo8uLOickgx2ZMRZoMyeTfmMdmN8eFKxR3xiDBAJqt.vWQw1.JW', 'faculty', 'active'),
(3, 'prof.patel', '$2y$10$N9qo8uLOickgx2ZMRZoMyeTfmMdmN8eFKxR3xiDBAJqt.vWQw1.JW', 'faculty', 'active'),
(4, 'dr.verma', '$2y$10$N9qo8uLOickgx2ZMRZoMyeTfmMdmN8eFKxR3xiDBAJqt.vWQw1.JW', 'faculty', 'active'),
(5, 'prof.nair', '$2y$10$N9qo8uLOickgx2ZMRZoMyeTfmMdmN8eFKxR3xiDBAJqt.vWQw1.JW', 'faculty', 'active'),
(6, 'prof.rao', '$2y$10$N9qo8uLOickgx2ZMRZoMyeTfmMdmN8eFKxR3xiDBAJqt.vWQw1.JW', 'faculty', 'active');

-- 3. Faculty Profiles (5 faculty linked to users 2..6)
INSERT INTO `faculty` (`id`, `user_id`, `department_id`, `emp_id`, `full_name`, `email`, `phone`, `designation`, `qualification`, `joining_date`, `photo`) VALUES
(1, 2, 1, 'FAC-BCA-001', 'Dr. Demo 1', 'demo1@college.edu', '+91 98765 43210', 'Professor & HOD', 'Ph.D. in Computer Science', '2018-06-15', 'default_avatar.png'),
(2, 3, 1, 'FAC-BCA-002', 'Prof. Anjali Patel', 'a.patel@college.edu', '+91 98765 43211', 'Associate Professor', 'M.C.A., M.Tech.', '2020-01-10', 'default_avatar.png'),
(3, 4, 2, 'FAC-CS-003', 'Dr. Vikram Verma', 'v.verma@college.edu', '+91 98765 43212', 'Associate Professor', 'Ph.D. in Software Engineering', '2019-07-22', 'default_avatar.png'),
(4, 5, 2, 'FAC-CS-004', 'Prof. Meera Nair', 'm.nair@college.edu', '+91 98765 43213', 'Assistant Professor', 'M.Tech. (CS)', '2021-08-01', 'default_avatar.png'),
(5, 6, 3, 'FAC-IT-005', 'Prof. Suresh Rao', 's.rao@college.edu', '+91 98765 43214', 'Assistant Professor', 'M.C.A.', '2022-03-15', 'default_avatar.png');

-- 4. Subjects (5 subjects across departments)
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `department_id`, `semester`, `credits`) VALUES
(1, 'BCA301', 'Relational Database Management Systems', 1, 3, 4),
(2, 'BCA302', 'Web Technologies & PHP Programming', 1, 3, 4),
(3, 'CS401', 'Data Structures and Algorithms', 2, 4, 4),
(4, 'CS402', 'Operating Systems & System Architecture', 2, 4, 3),
(5, 'IT501', 'Cloud Computing and Network Security', 3, 5, 4);

-- 5. Faculty Subjects (Many-to-Many assignments)
INSERT INTO `faculty_subjects` (`id`, `faculty_id`, `subject_id`, `academic_year`) VALUES
(1, 1, 1, '2025-2026'), -- Dr. Ramesh Sharma teaches RDBMS
(2, 2, 2, '2025-2026'), -- Prof. Anjali Patel teaches Web Tech & PHP
(3, 3, 3, '2025-2026'), -- Dr. Vikram Verma teaches Data Structures
(4, 4, 4, '2025-2026'), -- Prof. Meera Nair teaches Operating Systems
(5, 5, 5, '2025-2026'), -- Prof. Suresh Rao teaches Cloud Computing
(6, 1, 2, '2025-2026'); -- Dr. Ramesh Sharma co-teaches Web Technologies

-- 6. Sample Attendance (recent records for today & yesterday)
INSERT INTO `attendance` (`id`, `faculty_id`, `attendance_date`, `status`, `in_time`, `out_time`, `remarks`, `recorded_by`) VALUES
(1, 1, CURDATE(), 'Present', '08:45:00', '16:30:00', 'On time - biometric punch', 1),
(2, 2, CURDATE(), 'Present', '08:52:00', '16:15:00', 'On time - morning session', 1),
(3, 3, CURDATE(), 'Present', '09:10:00', '16:45:00', 'Late entry approved by HOD', 1),
(4, 4, CURDATE(), 'On Leave', NULL, NULL, 'Medical leave approved', 1),
(5, 5, CURDATE(), 'Present', '08:55:00', '16:00:00', 'Regular attendance', 1),
-- Yesterday's records
(6, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Present', '08:50:00', '16:35:00', 'Regular', 1),
(7, 2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Present', '08:48:00', '16:20:00', 'Regular', 1),
(8, 3, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Present', '08:55:00', '16:30:00', 'Regular', 1),
(9, 4, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'On Leave', NULL, NULL, 'Medical leave', 1),
(10, 5, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Present', '09:00:00', '16:10:00', 'Regular', 1);

-- 7. Sample Leave Requests
INSERT INTO `leave_requests` (`id`, `faculty_id`, `leave_type`, `start_date`, `end_date`, `days_count`, `reason`, `status`, `admin_remarks`, `reviewed_by`, `reviewed_at`) VALUES
(1, 4, 'Sick Leave', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 DAY), 2, 'Viral fever and doctor-prescribed rest.', 'Approved', 'Get well soon. Medical certificate attached.', 1, NOW()),
(2, 2, 'Casual Leave', DATE_ADD(CURDATE(), INTERVAL 4 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 2, 'Attending family wedding ceremony.', 'Pending', NULL, NULL, NULL),
(3, 5, 'Duty Leave', DATE_ADD(CURDATE(), INTERVAL 7 DAY), DATE_ADD(CURDATE(), INTERVAL 8 DAY), 2, 'Attending IEEE International Conference as paper presenter.', 'Pending', NULL, NULL, NULL),
(4, 3, 'Casual Leave', DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL 9 DAY), 2, 'Personal errands and vehicle registration.', 'Approved', 'Sanctioned with substitute arrangement.', 1, DATE_SUB(NOW(), INTERVAL 11 DAY));

-- 8. Sample Timetable Slots
INSERT INTO `timetable` (`id`, `faculty_id`, `subject_id`, `department_id`, `day_of_week`, `start_time`, `end_time`, `room_number`, `semester`) VALUES
(1, 1, 1, 1, 'Monday', '09:00:00', '10:00:00', 'Lab-301', 3),
(2, 2, 2, 1, 'Monday', '10:15:00', '11:15:00', 'Room-204', 3),
(3, 3, 3, 2, 'Monday', '11:30:00', '12:30:00', 'Room-105', 4),
(4, 4, 4, 2, 'Tuesday', '09:00:00', '10:00:00', 'Room-102', 4),
(5, 5, 5, 3, 'Tuesday', '10:15:00', '11:15:00', 'Lab-202', 5),
(6, 1, 1, 1, 'Wednesday', '09:00:00', '10:00:00', 'Lab-301', 3),
(7, 2, 2, 1, 'Thursday', '11:30:00', '12:30:00', 'Room-204', 3),
(8, 3, 3, 2, 'Friday', '14:00:00', '15:00:00', 'Room-105', 4);
