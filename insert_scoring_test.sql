-- Disable FK checks to avoid issues during insert
SET FOREIGN_KEY_CHECKS=0;

-- ==========================================
-- 0. Cleanup Old Test Data
-- ==========================================
DELETE FROM assignments WHERE id = 101;
DELETE FROM assignment_submissions WHERE assignment_id IN (1, 2, 3, 101);
DELETE FROM exam_submissions WHERE exam_id = 1;
DELETE FROM exam_codes WHERE exam_id = 1;

-- ==========================================
-- 1. Create a QUIIZ Assignment for testing Auto-Score
-- ==========================================
INSERT INTO `assignments` (`id`, `course_id`, `lecturer_id`, `title`, `description`, `type`, `deadline`, `status`, `created_at`, `updated_at`) VALUES
(101, 1, 1, 'Bài kiểm tra trắc nghiệm C++ (Auto Score)', ' Kiểm tra kiến thức cơ bản', 'quiz', '2026-03-01 23:59:00', 'published', NOW(), NOW());

-- ==========================================
-- 2. Insert Assignment Submissions (Testing `score` attribute logic)
-- ==========================================

-- Case 1: Manual Score (Essay) - Score: 8.5
-- Student 1, Assignment 1
INSERT INTO `assignment_submissions` (`assignment_id`, `student_id`, `attempt`, `started_at`, `submitted_at`, `auto_score`, `manual_score`, `total_score`, `status`, `graded_by`, `graded_at`, `feedback`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NOW(), NOW(), NULL, 8.50, 8.50, 'graded', 1, NOW(), 'Bài làm tốt, cấu trúc rõ ràng.', NOW(), NOW());

-- Case 2: Auto Score (Quiz) - Score: 9.0
-- Student 1, Assignment 101
INSERT INTO `assignment_submissions` (`assignment_id`, `student_id`, `attempt`, `started_at`, `submitted_at`, `auto_score`, `manual_score`, `total_score`, `status`, `graded_by`, `graded_at`, `feedback`, `created_at`, `updated_at`) VALUES
(101, 1, 1, NOW(), NOW(), 9.00, NULL, 9.00, 'graded', NULL, NOW(), 'Kết quả trắc nghiệm tự động.', NOW(), NOW());

-- Case 3: In Progress (No Score)
-- Student 1, Assignment 2
INSERT INTO `assignment_submissions` (`assignment_id`, `student_id`, `attempt`, `started_at`, `submitted_at`, `auto_score`, `manual_score`, `total_score`, `status`, `graded_by`, `graded_at`, `feedback`, `created_at`, `updated_at`) VALUES
(2, 1, 1, NOW(), NULL, NULL, NULL, NULL, 'in_progress', NULL, NULL, NULL, NOW(), NOW());

-- Case 4: Submitted but NOT Graded (Pending)
-- Student 1, Assignment 3
INSERT INTO `assignment_submissions` (`assignment_id`, `student_id`, `attempt`, `started_at`, `submitted_at`, `auto_score`, `manual_score`, `total_score`, `status`, `graded_by`, `graded_at`, `feedback`, `created_at`, `updated_at`) VALUES
(3, 1, 1, NOW(), NOW(), NULL, NULL, NULL, 'submitted', NULL, NULL, NULL, NOW(), NOW());

-- Case 5: Low Score / Failed - Score: 3.5
-- Student 4, Assignment 1
INSERT INTO `assignment_submissions` (`assignment_id`, `student_id`, `attempt`, `started_at`, `submitted_at`, `auto_score`, `manual_score`, `total_score`, `status`, `graded_by`, `graded_at`, `feedback`, `created_at`, `updated_at`) VALUES
(1, 4, 1, NOW(), NOW(), NULL, 3.50, 3.50, 'graded', 1, NOW(), 'Cần cố gắng nhiều hơn.', NOW(), NOW());

-- ==========================================
-- 3. Insert Exam Submissions (Testing `score` attribute logic in ExamSubmission)
-- ==========================================

-- 3.0 Insert Exam Code (Required)
INSERT INTO `exam_codes` (`id`, `exam_id`, `code`, `question_order`, `option_shuffle_map`, `created_at`, `updated_at`) VALUES
(1, 1, 'TEST01', '[]', '[]', NOW(), NOW());

-- Case 6: Exam Completed - Score: 7.5
-- Used exam_code_id = 1
INSERT INTO `exam_submissions` (`exam_id`, `exam_code_id`, `student_id`, `attempt`, `started_at`, `submitted_at`, `total_score`, `correct_count`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, NOW(), NOW(), 7.50, 8, 'submitted', NOW(), NOW());

-- Re-enable FK checks
SET FOREIGN_KEY_CHECKS=1;

-- Output confirmation
SELECT 'Test data inserted successfully corresponding to standard Scoring Logic' as status;
