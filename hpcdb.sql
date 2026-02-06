-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.43 - MySQL Community Server - GPL
-- Server OS:                    Linux
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for system_services
CREATE DATABASE IF NOT EXISTS `system_services` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `system_services`;

-- Dumping structure for table system_services.answers
CREATE TABLE IF NOT EXISTS `answers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `submission_id` bigint unsigned NOT NULL,
  `question_id` bigint unsigned NOT NULL,
  `answer_text` text COLLATE utf8mb4_unicode_ci COMMENT 'Câu TL tự luận hoặc đáp án chọn',
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL COMMENT 'Đúng/sai (auto-check)',
  `score` decimal(5,2) DEFAULT NULL COMMENT 'Điểm câu này',
  `feedback` text COLLATE utf8mb4_unicode_ci COMMENT 'Nhận xét từng câu',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `answers_submission_id_foreign` (`submission_id`),
  CONSTRAINT `answers_submission_id_foreign` FOREIGN KEY (`submission_id`) REFERENCES `assignment_submissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.assignments
CREATE TABLE IF NOT EXISTS `assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `course_id` bigint unsigned DEFAULT NULL,
  `lecturer_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` enum('quiz','essay','mixed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mixed',
  `deadline` timestamp NULL DEFAULT NULL,
  `time_limit` int unsigned DEFAULT NULL COMMENT 'Minutes',
  `max_attempts` int unsigned NOT NULL DEFAULT '1',
  `show_answers` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Show answers after submit',
  `shuffle_questions` tinyint(1) NOT NULL DEFAULT '0',
  `shuffle_options` tinyint(1) NOT NULL DEFAULT '0',
  `question_pool_enabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Bật chế độ random đề thi từ ngân hàng câu hỏi',
  `question_pool_config` json DEFAULT NULL COMMENT 'Cấu hình số câu theo độ khó: {"easy": 10, "medium": 30, "hard": 10, "total": 50}',
  `status` enum('draft','published','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assignments_course_id_index` (`course_id`),
  KEY `assignments_lecturer_id_index` (`lecturer_id`),
  CONSTRAINT `assignments_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `assignments_lecturer_id_foreign` FOREIGN KEY (`lecturer_id`) REFERENCES `lecturer` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.assignment_submissions
CREATE TABLE IF NOT EXISTS `assignment_submissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `assignment_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `attempt` int unsigned NOT NULL DEFAULT '1' COMMENT 'Lần làm thứ mấy',
  `started_at` timestamp NULL DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `auto_score` decimal(5,2) DEFAULT NULL COMMENT 'Điểm tự động chấm',
  `manual_score` decimal(5,2) DEFAULT NULL COMMENT 'Điểm GV chấm',
  `total_score` decimal(5,2) DEFAULT NULL COMMENT 'Tổng điểm',
  `status` enum('in_progress','submitted','graded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'in_progress',
  `graded_by` bigint unsigned DEFAULT NULL,
  `graded_at` timestamp NULL DEFAULT NULL,
  `feedback` text COLLATE utf8mb4_unicode_ci COMMENT 'Nhận xét của GV',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.attendances
CREATE TABLE IF NOT EXISTS `attendances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `status` enum('present','absent','late','excused','not_marked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_marked',
  `check_in_time` time DEFAULT NULL,
  `minutes_late` int NOT NULL DEFAULT '0',
  `note` text COLLATE utf8mb4_unicode_ci,
  `excuse_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `excuse_document` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marked_by` bigint unsigned DEFAULT NULL,
  `marked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendances_session_id_student_id_unique` (`session_id`,`student_id`),
  KEY `attendances_marked_by_foreign` (`marked_by`),
  KEY `attendances_student_id_index` (`student_id`),
  KEY `attendances_status_index` (`status`),
  KEY `attendances_marked_at_index` (`marked_at`),
  CONSTRAINT `attendances_marked_by_foreign` FOREIGN KEY (`marked_by`) REFERENCES `lecturer` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendances_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `attendance_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendances_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `student` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.attendance_sessions
CREATE TABLE IF NOT EXISTS `attendance_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `course_id` bigint unsigned NOT NULL,
  `session_number` int NOT NULL,
  `session_date` date NOT NULL,
  `day_of_week` tinyint NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `topic` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `room` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('scheduled','in_progress','completed','cancelled','holiday') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `marked_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendance_sessions_course_id_session_number_unique` (`course_id`,`session_number`),
  KEY `attendance_sessions_marked_by_foreign` (`marked_by`),
  KEY `attendance_sessions_course_id_session_date_index` (`course_id`,`session_date`),
  KEY `attendance_sessions_status_index` (`status`),
  KEY `attendance_sessions_session_date_index` (`session_date`),
  CONSTRAINT `attendance_sessions_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_sessions_marked_by_foreign` FOREIGN KEY (`marked_by`) REFERENCES `lecturer` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.audit_logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_id` bigint unsigned DEFAULT NULL,
  `data` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_index` (`user_id`),
  KEY `audit_logs_target_type_target_id_index` (`target_type`,`target_id`),
  KEY `audit_logs_action_index` (`action`),
  KEY `audit_logs_created_at_index` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.calendar
CREATE TABLE IF NOT EXISTS `calendar` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `event_type` enum('task','event') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `task_id` bigint unsigned DEFAULT NULL,
  `participant_id` bigint unsigned NOT NULL,
  `participant_type` enum('lecturer','student') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `creator_id` bigint unsigned NOT NULL,
  `creator_type` enum('lecturer','student') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `calendar_participant_type_participant_id_index` (`participant_type`,`participant_id`),
  KEY `calendar_start_time_index` (`start_time`),
  KEY `calendar_task_id_index` (`task_id`),
  CONSTRAINT `calendar_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `task` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.chapters
CREATE TABLE IF NOT EXISTS `chapters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `question_bank_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_index` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chapters_question_bank_id_index` (`question_bank_id`),
  CONSTRAINT `chapters_question_bank_id_foreign` FOREIGN KEY (`question_bank_id`) REFERENCES `question_banks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.class
CREATE TABLE IF NOT EXISTS `class` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `class_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `class_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department_id` bigint unsigned NOT NULL,
  `lecturer_id` bigint unsigned DEFAULT NULL,
  `school_year` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `class_class_code_unique` (`class_code`),
  KEY `class_faculty_id_foreign` (`department_id`),
  KEY `class_lecturer_id_foreign` (`lecturer_id`),
  CONSTRAINT `class_faculty_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `department` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_lecturer_id_foreign` FOREIGN KEY (`lecturer_id`) REFERENCES `lecturer` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.courses
CREATE TABLE IF NOT EXISTS `courses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `credits` int NOT NULL DEFAULT '3',
  `description` text COLLATE utf8mb4_unicode_ci,
  `semester_id` bigint unsigned NOT NULL,
  `lecturer_id` bigint unsigned DEFAULT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `schedule_days` json DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `room` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_sessions` int NOT NULL DEFAULT '30',
  `max_absences` int NOT NULL DEFAULT '3',
  `absence_warning` int NOT NULL DEFAULT '2',
  `late_threshold_minutes` int NOT NULL DEFAULT '15',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('draft','active','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `sessions_generated` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `courses_code_semester_id_unique` (`code`,`semester_id`),
  KEY `courses_department_id_foreign` (`department_id`),
  KEY `courses_semester_id_index` (`semester_id`),
  KEY `courses_lecturer_id_index` (`lecturer_id`),
  KEY `courses_status_index` (`status`),
  KEY `courses_start_date_end_date_index` (`start_date`,`end_date`),
  CONSTRAINT `courses_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `department` (`id`) ON DELETE SET NULL,
  CONSTRAINT `courses_lecturer_id_foreign` FOREIGN KEY (`lecturer_id`) REFERENCES `lecturer` (`id`) ON DELETE SET NULL,
  CONSTRAINT `courses_semester_id_foreign` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.course_enrollments
CREATE TABLE IF NOT EXISTS `course_enrollments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `course_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `enrolled_at` date NOT NULL,
  `status` enum('active','dropped','completed','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `note` text COLLATE utf8mb4_unicode_ci,
  `dropped_at` date DEFAULT NULL,
  `drop_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_enrollments_course_id_student_id_unique` (`course_id`,`student_id`),
  KEY `course_enrollments_student_id_index` (`student_id`),
  KEY `course_enrollments_status_index` (`status`),
  CONSTRAINT `course_enrollments_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `course_enrollments_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `student` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.department
CREATE TABLE IF NOT EXISTS `department` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('school','faculty','department') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_at` timestamp NOT NULL,
  `created_at` timestamp NOT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `department_parent_id_foreign` (`parent_id`),
  CONSTRAINT `department_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `department` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.exams
CREATE TABLE IF NOT EXISTS `exams` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `question_bank_id` bigint unsigned NOT NULL,
  `course_id` bigint unsigned DEFAULT NULL,
  `lecturer_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `time_limit` int unsigned NOT NULL COMMENT 'Thời gian làm bài (phút)',
  `total_questions` int unsigned NOT NULL COMMENT 'Tổng số câu trong đề',
  `max_attempts` int unsigned NOT NULL DEFAULT '2',
  `difficulty_config` json DEFAULT NULL COMMENT 'Tỉ lệ độ khó: easy, medium, hard',
  `exam_codes_count` int unsigned NOT NULL DEFAULT '4' COMMENT 'Số mã đề',
  `show_answers_after_submit` tinyint(1) NOT NULL DEFAULT '1',
  `shuffle_questions` tinyint(1) NOT NULL DEFAULT '1',
  `shuffle_options` tinyint(1) NOT NULL DEFAULT '1',
  `anti_cheat_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `status` enum('draft','published','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `exams_question_bank_id_index` (`question_bank_id`),
  KEY `exams_course_id_index` (`course_id`),
  KEY `exams_lecturer_id_index` (`lecturer_id`),
  CONSTRAINT `exams_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `exams_lecturer_id_foreign` FOREIGN KEY (`lecturer_id`) REFERENCES `lecturer` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exams_question_bank_id_foreign` FOREIGN KEY (`question_bank_id`) REFERENCES `question_banks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.exam_codes
CREATE TABLE IF NOT EXISTS `exam_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `exam_id` bigint unsigned NOT NULL,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `question_order` json NOT NULL COMMENT 'Mảng question_id theo thứ tự',
  `option_shuffle_map` json DEFAULT NULL COMMENT 'Map xáo trộn đáp án',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exam_code_unique` (`exam_id`,`code`),
  KEY `exam_codes_exam_id_index` (`exam_id`),
  CONSTRAINT `exam_codes_exam_id_foreign` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.exam_submissions
CREATE TABLE IF NOT EXISTS `exam_submissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `exam_id` bigint unsigned NOT NULL,
  `exam_code_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `attempt` tinyint unsigned NOT NULL DEFAULT '1',
  `started_at` timestamp NULL DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `correct_count` int unsigned NOT NULL DEFAULT '0' COMMENT 'Số câu trả lời đúng',
  `wrong_count` int unsigned NOT NULL DEFAULT '0' COMMENT 'Số câu trả lời sai',
  `unanswered_count` int unsigned NOT NULL DEFAULT '0' COMMENT 'Số câu chưa trả lời',
  `total_score` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Điểm thang 10',
  `manual_score` decimal(5,2) DEFAULT NULL COMMENT 'Điểm giáo viên sửa',
  `graded_by` bigint unsigned DEFAULT NULL,
  `graded_at` timestamp NULL DEFAULT NULL,
  `grader_note` text COLLATE utf8mb4_unicode_ci,
  `status` enum('in_progress','submitted','graded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'in_progress',
  `anti_cheat_violations` json DEFAULT NULL COMMENT 'Log các vi phạm',
  `answers` json DEFAULT NULL COMMENT 'Câu trả lời',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `exam_submissions_graded_by_foreign` (`graded_by`),
  KEY `exam_submissions_exam_id_index` (`exam_id`),
  KEY `exam_submissions_exam_code_id_index` (`exam_code_id`),
  KEY `exam_submissions_student_id_index` (`student_id`),
  CONSTRAINT `exam_submissions_exam_code_id_foreign` FOREIGN KEY (`exam_code_id`) REFERENCES `exam_codes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exam_submissions_exam_id_foreign` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exam_submissions_graded_by_foreign` FOREIGN KEY (`graded_by`) REFERENCES `lecturer` (`id`) ON DELETE SET NULL,
  CONSTRAINT `exam_submissions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `student` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.extension_requests
CREATE TABLE IF NOT EXISTS `extension_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `assignment_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Lý do xin gia hạn',
  `new_deadline` timestamp NOT NULL COMMENT 'Deadline mới đề xuất',
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewer_note` text COLLATE utf8mb4_unicode_ci COMMENT 'Ghi chú của GV',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `extension_requests_reviewed_by_foreign` (`reviewed_by`),
  KEY `extension_requests_assignment_id_index` (`assignment_id`),
  KEY `extension_requests_student_id_index` (`student_id`),
  CONSTRAINT `extension_requests_assignment_id_foreign` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `extension_requests_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `lecturer` (`id`) ON DELETE SET NULL,
  CONSTRAINT `extension_requests_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `student` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.failed_jobs
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.holidays
CREATE TABLE IF NOT EXISTS `holidays` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `holidays_date_index` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.import_failures
CREATE TABLE IF NOT EXISTS `import_failures` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `import_job_id` bigint unsigned NOT NULL,
  `row_number` int NOT NULL,
  `attribute` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `errors` text COLLATE utf8mb4_unicode_ci,
  `values` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `import_failures_import_job_id_index` (`import_job_id`),
  KEY `import_failures_row_number_index` (`row_number`),
  KEY `import_failures_created_at_index` (`created_at`),
  CONSTRAINT `import_failures_import_job_id_foreign` FOREIGN KEY (`import_job_id`) REFERENCES `import_jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.import_jobs
CREATE TABLE IF NOT EXISTS `import_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `entity_type` enum('student','lecturer') COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','processing','done','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `total` int NOT NULL DEFAULT '0',
  `processed_rows` int NOT NULL DEFAULT '0',
  `success` int NOT NULL DEFAULT '0',
  `failed` int NOT NULL DEFAULT '0',
  `error` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `import_jobs_user_id_index` (`user_id`),
  KEY `import_jobs_entity_type_index` (`entity_type`),
  KEY `import_jobs_status_index` (`status`),
  KEY `import_jobs_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.jobs
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.lecturer
CREATE TABLE IF NOT EXISTS `lecturer` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` enum('male','female','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lecturer_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `assignes_id` bigint unsigned DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lecturer_email_unique` (`email`),
  UNIQUE KEY `lecturer_lecturer_code_unique` (`lecturer_code`),
  KEY `lecturer_unit_id_foreign` (`department_id`) USING BTREE,
  CONSTRAINT `lecturer_unit_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `department` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.lecturer_account
CREATE TABLE IF NOT EXISTS `lecturer_account` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lecturer_id` bigint unsigned NOT NULL,
  `username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_admin` tinyint NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lecturer_account_username_unique` (`username`),
  KEY `lecturer_account_lecturer_id_index` (`lecturer_id`),
  CONSTRAINT `lecturer_account_lecturer_id_foreign` FOREIGN KEY (`lecturer_id`) REFERENCES `lecturer` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.migrations
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `data` json DEFAULT NULL,
  `template_id` bigint unsigned DEFAULT NULL,
  `sender_id` bigint unsigned DEFAULT NULL,
  `sender_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_template_id_foreign` (`template_id`),
  KEY `notifications_type_priority_index` (`type`,`priority`),
  KEY `notifications_status_scheduled_at_index` (`status`,`scheduled_at`),
  KEY `notifications_sender_id_sender_type_index` (`sender_id`,`sender_type`),
  CONSTRAINT `notifications_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `notification_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.notification_templates
CREATE TABLE IF NOT EXISTS `notification_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_template` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `sms_template` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `push_template` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `in_app_template` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `channels` json NOT NULL,
  `priority` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `category` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_templates_name_unique` (`name`),
  KEY `notification_templates_name_is_active_index` (`name`,`is_active`),
  KEY `notification_templates_category_is_active_index` (`category`,`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.questions
CREATE TABLE IF NOT EXISTS `questions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `question_bank_id` bigint unsigned DEFAULT NULL,
  `chapter_id` bigint unsigned DEFAULT NULL,
  `subject_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assignment_id` bigint unsigned DEFAULT NULL,
  `type` enum('multiple_choice','short_answer','essay') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'multiple_choice',
  `difficulty` enum('easy','medium','hard') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium' COMMENT 'Mức độ khó: easy, medium, hard',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nội dung câu hỏi',
  `options` json DEFAULT NULL COMMENT 'Đáp án cho trắc nghiệm: [{"key":"A","text":"..."},...]',
  `correct_answer` text COLLATE utf8mb4_unicode_ci COMMENT 'Đáp án đúng: "A" hoặc keyword',
  `points` decimal(5,2) NOT NULL DEFAULT '1.00' COMMENT 'Điểm của câu hỏi',
  `order_index` int unsigned NOT NULL DEFAULT '0',
  `explanation` text COLLATE utf8mb4_unicode_ci COMMENT 'Giải thích đáp án',
  `rubric` text COLLATE utf8mb4_unicode_ci COMMENT 'Tiêu chí chấm điểm cho câu tự luận',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `questions_assignment_id_index` (`assignment_id`),
  KEY `questions_question_bank_id_index` (`question_bank_id`),
  KEY `questions_chapter_id_index` (`chapter_id`),
  KEY `questions_subject_code_index` (`subject_code`),
  CONSTRAINT `questions_assignment_id_foreign` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `questions_chapter_id_foreign` FOREIGN KEY (`chapter_id`) REFERENCES `chapters` (`id`) ON DELETE SET NULL,
  CONSTRAINT `questions_question_bank_id_foreign` FOREIGN KEY (`question_bank_id`) REFERENCES `question_banks` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=866 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.question_banks
CREATE TABLE IF NOT EXISTS `question_banks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `course_id` bigint unsigned DEFAULT NULL,
  `lecturer_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `subject_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `material_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `question_banks_course_id_index` (`course_id`),
  KEY `question_banks_lecturer_id_index` (`lecturer_id`),
  KEY `question_banks_subject_code_index` (`subject_code`),
  CONSTRAINT `question_banks_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `question_banks_lecturer_id_foreign` FOREIGN KEY (`lecturer_id`) REFERENCES `lecturer` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.question_import_logs
CREATE TABLE IF NOT EXISTS `question_import_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `assignment_id` bigint unsigned NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_rows` int unsigned NOT NULL DEFAULT '0',
  `processed_rows` int unsigned NOT NULL DEFAULT '0',
  `success_count` int unsigned NOT NULL DEFAULT '0',
  `error_count` int unsigned NOT NULL DEFAULT '0',
  `status` enum('processing','completed','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'processing',
  `error_details` json DEFAULT NULL COMMENT 'Chi tiết lỗi: [{row, error, data}]',
  `imported_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.reminders
CREATE TABLE IF NOT EXISTS `reminders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `user_type` enum('student','lecturer','admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reminder_type` enum('email','push','sms','in_app') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reminder_time` datetime NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','sent','failed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `sent_at` datetime DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reminders_user_id_user_type_index` (`user_id`,`user_type`),
  KEY `reminders_task_id_status_index` (`task_id`,`status`),
  KEY `reminders_reminder_time_status_index` (`reminder_time`,`status`),
  KEY `reminders_reminder_type_status_index` (`reminder_type`,`status`),
  KEY `reminders_created_at_index` (`created_at`),
  CONSTRAINT `reminders_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `task` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.roll_calls
CREATE TABLE IF NOT EXISTS `roll_calls` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `class_id` bigint unsigned DEFAULT NULL,
  `type` enum('class_based','manual') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'class_based',
  `expected_participants` int DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `date` datetime NOT NULL,
  `status` enum('active','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `roll_calls_created_by_foreign` (`created_by`),
  KEY `roll_calls_class_id_date_index` (`class_id`,`date`),
  KEY `roll_calls_status_index` (`status`),
  KEY `roll_calls_type_status_index` (`type`,`status`),
  CONSTRAINT `roll_calls_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `class` (`id`) ON DELETE CASCADE,
  CONSTRAINT `roll_calls_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `lecturer` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.roll_call_details
CREATE TABLE IF NOT EXISTS `roll_call_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `roll_call_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `status` enum('Có Mặt','Vắng Mặt','Có Phép','Muộn') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Có Mặt',
  `note` text COLLATE utf8mb4_unicode_ci,
  `checked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `roll_call_details_student_id_foreign` (`student_id`),
  KEY `roll_call_details_roll_call_id_student_id_index` (`roll_call_id`,`student_id`),
  KEY `roll_call_details_status_index` (`status`),
  CONSTRAINT `roll_call_details_roll_call_id_foreign` FOREIGN KEY (`roll_call_id`) REFERENCES `roll_calls` (`id`) ON DELETE CASCADE,
  CONSTRAINT `roll_call_details_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `student` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.semesters
CREATE TABLE IF NOT EXISTS `semesters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester_type` enum('1','2','3') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `semesters_code_unique` (`code`),
  KEY `semesters_academic_year_index` (`academic_year`),
  KEY `semesters_is_active_index` (`is_active`),
  KEY `semesters_start_date_end_date_index` (`start_date`,`end_date`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.student
CREATE TABLE IF NOT EXISTS `student` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` enum('male','female','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `enrolled_id` bigint unsigned DEFAULT NULL,
  `class_id` bigint unsigned DEFAULT NULL,
  `imported_at` timestamp NULL DEFAULT NULL,
  `import_job_id` bigint unsigned DEFAULT NULL,
  `account_status` enum('active','inactive','locked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_email_unique` (`email`),
  UNIQUE KEY `student_student_code_unique` (`student_code`),
  KEY `student_class_id_foreign` (`class_id`),
  KEY `student_import_job_id_index` (`import_job_id`),
  KEY `student_account_status_index` (`account_status`),
  KEY `student_deleted_at_index` (`deleted_at`),
  CONSTRAINT `student_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `class` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_import_job_id_foreign` FOREIGN KEY (`import_job_id`) REFERENCES `import_jobs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.student_account
CREATE TABLE IF NOT EXISTS `student_account` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_account_username_unique` (`username`),
  KEY `student_account_student_id_index` (`student_id`),
  CONSTRAINT `student_account_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `student` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.submission_questions
CREATE TABLE IF NOT EXISTS `submission_questions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `submission_id` bigint unsigned NOT NULL,
  `question_id` bigint unsigned NOT NULL,
  `order_index` int unsigned NOT NULL DEFAULT '0' COMMENT 'Thứ tự hiển thị câu hỏi',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `submission_question_unique` (`submission_id`,`question_id`),
  KEY `submission_questions_submission_id_index` (`submission_id`),
  KEY `submission_questions_question_id_index` (`question_id`),
  CONSTRAINT `submission_questions_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `submission_questions_submission_id_foreign` FOREIGN KEY (`submission_id`) REFERENCES `assignment_submissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.task
CREATE TABLE IF NOT EXISTS `task` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `receiver_id` bigint unsigned NOT NULL,
  `receiver_type` enum('lecturer','student') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `creator_id` bigint unsigned NOT NULL,
  `creator_type` enum('lecturer','student') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` enum('low','medium','high') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `due_date` date DEFAULT NULL,
  `deadline` datetime DEFAULT NULL,
  `status` enum('pending','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `task_receiver_type_receiver_id_index` (`receiver_type`,`receiver_id`),
  KEY `task_creator_type_creator_id_index` (`creator_type`,`creator_id`),
  KEY `task_creator_id_creator_type_index` (`creator_id`,`creator_type`),
  KEY `task_status_index` (`status`),
  KEY `task_due_date_index` (`due_date`),
  KEY `task_deadline_index` (`deadline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.task_file
CREATE TABLE IF NOT EXISTS `task_file` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint unsigned NOT NULL,
  `file_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `task_file_task_id_index` (`task_id`),
  CONSTRAINT `task_file_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `task` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.task_receivers
CREATE TABLE IF NOT EXISTS `task_receivers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint unsigned NOT NULL,
  `receiver_id` bigint unsigned NOT NULL,
  `receiver_type` enum('lecturer','student','all_students','all_lecturers','classes','department') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `task_receivers_receiver_type_receiver_id_index` (`receiver_type`,`receiver_id`),
  KEY `task_receivers_task_id_receiver_type_index` (`task_id`,`receiver_type`),
  KEY `task_receivers_receiver_id_receiver_type_task_id_index` (`receiver_id`,`receiver_type`,`task_id`),
  CONSTRAINT `task_receivers_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `task` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.task_submissions
CREATE TABLE IF NOT EXISTS `task_submissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `submission_content` text COLLATE utf8mb4_unicode_ci,
  `submission_files` json DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','submitted','graded','overdue') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `grade` decimal(5,2) DEFAULT NULL,
  `feedback` text COLLATE utf8mb4_unicode_ci,
  `graded_at` timestamp NULL DEFAULT NULL,
  `graded_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `task_submissions_graded_by_foreign` (`graded_by`),
  KEY `task_submissions_task_id_student_id_index` (`task_id`,`student_id`),
  KEY `task_submissions_student_id_status_index` (`student_id`,`status`),
  KEY `task_submissions_task_id_status_index` (`task_id`,`status`),
  KEY `task_submissions_submitted_at_index` (`submitted_at`),
  KEY `task_submissions_graded_at_index` (`graded_at`),
  CONSTRAINT `task_submissions_graded_by_foreign` FOREIGN KEY (`graded_by`) REFERENCES `lecturer` (`id`) ON DELETE SET NULL,
  CONSTRAINT `task_submissions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `student` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_submissions_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `task` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table system_services.user_notifications
CREATE TABLE IF NOT EXISTS `user_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `user_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notification_id` bigint unsigned NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  `email_sent` tinyint(1) NOT NULL DEFAULT '0',
  `email_sent_at` timestamp NULL DEFAULT NULL,
  `push_sent` tinyint(1) NOT NULL DEFAULT '0',
  `push_sent_at` timestamp NULL DEFAULT NULL,
  `sms_sent` tinyint(1) NOT NULL DEFAULT '0',
  `sms_sent_at` timestamp NULL DEFAULT NULL,
  `in_app_sent` tinyint(1) NOT NULL DEFAULT '0',
  `in_app_sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_notifications_user_id_user_type_index` (`user_id`,`user_type`),
  KEY `user_notifications_notification_id_index` (`notification_id`),
  KEY `user_notifications_is_read_index` (`is_read`),
  CONSTRAINT `user_notifications_notification_id_foreign` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
