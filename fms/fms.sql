-- ==========================================================
-- Faculty Management System (FMS) - Complete Database Dump
-- Compatible with MySQL 5.7+ / MySQL 8.0+ / MariaDB (XAMPP)
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `fms` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `fms`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `leave_requests`;
DROP TABLE IF EXISTS `attendance`;
DROP TABLE IF EXISTS `timetable`;
DROP TABLE IF EXISTS `faculty_subjects`;
DROP TABLE IF EXISTS `subjects`;
DROP TABLE IF EXISTS `faculty`;
DROP TABLE IF EXISTS `departments`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(60) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'faculty') NOT NULL DEFAULT 'faculty',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `departments`
-- --------------------------------------------------------
CREATE TABLE `departments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `dept_code` VARCHAR(20) NOT NULL UNIQUE,
  `dept_name` VARCHAR(120) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `faculty`
-- --------------------------------------------------------
CREATE TABLE `faculty` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL UNIQUE,
  `department_id` INT(11) NOT NULL,
  `emp_id` VARCHAR(30) NOT NULL UNIQUE,
  `full_name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `phone` VARCHAR(25) NOT NULL,
  `designation` VARCHAR(80) NOT NULL,
  `qualification` VARCHAR(150) NOT NULL,
  `joining_date` DATE NOT NULL,
  `photo` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_faculty_department` (`department_id`),
  CONSTRAINT `fk_faculty_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_faculty_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `subjects`
-- --------------------------------------------------------
CREATE TABLE `subjects` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `subject_code` VARCHAR(20) NOT NULL UNIQUE,
  `subject_name` VARCHAR(150) NOT NULL,
  `department_id` INT(11) NOT NULL,
  `semester` INT(11) NOT NULL DEFAULT 1,
  `credits` INT(11) NOT NULL DEFAULT 3,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_subjects_department` (`department_id`),
  CONSTRAINT `fk_subjects_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `faculty_subjects`
-- --------------------------------------------------------
CREATE TABLE `faculty_subjects` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `faculty_id` INT(11) NOT NULL,
  `subject_id` INT(11) NOT NULL,
  `academic_year` VARCHAR(20) NOT NULL DEFAULT '2024-2025',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_fac_sub_unique` (`faculty_id`, `subject_id`),
  KEY `fk_fs_subject` (`subject_id`),
  CONSTRAINT `fk_fs_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fs_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `timetable`
-- --------------------------------------------------------
CREATE TABLE `timetable` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `faculty_id` INT(11) NOT NULL,
  `subject_id` INT(11) NOT NULL,
  `department_id` INT(11) NOT NULL,
  `day_of_week` ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `room_number` VARCHAR(30) NOT NULL,
  `semester` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_tt_faculty` (`faculty_id`),
  KEY `fk_tt_subject` (`subject_id`),
  KEY `fk_tt_department` (`department_id`),
  CONSTRAINT `fk_tt_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tt_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tt_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `attendance`
-- --------------------------------------------------------
CREATE TABLE `attendance` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `faculty_id` INT(11) NOT NULL,
  `attendance_date` DATE NOT NULL,
  `status` ENUM('Present', 'Absent', 'Late', 'On-Leave') NOT NULL DEFAULT 'Present',
  `in_time` TIME DEFAULT NULL,
  `out_time` TIME DEFAULT NULL,
  `remarks` VARCHAR(255) DEFAULT NULL,
  `recorded_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_fac_date_unique` (`faculty_id`, `attendance_date`),
  KEY `fk_att_faculty` (`faculty_id`),
  CONSTRAINT `fk_att_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `leave_requests`
-- --------------------------------------------------------
CREATE TABLE `leave_requests` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `faculty_id` INT(11) NOT NULL,
  `leave_type` VARCHAR(50) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `days_count` INT(11) NOT NULL DEFAULT 1,
  `reason` TEXT NOT NULL,
  `status` ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
  `admin_remarks` TEXT DEFAULT NULL,
  `reviewed_by` INT(11) DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_lr_faculty` (`faculty_id`),
  CONSTRAINT `fk_lr_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- Seed Initial Demo Data
-- ========================================================

-- 1. Default Administrator Account (username: admin / password: admin123)
-- 2. Faculty User Accounts (passwords: faculty123)
INSERT INTO `users` (`id`, `username`, `password`, `role`, `status`) VALUES
(1, 'admin', '$2y$10$rO6s6lfcxGFpJ8pxWAnum.8gsuJ4BHCvLyiMaK7Yj3iqXXBPXbS02', 'admin', 'active'),
(2, 'dr.ramesh', '$2y$10$Tr1z8B9.5kDyMmTpWN9CxusN1erULkBhUc3mS4ZFxM6wckp.f2482', 'faculty', 'active'),
(3, 'prof.priya', '$2y$10$Tr1z8B9.5kDyMmTpWN9CxusN1erULkBhUc3mS4ZFxM6wckp.f2482', 'faculty', 'active'),
(4, 'dr.anil', '$2y$10$Tr1z8B9.5kDyMmTpWN9CxusN1erULkBhUc3mS4ZFxM6wckp.f2482', 'faculty', 'active'),
(5, 'prof.sneha', '$2y$10$Tr1z8B9.5kDyMmTpWN9CxusN1erULkBhUc3mS4ZFxM6wckp.f2482', 'faculty', 'active');

-- Departments
INSERT INTO `departments` (`id`, `dept_code`, `dept_name`, `description`) VALUES
(1, 'BCA', 'Department of Computer Applications', 'Undergraduate programs in Computer Applications, Web Development, and Database Systems.'),
(2, 'MCA', 'Department of Master of Computer Applications', 'Postgraduate software engineering, AI, and Cloud Computing research.'),
(3, 'BSCCS', 'Department of Computer Science', 'Foundational computing, algorithm engineering, and data science.'),
(4, 'IT', 'Department of Information Technology', 'Network infrastructure, cyber security, and enterprise system administration.');

-- Faculty Profiles
INSERT INTO `faculty` (`id`, `user_id`, `department_id`, `emp_id`, `full_name`, `email`, `phone`, `designation`, `qualification`, `joining_date`, `photo`) VALUES
(1, 2, 1, 'FAC-BCA-001', 'Dr. Ramesh Kumar', 'ramesh.kumar@college.edu', '+91 98765 43210', 'Professor & HOD', 'Ph.D in Computer Science, M.Tech, MCA', '2018-06-15', NULL),
(2, 3, 1, 'FAC-BCA-002', 'Prof. Demo 2', 'demo2@college.edu', '+91 98765 43211', 'Assistant Professor', 'M.Tech in Software Engineering, B.E', '2020-08-01', NULL),
(3, 4, 2, 'FAC-MCA-001', 'Dr. Anil Deshmukh', 'anil.deshmukh@college.edu', '+91 98765 43212', 'Associate Professor', 'Ph.D in Information Security, M.Tech', '2019-01-10', NULL),
(4, 5, 3, 'FAC-CS-001', 'Prof. Sneha Verma', 'sneha.verma@college.edu', '+91 98765 43213', 'Assistant Professor', 'M.Sc in Computer Science, NET-JRF Qualified', '2021-07-20', NULL);

-- Curriculum Subjects
INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `department_id`, `semester`, `credits`) VALUES
(1, 'BCA-101', 'Programming in C & Data Types', 1, 1, 4),
(2, 'BCA-301', 'Data Structures & Algorithm Design', 1, 3, 4),
(3, 'BCA-302', 'Relational Database Management Systems (RDBMS)', 1, 3, 4),
(4, 'BCA-501', 'Web Technologies & PHP Fullstack', 1, 5, 4),
(5, 'MCA-101', 'Advanced Object-Oriented Analysis & Design', 2, 1, 4),
(6, 'MCA-102', 'Enterprise Cloud Architecture', 2, 1, 4),
(7, 'CS-201', 'Discrete Mathematical Structures', 3, 2, 3),
(8, 'IT-301', 'Computer Networks & Network Security', 4, 3, 4);

-- Faculty-Subject Allocations
INSERT INTO `faculty_subjects` (`faculty_id`, `subject_id`, `academic_year`) VALUES
(1, 2, '2024-2025'),
(1, 5, '2024-2025'),
(2, 3, '2024-2025'),
(2, 4, '2024-2025'),
(3, 6, '2024-2025'),
(4, 7, '2024-2025');

-- Timetable Schedule
INSERT INTO `timetable` (`faculty_id`, `subject_id`, `department_id`, `day_of_week`, `start_time`, `end_time`, `room_number`, `semester`) VALUES
(1, 2, 1, 'Monday', '09:00:00', '10:00:00', 'Room 204', 3),
(1, 2, 1, 'Wednesday', '09:00:00', '10:00:00', 'Room 204', 3),
(1, 2, 1, 'Friday', '11:00:00', '12:00:00', 'Lab-A', 3),
(2, 3, 1, 'Tuesday', '10:00:00', '11:00:00', 'Room 204', 3),
(2, 3, 1, 'Thursday', '10:00:00', '11:00:00', 'Room 204', 3),
(2, 4, 1, 'Monday', '11:00:00', '12:00:00', 'Lab-B', 5),
(2, 4, 1, 'Wednesday', '11:00:00', '12:00:00', 'Lab-B', 5),
(3, 6, 2, 'Tuesday', '14:00:00', '15:00:00', 'PG-Room 1', 1),
(3, 6, 2, 'Thursday', '14:00:00', '15:00:00', 'PG-Room 1', 1),
(4, 7, 3, 'Monday', '10:00:00', '11:00:00', 'CS-Hall 3', 2);

-- Sample Attendance
INSERT INTO `attendance` (`faculty_id`, `attendance_date`, `status`, `in_time`, `out_time`, `remarks`, `recorded_by`) VALUES
(1, CURRENT_DATE(), 'Present', '08:50:00', '17:10:00', 'Regular academic duty', 1),
(2, CURRENT_DATE(), 'Present', '09:05:00', '17:00:00', 'Regular academic duty', 1),
(3, CURRENT_DATE(), 'Late', '09:30:00', '17:15:00', 'Transit delay', 1),
(4, CURRENT_DATE(), 'Present', '08:55:00', '17:05:00', 'Regular academic duty', 1);

-- Sample Leave Requests
INSERT INTO `leave_requests` (`faculty_id`, `leave_type`, `start_date`, `end_date`, `days_count`, `reason`, `status`, `admin_remarks`, `reviewed_by`, `reviewed_at`) VALUES
(2, 'Casual Leave', DATE_ADD(CURRENT_DATE(), INTERVAL 5 DAY), DATE_ADD(CURRENT_DATE(), INTERVAL 6 DAY), 2, 'Family function in hometown. Prof. Ramesh will cover Lab sessions.', 'Pending', NULL, NULL, NULL),
(3, 'Sick Leave', DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY), DATE_SUB(CURRENT_DATE(), INTERVAL 6 DAY), 2, 'Viral fever recovery. Medical certificate attached.', 'Approved', 'Approved with medical certificate on record.', 1, CURRENT_TIMESTAMP());
