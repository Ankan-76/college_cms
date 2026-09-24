-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: college_cms
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_broadcasts`
--

DROP TABLE IF EXISTS `admin_broadcasts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_broadcasts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `target_type` enum('ALL','ALL_STUDENTS','ALL_FACULTY','STUDENT','FACULTY') NOT NULL DEFAULT 'ALL',
  `target_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `is_unsent` tinyint(1) NOT NULL DEFAULT 0,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `priority` enum('NORMAL','URGENT','ACADEMIC','EVENT') NOT NULL DEFAULT 'NORMAL',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_admin_broadcasts_admin` (`admin_id`),
  KEY `idx_admin_broadcasts_created` (`created_at`),
  KEY `idx_admin_broadcasts_target` (`target_type`,`target_id`),
  KEY `idx_admin_broadcasts_lifecycle` (`is_unsent`,`is_pinned`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_broadcasts`
--

LOCK TABLES `admin_broadcasts` WRITE;
/*!40000 ALTER TABLE `admin_broadcasts` DISABLE KEYS */;
INSERT INTO `admin_broadcasts` VALUES (1,1,'STUDENT',1,'hi',0,0,'NORMAL','2026-09-20 07:10:17',NULL);
/*!40000 ALTER TABLE `admin_broadcasts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_permissions`
--

DROP TABLE IF EXISTS `admin_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_admin_module` (`admin_id`,`module_id`),
  KEY `module_id` (`module_id`),
  CONSTRAINT `admin_permissions_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE,
  CONSTRAINT `admin_permissions_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_permissions`
--

LOCK TABLES `admin_permissions` WRITE;
/*!40000 ALTER TABLE `admin_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `role` varchar(50) DEFAULT 'SUPER ADMIN',
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_admin_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'Super Admin','SUPER ADMIN','admin@college.edu','$2y$10$nDcTBr2F2vSPRUDL7sXHP.3tgFHV1LJEgUh/DToNZYDmbGT7LKoP2','1234567890','profile_1_1789708562.png','ACTIVE','2026-09-16 15:43:02');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admission_inquiries`
--

DROP TABLE IF EXISTS `admission_inquiries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admission_inquiries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `previous_qualification` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','contacted','admitted','rejected') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_inquiry_department` (`department_id`),
  CONSTRAINT `fk_inquiry_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admission_inquiries`
--

LOCK TABLES `admission_inquiries` WRITE;
/*!40000 ALTER TABLE `admission_inquiries` DISABLE KEYS */;
INSERT INTO `admission_inquiries` VALUES (2,'Aarav Sharma','aarav.sharma@example.com','+91 9876543210',1,'12th Science (PCM 88%)','Inquiring about hostel facilities and merit scholarships for 2026-27 batch.','contacted','','2026-09-23 14:09:59','2026-09-23 14:41:51');
/*!40000 ALTER TABLE `admission_inquiries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assessment_marks`
--

DROP TABLE IF EXISTS `assessment_marks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_marks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assessment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `marks_obtained` decimal(5,2) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `assessment_id` (`assessment_id`,`student_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `assessment_marks_ibfk_1` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessment_marks_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assessment_marks`
--

LOCK TABLES `assessment_marks` WRITE;
/*!40000 ALTER TABLE `assessment_marks` DISABLE KEYS */;
/*!40000 ALTER TABLE `assessment_marks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assessments`
--

DROP TABLE IF EXISTS `assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `max_marks` int(11) NOT NULL DEFAULT 100,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  KEY `faculty_id` (`faculty_id`),
  CONSTRAINT `assessments_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessments_ibfk_2` FOREIGN KEY (`faculty_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assessments`
--

LOCK TABLES `assessments` WRITE;
/*!40000 ALTER TABLE `assessments` DISABLE KEYS */;
/*!40000 ALTER TABLE `assessments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assignment_submissions`
--

DROP TABLE IF EXISTS `assignment_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assignment_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assignment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL DEFAULT 0,
  `file_name` varchar(255) NOT NULL COMMENT 'Original filename for display',
  `is_late` tinyint(1) NOT NULL DEFAULT 0,
  `marks_obtained` decimal(6,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `graded_at` datetime DEFAULT NULL,
  `graded_by` int(11) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_submission_student` (`assignment_id`,`student_id`),
  KEY `idx_submissions_assignment` (`assignment_id`),
  KEY `idx_submissions_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assignment_submissions`
--

LOCK TABLES `assignment_submissions` WRITE;
/*!40000 ALTER TABLE `assignment_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `assignment_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assignments`
--

DROP TABLE IF EXISTS `assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `max_marks` int(11) NOT NULL DEFAULT 100,
  `deadline` datetime NOT NULL,
  `reference_file` varchar(500) DEFAULT NULL COMMENT 'Optional file attachment by faculty',
  `allow_late` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = allow late submissions with flag',
  `status` enum('ACTIVE','CLOSED') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_assignments_course` (`course_id`),
  KEY `idx_assignments_faculty` (`faculty_id`),
  KEY `idx_assignments_deadline` (`deadline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assignments`
--

LOCK TABLES `assignments` WRITE;
/*!40000 ALTER TABLE `assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('PRESENT','ABSENT','LATE') NOT NULL,
  `marked_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`,`course_id`,`date`),
  KEY `idx_attendance_date` (`date`),
  KEY `idx_attendance_student_course` (`student_id`,`course_id`),
  KEY `attendance_ibfk_new2` (`course_id`),
  KEY `attendance_ibfk_new3` (`marked_by`),
  CONSTRAINT `attendance_ibfk_new1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_ibfk_new2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_ibfk_new3` FOREIGN KEY (`marked_by`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
INSERT INTO `attendance` VALUES (1,1,1,'2026-09-18','ABSENT',2,'2026-09-18 07:04:23'),(2,2,1,'2026-09-18','ABSENT',2,'2026-09-18 07:04:23'),(5,1,1,'2026-09-17','PRESENT',2,'2026-09-18 07:05:31'),(6,2,1,'2026-09-17','ABSENT',2,'2026-09-18 07:05:31'),(11,1,1,'2026-09-19','PRESENT',2,'2026-09-19 17:20:06'),(12,2,1,'2026-09-19','ABSENT',2,'2026-09-19 17:20:06');
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conversations`
--

DROP TABLE IF EXISTS `conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conv_type` enum('FACULTY_STUDENT','PEER_STUDENT','FACULTY_FACULTY') NOT NULL DEFAULT 'FACULTY_STUDENT',
  `user1_id` int(11) DEFAULT NULL,
  `user1_type` enum('STUDENT','FACULTY') DEFAULT NULL,
  `user2_id` int(11) DEFAULT NULL,
  `user2_type` enum('STUDENT','FACULTY') DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `faculty_id` int(11) DEFAULT NULL,
  `last_message_at` datetime DEFAULT NULL,
  `student_cleared_at` datetime DEFAULT NULL,
  `faculty_cleared_at` datetime DEFAULT NULL,
  `user1_cleared_at` datetime DEFAULT NULL,
  `user2_cleared_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_conversation_pair` (`student_id`,`faculty_id`),
  UNIQUE KEY `uq_participants` (`user1_id`,`user1_type`,`user2_id`,`user2_type`),
  KEY `idx_conv_student` (`student_id`),
  KEY `idx_conv_faculty` (`faculty_id`),
  KEY `idx_conv_last_msg` (`last_message_at`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversations`
--

LOCK TABLES `conversations` WRITE;
/*!40000 ALTER TABLE `conversations` DISABLE KEYS */;
INSERT INTO `conversations` VALUES (1,'FACULTY_STUDENT',1,'STUDENT',2,'FACULTY',1,2,'2026-09-20 15:31:17','2026-09-20 15:31:17',NULL,'2026-09-20 15:31:17',NULL,'2026-09-20 05:09:36'),(2,'FACULTY_STUDENT',2,'STUDENT',2,'FACULTY',2,2,'2026-09-20 10:40:13',NULL,NULL,NULL,NULL,'2026-09-20 05:10:09'),(3,'PEER_STUDENT',1,'STUDENT',2,'STUDENT',1,NULL,'2026-09-20 15:31:13',NULL,NULL,NULL,NULL,'2026-09-20 09:57:14'),(4,'FACULTY_FACULTY',2,'FACULTY',4,'FACULTY',NULL,NULL,'2026-09-20 15:52:03',NULL,NULL,NULL,NULL,'2026-09-20 10:21:45');
/*!40000 ALTER TABLE `conversations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_assignments`
--

DROP TABLE IF EXISTS `course_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_id` (`course_id`,`faculty_id`),
  KEY `course_assignments_ibfk_new2` (`faculty_id`),
  CONSTRAINT `course_assignments_ibfk_new1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `course_assignments_ibfk_new2` FOREIGN KEY (`faculty_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_assignments`
--

LOCK TABLES `course_assignments` WRITE;
/*!40000 ALTER TABLE `course_assignments` DISABLE KEYS */;
INSERT INTO `course_assignments` VALUES (1,1,2);
/*!40000 ALTER TABLE `course_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `credits` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_code` (`course_code`),
  KEY `semester_id` (`semester_id`),
  KEY `idx_course_dept_sem` (`department_id`,`semester_id`),
  CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `courses_ibfk_2` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courses`
--

LOCK TABLES `courses` WRITE;
/*!40000 ALTER TABLE `courses` DISABLE KEYS */;
INSERT INTO `courses` VALUES (1,3,1,'BCAC101','C Programming',3);
/*!40000 ALTER TABLE `courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `department_semesters`
--

DROP TABLE IF EXISTS `department_semesters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `department_semesters` (
  `department_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  PRIMARY KEY (`department_id`,`semester_id`),
  KEY `semester_id` (`semester_id`),
  CONSTRAINT `department_semesters_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `department_semesters_ibfk_2` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `department_semesters`
--

LOCK TABLES `department_semesters` WRITE;
/*!40000 ALTER TABLE `department_semesters` DISABLE KEYS */;
INSERT INTO `department_semesters` VALUES (1,2);
/*!40000 ALTER TABLE `department_semesters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dept_code` varchar(20) NOT NULL,
  `dept_name` varchar(100) NOT NULL,
  `total_semesters` int(11) NOT NULL DEFAULT 8,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `dept_code` (`dept_code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'CS','Computer Science',8,'2026-09-16 15:43:02'),(2,'EE','Electrical Engineering',8,'2026-09-16 15:43:02'),(3,'BCA','Bachelor of Computer Application',8,'2026-09-17 11:46:50'),(4,'BBA','Bachelor of Business Administration',8,'2026-09-17 16:55:42');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedbacks`
--

DROP TABLE IF EXISTS `feedbacks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feedbacks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `user_role` enum('STUDENT','FACULTY','ADMIN','GUEST') NOT NULL DEFAULT 'GUEST',
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'General',
  `subject` varchar(200) NOT NULL,
  `rating` tinyint(3) unsigned DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('NEW','REVIEWED','RESOLVED') NOT NULL DEFAULT 'NEW',
  `admin_notes` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_role` (`user_role`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedbacks`
--

LOCK TABLES `feedbacks` WRITE;
/*!40000 ALTER TABLE `feedbacks` DISABLE KEYS */;
INSERT INTO `feedbacks` VALUES (1,NULL,'GUEST','John Guest','john.guest@example.com','+91 9988776655','Campus & Infrastructure','Wi-Fi coverage in Library second floor',4,'The Wi-Fi network in the reading hall upstairs tends to drop during peak hours. Please look into adding an access point.','REVIEWED','Acknowledged by IT support team.','Unknown','Unknown','2026-09-21 17:29:11','2026-09-21 17:29:11'),(2,1,'STUDENT','Sarah Student','sarah.student@greenfield.edu','+91 9123456780','Academic Curriculum','Request for Data Science elective course',5,'We would love to have a practical elective on Machine Learning and Data Science in the upcoming semester.','NEW',NULL,'Unknown','Unknown','2026-09-21 17:29:11','2026-09-21 17:29:11'),(3,1,'FACULTY','Dr. Robert Smith','robert.smith@greenfield.edu','+91 9876501234','Teaching & Faculty','Projector replacement in Lecture Hall 3',3,'The HDMI display connection on the podium in LH-3 keeps flickering during lectures.','NEW',NULL,'Unknown','Unknown','2026-09-21 17:29:11','2026-09-21 17:29:11');
/*!40000 ALTER TABLE `feedbacks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_requests`
--

DROP TABLE IF EXISTS `leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `applicant_id` int(11) NOT NULL,
  `applicant_type` enum('STUDENT','FACULTY') NOT NULL,
  `leave_type` enum('SICK','CASUAL','OTHER') NOT NULL DEFAULT 'CASUAL',
  `custom_subject` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text NOT NULL,
  `supporting_docs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`supporting_docs`)),
  `status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `admin_remarks` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL COMMENT 'Admin ID who reviewed',
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_leave_applicant` (`applicant_id`,`applicant_type`),
  KEY `idx_leave_status` (`status`),
  KEY `idx_leave_dates` (`start_date`,`end_date`),
  KEY `idx_leave_type` (`leave_type`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_requests`
--

LOCK TABLES `leave_requests` WRITE;
/*!40000 ALTER TABLE `leave_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `leave_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `message_reactions`
--

DROP TABLE IF EXISTS `message_reactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `message_reactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('STUDENT','FACULTY') NOT NULL,
  `reaction` varchar(20) NOT NULL COMMENT 'e.g. THUMBS_UP, HEART, QUESTION, BULB, CHECK',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_msg_reaction_user` (`message_id`,`user_id`,`user_type`,`reaction`),
  KEY `idx_reaction_msg` (`message_id`),
  CONSTRAINT `fk_reaction_msg` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `message_reactions`
--

LOCK TABLES `message_reactions` WRITE;
/*!40000 ALTER TABLE `message_reactions` DISABLE KEYS */;
INSERT INTO `message_reactions` VALUES (1,4,2,'FACULTY','👍','2026-09-20 07:51:34'),(2,7,1,'STUDENT','💡','2026-09-20 07:54:58'),(3,8,2,'STUDENT','👍','2026-09-20 10:00:55'),(4,10,2,'STUDENT','👍','2026-09-20 10:01:13'),(5,13,1,'STUDENT','💡','2026-09-20 10:01:17'),(6,14,4,'FACULTY','👍','2026-09-20 10:22:03');
/*!40000 ALTER TABLE `message_reactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` int(11) NOT NULL,
  `sender_type` enum('STUDENT','FACULTY') NOT NULL,
  `sender_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `reply_to_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `pinned_at` datetime DEFAULT NULL,
  `deleted_by_student` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_by_faculty` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_by_user1` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_by_user2` tinyint(1) NOT NULL DEFAULT 0,
  `is_unsent` tinyint(1) NOT NULL DEFAULT 0,
  `unsent_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_msg_conversation` (`conversation_id`),
  KEY `idx_msg_sender` (`sender_type`,`sender_id`),
  KEY `idx_msg_read` (`is_read`),
  KEY `idx_msg_created` (`conversation_id`,`created_at`),
  KEY `fk_msg_reply_to` (`reply_to_id`),
  KEY `idx_msg_flags` (`conversation_id`,`is_unsent`,`is_pinned`),
  CONSTRAINT `fk_msg_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_msg_reply_to` FOREIGN KEY (`reply_to_id`) REFERENCES `messages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT INTO `messages` VALUES (1,1,'FACULTY',2,'hi',NULL,1,0,NULL,0,0,0,0,0,NULL,'2026-09-20 05:09:50'),(2,2,'FACULTY',2,'hello',NULL,0,0,NULL,0,0,0,0,0,NULL,'2026-09-20 05:10:13'),(3,1,'STUDENT',1,'yes!',NULL,1,0,NULL,0,0,0,0,0,NULL,'2026-09-20 05:15:03'),(4,1,'STUDENT',1,'Test message for enhancement verification',NULL,1,1,NULL,0,0,0,0,1,'2026-09-20 13:21:34','2026-09-20 07:51:34'),(5,1,'FACULTY',2,'Replying to your test message',4,1,0,NULL,0,0,0,0,0,NULL,'2026-09-20 07:51:34'),(6,1,'STUDENT',1,'Hello Professor, I have a doubt regarding Chapter 4.',NULL,1,0,NULL,1,0,1,0,1,'2026-09-20 13:24:58','2026-09-20 07:54:58'),(7,1,'FACULTY',2,'Please review section 4.2 on binary trees.',6,1,1,NULL,0,0,0,0,0,NULL,'2026-09-20 07:54:58'),(8,3,'STUDENT',1,'Hey Jit, what are the topics for the upcoming lab exam?',NULL,1,0,NULL,0,0,0,0,0,NULL,'2026-09-20 10:00:55'),(9,3,'STUDENT',2,'It covers Graph Traversal and Dijkstra algorithm.',8,1,0,NULL,0,0,0,0,0,NULL,'2026-09-20 10:00:55'),(10,3,'STUDENT',1,'Hey Jit, what are the topics for the upcoming lab exam?',NULL,1,0,NULL,0,0,1,0,0,NULL,'2026-09-20 10:01:13'),(11,3,'STUDENT',2,'It covers Graph Traversal and Dijkstra algorithm.',10,1,0,NULL,0,0,0,0,0,NULL,'2026-09-20 10:01:13'),(12,1,'STUDENT',1,'Hello Professor, I have a doubt regarding Chapter 4.',NULL,1,0,NULL,0,0,1,0,1,'2026-09-20 15:31:17','2026-09-20 10:01:17'),(13,1,'FACULTY',2,'Please review section 4.2 on binary trees.',12,1,1,NULL,0,0,0,0,0,NULL,'2026-09-20 10:01:17'),(14,4,'FACULTY',2,'Hello colleague! Scheduling department meeting.',NULL,1,0,NULL,0,0,0,0,0,NULL,'2026-09-20 10:22:03'),(15,4,'FACULTY',4,'Acknowledged! Will attend.',14,1,0,NULL,0,0,0,0,0,NULL,'2026-09-20 10:22:03');
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `modules`
--

DROP TABLE IF EXISTS `modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_key` varchar(50) NOT NULL COMMENT 'Unique identifier for permission checks',
  `module_name` varchar(100) NOT NULL COMMENT 'Human-readable display name',
  `module_group` varchar(50) NOT NULL COMMENT 'Sidebar section grouping',
  `icon` varchar(50) DEFAULT 'box' COMMENT 'Lucide icon name',
  `url` varchar(255) NOT NULL COMMENT 'Primary URL path for sidebar link',
  `sort_order` int(11) DEFAULT 0 COMMENT 'Display order within sidebar',
  PRIMARY KEY (`id`),
  UNIQUE KEY `module_key` (`module_key`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modules`
--

LOCK TABLES `modules` WRITE;
/*!40000 ALTER TABLE `modules` DISABLE KEYS */;
INSERT INTO `modules` VALUES (1,'dashboard','Dashboard','MAIN','layout-dashboard','/views/admin/dashboard.php',1),(2,'students','Students','USERS','graduation-cap','/views/admin/students.php',10),(3,'faculty','Faculty','USERS','users','/views/admin/faculty.php',11),(4,'departments','Departments','ACADEMICS','building-2','/views/admin/departments.php',20),(5,'semesters','Semesters','ACADEMICS','calendar-days','/views/admin/semesters.php',21),(6,'subjects','Subjects','ACADEMICS','book-open','/views/admin/subjects.php',22),(7,'subject_assignments','Assignments','ACADEMICS','clipboard-list','/views/admin/subject_assignments.php',23),(8,'timetables','Timetables','ACADEMICS','calendar','/views/admin/timetables.php',24),(9,'broadcasts','Broadcasts','COMMUNICATION','radio','/views/admin/broadcasts.php',30),(10,'notices','Notices','COMMUNICATION','bell','/views/admin/notices.php',31),(11,'leave_requests','Leave Requests','COMMUNICATION','calendar-off','/views/admin/leave_requests.php',32),(12,'feedbacks','Feedbacks','COMMUNICATION','message-square-heart','/views/admin/view-feedback.php',33),(13,'security_logs','Security Logs','SYSTEMS MATRIX','fingerprint','/views/admin/security_logs.php',90),(14,'manage_admins','Manage Admins','ADMINISTRATION','shield-check','/views/admin/manage-admins.php',100),(15,'admission_inquiries','Admission Inquiries','COMMUNICATION','user-plus','/views/admin/admission-inquiries.php',34);
/*!40000 ALTER TABLE `modules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notices`
--

DROP TABLE IF EXISTS `notices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `target_role` enum('ALL','FACULTY','STUDENT') DEFAULT 'ALL',
  `attachment_url` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `is_pinned` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_notice_role` (`target_role`),
  KEY `idx_notice_pinned` (`is_pinned`),
  KEY `notices_ibfk_new1` (`created_by`),
  CONSTRAINT `notices_ibfk_new1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notices`
--

LOCK TABLES `notices` WRITE;
/*!40000 ALTER TABLE `notices` DISABLE KEYS */;
INSERT INTO `notices` VALUES (1,'Admissions Open for Academic Session 2026-2027','admissions-open-2026-2027','Applications are officially invited for Undergraduate and Postgraduate programs for the upcoming academic year. Candidates may submit their inquiries online or visit the campus admissions office between 9:00 AM and 4:00 PM.','ALL',NULL,1,1,'2026-09-23 11:25:10'),(2,'Annual Cultural & Innovation Fest TechNova 2026 Announced','technova-2026-announcement','Greenfield College is pleased to announce TechNova 2026, our flagship national level inter-collegiate technical and cultural symposium. Registrations for competitive tracks and hackathons will commence shortly.','ALL',NULL,1,0,'2026-09-23 11:25:10'),(3,'Campus Placement Drive: Top Tier-1 Recruiters Visiting','campus-placement-drive-2026','Final year students are advised to register on the placement portal. Upcoming interview schedules and company eligibility criteria have been updated in the placement brochure.','ALL',NULL,1,0,'2026-09-23 11:25:10');
/*!40000 ALTER TABLE `notices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_attempts`
--

DROP TABLE IF EXISTS `quiz_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `answers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'JSON object: {"question_id": "selected_option", ...}' CHECK (json_valid(`answers`)),
  `score` decimal(6,2) NOT NULL DEFAULT 0.00,
  `total_marks` int(11) NOT NULL DEFAULT 0,
  `correct_count` int(11) NOT NULL DEFAULT 0,
  `wrong_count` int(11) NOT NULL DEFAULT 0,
  `unanswered_count` int(11) NOT NULL DEFAULT 0,
  `time_taken_seconds` int(11) NOT NULL DEFAULT 0 COMMENT 'Actual time spent in seconds',
  `started_at` datetime NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attempt_student` (`quiz_id`,`student_id`),
  KEY `idx_attempts_quiz` (`quiz_id`),
  KEY `idx_attempts_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_attempts`
--

LOCK TABLES `quiz_attempts` WRITE;
/*!40000 ALTER TABLE `quiz_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `quiz_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_questions`
--

DROP TABLE IF EXISTS `quiz_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `option_a` varchar(500) NOT NULL,
  `option_b` varchar(500) NOT NULL,
  `option_c` varchar(500) NOT NULL,
  `option_d` varchar(500) NOT NULL,
  `correct_option` enum('A','B','C','D') NOT NULL,
  `marks` int(11) NOT NULL DEFAULT 1 COMMENT 'Marks for this question',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_questions_quiz` (`quiz_id`),
  KEY `idx_questions_sort` (`quiz_id`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_questions`
--

LOCK TABLES `quiz_questions` WRITE;
/*!40000 ALTER TABLE `quiz_questions` DISABLE KEYS */;
/*!40000 ALTER TABLE `quiz_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quizzes`
--

DROP TABLE IF EXISTS `quizzes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `duration_minutes` int(11) NOT NULL DEFAULT 30 COMMENT 'Time limit in minutes',
  `max_marks` int(11) NOT NULL DEFAULT 0 COMMENT 'Auto-calculated from questions or manual',
  `start_time` datetime DEFAULT NULL COMMENT 'When quiz becomes available',
  `end_time` datetime DEFAULT NULL COMMENT 'When quiz closes',
  `show_results` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Show results to students after submission',
  `shuffle_questions` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('DRAFT','PUBLISHED','CLOSED') NOT NULL DEFAULT 'DRAFT',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_quizzes_course` (`course_id`),
  KEY `idx_quizzes_faculty` (`faculty_id`),
  KEY `idx_quizzes_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quizzes`
--

LOCK TABLES `quizzes` WRITE;
/*!40000 ALTER TABLE `quizzes` DISABLE KEYS */;
/*!40000 ALTER TABLE `quizzes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_logs`
--

DROP TABLE IF EXISTS `security_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `user_table` varchar(20) DEFAULT NULL,
  `email_attempt` varchar(100) NOT NULL,
  `status` enum('GRANTED','BLOCKED') NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `browser_vector` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_security_logs_status` (`status`),
  KEY `idx_security_logs_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_logs`
--

LOCK TABLES `security_logs` WRITE;
/*!40000 ALTER TABLE `security_logs` DISABLE KEYS */;
INSERT INTO `security_logs` VALUES (1,1,NULL,'admin@college.edu','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-18 02:10:13'),(2,1,'admins','admin@college.edu','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-18 04:42:55'),(3,2,'teachers','ankanbiswas762@gmail.com','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-18 04:44:15'),(4,1,'admins','admin@college.edu','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-18 05:02:31'),(5,4,'teachers','faculty2@gmail.com','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-18 05:45:14'),(6,1,'admins','admin@college.edu','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-18 05:46:29'),(7,NULL,'teachers','ankanbiswas762@gmail.com','BLOCKED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-18 06:13:52'),(8,NULL,'teachers','ankanbiswas7699@gmail.com','BLOCKED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-18 06:14:15'),(9,NULL,'teachers','ankanbiswas762@gmail.com','BLOCKED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-18 06:14:28'),(10,NULL,'teachers','ankanbiswas762@gmail.com','BLOCKED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-18 06:14:48'),(11,1,'admins','admin@college.edu','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-18 06:15:07'),(12,2,'teachers','faculty1@gmail.com','BLOCKED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-18 06:16:07'),(13,2,'teachers','faculty1@gmail.com','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-18 06:16:31'),(14,1,'admins','admin@college.edu','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-18 06:17:07'),(15,2,'teachers','faculty1@gmail.com','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-18 06:17:55'),(16,2,'teachers','faculty1@gmail.com','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-18 17:22:48'),(17,1,'admins','admin@college.edu','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-18 17:23:22'),(18,1,'admins','admin@college.edu','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 06:53:29'),(19,NULL,'teachers','ankanbiswas7699@gmail.com','BLOCKED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 07:14:23'),(20,1,'students','ankanbiswas7699@gmail.com','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 07:14:38'),(21,1,'admins','admin@college.edu','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 07:47:33'),(22,1,'admins','admin@college.edu','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 09:30:08'),(23,1,'students','ankanbiswas7699@gmail.com','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 09:36:19'),(24,2,'teachers','faculty1@gmail.com','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 09:37:48'),(25,1,'students','ankanbiswas7699@gmail.com','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 09:39:51'),(26,NULL,'admins','faculty1@gmail.com','BLOCKED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 09:40:04'),(27,1,'admins','admin@college.edu','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 09:40:09'),(28,1,'students','ankanbiswas7699@gmail.com','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 10:37:57'),(29,1,'admins','admin@college.edu','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 11:24:00'),(30,2,'teachers','faculty1@gmail.com','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 11:30:05'),(31,1,'students','ankanbiswas7699@gmail.com','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 17:15:40'),(32,2,'teachers','faculty1@gmail.com','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 17:19:44'),(33,1,'admins','admin@college.edu','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 17:49:55'),(34,2,'teachers','faculty1@gmail.com','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 18:13:16'),(35,1,'students','ankanbiswas7699@gmail.com','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 18:14:53'),(36,2,'teachers','faculty1@gmail.com','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-20 04:40:22'),(37,1,'students','ankanbiswas7699@gmail.com','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-20 05:11:34'),(38,1,'admins','admin@college.edu','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-20 05:29:50'),(39,1,'students','ankanbiswas7699@gmail.com','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-20 05:44:12'),(40,2,'teachers','faculty1@gmail.com','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-20 06:28:47'),(41,1,'students','ankanbiswas7699@gmail.com','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-20 06:30:34'),(42,1,'admins','admin@college.edu','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-20 06:31:15'),(43,2,'teachers','faculty1@gmail.com','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-20 06:56:44'),(44,1,'admins','admin@college.edu','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-20 07:09:44'),(45,2,'teachers','faculty1@gmail.com','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-20 07:20:31'),(46,1,'admins','admin@college.edu','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-20 07:59:49'),(47,1,'students','ankanbiswas7699@gmail.com','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-20 08:00:27'),(48,2,'teachers','faculty1@gmail.com','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-20 10:05:18'),(49,1,'students','ankanbiswas7699@gmail.com','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-20 10:32:40'),(50,1,'admins','admin@college.edu','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-20 10:33:40'),(51,1,'admins','admin@college.edu','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-21 17:12:13'),(52,1,'admins','admin@college.edu','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-21 17:50:10'),(53,1,'admins','admin@college.edu','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 11:08:32'),(54,2,'teachers','faculty1@gmail.com','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 11:10:20'),(55,1,'students','ankanbiswas7699@gmail.com','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 11:17:28'),(56,1,'admins','admin@college.edu','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-23 09:35:26'),(57,1,'admins','admin@college.edu','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-23 13:48:25'),(58,1,'admins','admin@college.edu','GRANTED','::1','UNKNOWN','2026-09-23 14:15:44'),(59,1,'admins','admin@college.edu','GRANTED','::1','UNKNOWN','2026-09-23 14:16:03'),(60,1,'admins','admin@college.edu','GRANTED','::1','UNKNOWN','2026-09-23 14:16:19'),(61,1,'admins','admin@college.edu','GRANTED','::1','UNKNOWN','2026-09-23 14:16:41'),(62,1,'admins','admin@college.edu','GRANTED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-23 14:21:26'),(63,1,'admins','admin@college.edu','GRANTED','::1','UNKNOWN','2026-09-23 14:41:23'),(64,1,'admins','admin@college.edu','GRANTED','::1','UNKNOWN','2026-09-23 14:42:21'),(65,1,'admins','admin@college.edu','GRANTED','::1','UNKNOWN','2026-09-23 14:46:16'),(66,1,'admins','admin@college.edu','GRANTED','::1','UNKNOWN','2026-09-23 14:50:42'),(67,1,'admins','admin@college.edu','GRANTED','::1','UNKNOWN','2026-09-23 15:00:19');
/*!40000 ALTER TABLE `security_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `semesters`
--

DROP TABLE IF EXISTS `semesters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `semesters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `semester_number` int(11) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  PRIMARY KEY (`id`),
  UNIQUE KEY `semester_number` (`semester_number`,`academic_year`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `semesters`
--

LOCK TABLES `semesters` WRITE;
/*!40000 ALTER TABLE `semesters` DISABLE KEYS */;
INSERT INTO `semesters` VALUES (1,1,'2026-2027',NULL,NULL,'ACTIVE'),(2,2,'2026-2027',NULL,NULL,'INACTIVE'),(3,3,'2026-2027',NULL,NULL,'ACTIVE'),(4,4,'2026-2027',NULL,NULL,'INACTIVE');
/*!40000 ALTER TABLE `semesters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `starred_messages`
--

DROP TABLE IF EXISTS `starred_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `starred_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_type` enum('STUDENT','FACULTY') NOT NULL,
  `message_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_starred_user_msg` (`user_id`,`user_type`,`message_id`),
  KEY `idx_starred_user` (`user_id`,`user_type`),
  KEY `fk_starred_msg` (`message_id`),
  CONSTRAINT `fk_starred_msg` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `starred_messages`
--

LOCK TABLES `starred_messages` WRITE;
/*!40000 ALTER TABLE `starred_messages` DISABLE KEYS */;
INSERT INTO `starred_messages` VALUES (1,1,'STUDENT',4,'2026-09-20 07:51:34'),(2,1,'STUDENT',7,'2026-09-20 07:54:58'),(3,1,'STUDENT',13,'2026-09-20 10:01:17');
/*!40000 ALTER TABLE `starred_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `department_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `roll_number` varchar(50) NOT NULL,
  `registration_number` varchar(50) NOT NULL,
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `roll_number` (`roll_number`),
  UNIQUE KEY `registration_number` (`registration_number`),
  KEY `semester_id` (`semester_id`),
  KEY `idx_student_email` (`email`),
  KEY `idx_student_dept_sem` (`department_id`,`semester_id`),
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `students_ibfk_2` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (1,'Ankan Biswas','ankanbiswas7699@gmail.com','$argon2id$v=19$m=65536,t=4,p=1$WmpWYTJUQ1o5cTF3VFUvSQ$JKkmK2k88cnbC6I0+EeNO0zwcUt5PsQQH7CJFgLU45E','7908761796',NULL,3,1,'34542723006','233451010006','ACTIVE','2026-09-18 05:21:52'),(2,'Jit Biswas','happy@gmail.com','$argon2id$v=19$m=65536,t=4,p=1$djR5b0h5bWN6T0J0b1RjYg$r1QH6Galm1mzDiIDqOuP5Fb6OAwo75a3WbOGyLFMu6k','',NULL,3,1,'34542723028','233451010028','ACTIVE','2026-09-18 05:55:21');
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `study_materials`
--

DROP TABLE IF EXISTS `study_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `study_materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL DEFAULT 0,
  `uploaded_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  KEY `faculty_id` (`faculty_id`),
  CONSTRAINT `study_materials_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `study_materials_ibfk_2` FOREIGN KEY (`faculty_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `study_materials`
--

LOCK TABLES `study_materials` WRITE;
/*!40000 ALTER TABLE `study_materials` DISABLE KEYS */;
/*!40000 ALTER TABLE `study_materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher_departments`
--

DROP TABLE IF EXISTS `teacher_departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teacher_departments` (
  `teacher_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  PRIMARY KEY (`teacher_id`,`department_id`),
  KEY `fk_td_dept` (`department_id`),
  CONSTRAINT `fk_td_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_td_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_departments`
--

LOCK TABLES `teacher_departments` WRITE;
/*!40000 ALTER TABLE `teacher_departments` DISABLE KEYS */;
INSERT INTO `teacher_departments` VALUES (2,1),(2,3),(4,3),(4,4);
/*!40000 ALTER TABLE `teacher_departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teachers`
--

DROP TABLE IF EXISTS `teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teachers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `designation` varchar(100) NOT NULL,
  `qualification` varchar(255) NOT NULL,
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_teacher_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teachers`
--

LOCK TABLES `teachers` WRITE;
/*!40000 ALTER TABLE `teachers` DISABLE KEYS */;
INSERT INTO `teachers` VALUES (2,'Ankan Biswas','faculty1@gmail.com','$argon2id$v=19$m=65536,t=4,p=1$OWFzVXVlVUtTcEV2bHhEbg$7f7QB81nVuLlJKetBUEM6a6c3octR9uMDBI3iRHFhTU','',NULL,'Part Timer','MCA','ACTIVE','2026-09-17 17:06:10'),(4,'Ankan Biswas','faculty2@gmail.com','$argon2id$v=19$m=65536,t=4,p=1$NnNFdHhYbFhoaUVNamF1cA$RZVaIAPEDKj6NbIIUGjpMasi9W3/FpKNojkLwBrqhDI','',NULL,'Part Timer','MBA','ACTIVE','2026-09-18 05:44:08');
/*!40000 ALTER TABLE `teachers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timetables`
--

DROP TABLE IF EXISTS `timetables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timetables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room_number` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_timetable_dept` (`department_id`),
  KEY `fk_timetable_sem` (`semester_id`),
  KEY `fk_timetable_course` (`course_id`),
  KEY `fk_timetable_faculty` (`faculty_id`),
  CONSTRAINT `fk_timetable_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_timetable_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_timetable_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_timetable_sem` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timetables`
--

LOCK TABLES `timetables` WRITE;
/*!40000 ALTER TABLE `timetables` DISABLE KEYS */;
/*!40000 ALTER TABLE `timetables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'college_cms'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-23 21:01:05
