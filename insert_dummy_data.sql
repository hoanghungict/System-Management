-- Disable FK checks
SET FOREIGN_KEY_CHECKS=0;

-- Truncate tables to clean old data
TRUNCATE TABLE `department`;
TRUNCATE TABLE `semesters`;
TRUNCATE TABLE `lecturer`;
TRUNCATE TABLE `lecturer_account`;
TRUNCATE TABLE `class`;
TRUNCATE TABLE `student`;
TRUNCATE TABLE `student_account`;
TRUNCATE TABLE `courses`;
TRUNCATE TABLE `course_enrollments`;
TRUNCATE TABLE `attendance_sessions`;
TRUNCATE TABLE `question_banks`;
TRUNCATE TABLE `questions`;
TRUNCATE TABLE `assignments`;
TRUNCATE TABLE `exams`;

-- 1. Departments
INSERT INTO `department` (`id`, `name`, `type`, `created_at`, `updated_at`) VALUES
(1, 'Khoa Công nghệ thông tin', 'faculty', NOW(), NOW()),
(2, 'Khoa Kinh tế', 'faculty', NOW(), NOW());

-- 2. Semesters (Updated for 2026)
INSERT INTO `semesters` (`id`, `name`, `code`, `academic_year`, `semester_type`, `start_date`, `end_date`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Học kỳ 2 Năm học 2025-2026', 'HK2-2526', '2025-2026', '2', '2026-01-15', '2026-06-15', 1, NOW(), NOW());

-- 3. Lecturers (5 GV)
INSERT INTO `lecturer` (`id`, `full_name`, `gender`, `email`, `lecturer_code`, `department_id`) VALUES
(1, 'Nguyen Van A', 'male', 'gv1@example.com', 'GV001', 1),
(2, 'Tran Thi B', 'female', 'gv2@example.com', 'GV002', 1),
(3, 'Le Van C', 'male', 'gv3@example.com', 'GV003', 1),
(4, 'Pham Thi D', 'female', 'gv4@example.com', 'GV004', 1),
(5, 'Hoang Van E', 'male', 'gv5@example.com', 'GV005', 1);

INSERT INTO `lecturer_account` (`lecturer_id`, `username`, `password`, `is_admin`, `created_at`, `updated_at`) VALUES
(1, 'gv1', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', 1, NOW(), NOW()), -- Admin GV
(2, 'gv2', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', 0, NOW(), NOW()),
(3, 'gv3', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', 0, NOW(), NOW()),
(4, 'gv4', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', 0, NOW(), NOW()),
(5, 'gv5', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', 0, NOW(), NOW());
-- Password: 123456

-- 4. Classes (8 Classes)
INSERT INTO `class` (`id`, `class_name`, `class_code`, `department_id`, `lecturer_id`, `school_year`, `created_at`, `updated_at`) VALUES
(1, 'Lớp CNTT 1', 'CNTT1', 1, 1, '2025-2029', NOW(), NOW()),
(2, 'Lớp CNTT 2', 'CNTT2', 1, 2, '2025-2029', NOW(), NOW()),
(3, 'Lớp CNTT 3', 'CNTT3', 1, 3, '2025-2029', NOW(), NOW()),
(4, 'Lớp CNTT 4', 'CNTT4', 1, 4, '2025-2029', NOW(), NOW()),
(5, 'Lớp KTPM 1', 'KTPM1', 1, 5, '2025-2029', NOW(), NOW()),
(6, 'Lớp KTPM 2', 'KTPM2', 1, 1, '2025-2029', NOW(), NOW()),
(7, 'Lớp HTTT 1', 'HTTT1', 1, 2, '2025-2029', NOW(), NOW()),
(8, 'Lớp HTTT 2', 'HTTT2', 1, 3, '2025-2029', NOW(), NOW());

-- 5. Students (30 SV)
INSERT INTO `student` (`id`, `full_name`, `gender`, `email`, `student_code`, `class_id`, `account_status`, `created_at`, `updated_at`) VALUES
(1, 'SV Mot', 'male', 'sv1@example.com', 'SV001', 1, 'active', NOW(), NOW()),
(2, 'SV Hai', 'female', 'sv2@example.com', 'SV002', 1, 'active', NOW(), NOW()),
(3, 'SV Ba', 'male', 'sv3@example.com', 'SV003', 1, 'active', NOW(), NOW()),
(4, 'SV Bon', 'female', 'sv4@example.com', 'SV004', 1, 'active', NOW(), NOW()),
(5, 'SV Nam', 'male', 'sv5@example.com', 'SV005', 2, 'active', NOW(), NOW()),
(6, 'SV Sau', 'female', 'sv6@example.com', 'SV006', 2, 'active', NOW(), NOW()),
(7, 'SV Bay', 'male', 'sv7@example.com', 'SV007', 2, 'active', NOW(), NOW()),
(8, 'SV Tam', 'female', 'sv8@example.com', 'SV008', 2, 'active', NOW(), NOW()),
(9, 'SV Chin', 'male', 'sv9@example.com', 'SV009', 3, 'active', NOW(), NOW()),
(10, 'SV Muoi', 'female', 'sv10@example.com', 'SV010', 3, 'active', NOW(), NOW()),
(11, 'SV Muoi Mot', 'male', 'sv11@example.com', 'SV011', 3, 'active', NOW(), NOW()),
(12, 'SV Muoi Hai', 'female', 'sv12@example.com', 'SV012', 3, 'active', NOW(), NOW()),
(13, 'SV Muoi Ba', 'male', 'sv13@example.com', 'SV013', 4, 'active', NOW(), NOW()),
(14, 'SV Muoi Bon', 'female', 'sv14@example.com', 'SV014', 4, 'active', NOW(), NOW()),
(15, 'SV Muoi Nam', 'male', 'sv15@example.com', 'SV015', 4, 'active', NOW(), NOW()),
(16, 'SV Muoi Sau', 'female', 'sv16@example.com', 'SV016', 4, 'active', NOW(), NOW()),
(17, 'SV Muoi Bay', 'male', 'sv17@example.com', 'SV017', 5, 'active', NOW(), NOW()),
(18, 'SV Muoi Tam', 'female', 'sv18@example.com', 'SV018', 5, 'active', NOW(), NOW()),
(19, 'SV Muoi Chin', 'male', 'sv19@example.com', 'SV019', 5, 'active', NOW(), NOW()),
(20, 'SV Hai Muoi', 'female', 'sv20@example.com', 'SV020', 5, 'active', NOW(), NOW()),
(21, 'SV Hai Mot', 'male', 'sv21@example.com', 'SV021', 6, 'active', NOW(), NOW()),
(22, 'SV Hai Hai', 'female', 'sv22@example.com', 'SV022', 6, 'active', NOW(), NOW()),
(23, 'SV Hai Ba', 'male', 'sv23@example.com', 'SV023', 6, 'active', NOW(), NOW()),
(24, 'SV Hai Bon', 'female', 'sv24@example.com', 'SV024', 6, 'active', NOW(), NOW()),
(25, 'SV Hai Nam', 'male', 'sv25@example.com', 'SV025', 7, 'active', NOW(), NOW()),
(26, 'SV Hai Sau', 'female', 'sv26@example.com', 'SV026', 7, 'active', NOW(), NOW()),
(27, 'SV Hai Bay', 'male', 'sv27@example.com', 'SV027', 7, 'active', NOW(), NOW()),
(28, 'SV Hai Tam', 'female', 'sv28@example.com', 'SV028', 8, 'active', NOW(), NOW()),
(29, 'SV Hai Chin', 'male', 'sv29@example.com', 'SV029', 8, 'active', NOW(), NOW()),
(30, 'SV Ba Muoi', 'female', 'sv30@example.com', 'SV030', 8, 'active', NOW(), NOW());

INSERT INTO `student_account` (`student_id`, `username`, `password`, `created_at`, `updated_at`) VALUES
(1, 'sv1', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW()),
(2, 'sv2', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW()),
(3, 'sv3', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW()),
(4, 'sv4', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW()),
(5, 'sv5', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW()),
(6, 'sv6', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW()),
(7, 'sv7', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW()),
(8, 'sv8', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW()),
(9, 'sv9', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW()),
(10, 'sv10', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW()),
(11, 'sv11', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW()),
(12, 'sv12', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW()),
(13, 'sv13', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW()),
(14, 'sv14', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW()),
(15, 'sv15', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW()),
(16, 'sv16', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW()),
(17, 'sv17', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW()),
(18, 'sv18', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW()),
(19, 'sv19', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW()),
(20, 'sv20', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW()),
(21, 'sv21', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW()),
(22, 'sv22', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW()),
(23, 'sv23', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW()),
(24, 'sv24', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW()),
(25, 'sv25', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW()),
(26, 'sv26', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW()),
(27, 'sv27', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW()),
(28, 'sv28', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW()),
(29, 'sv29', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW()),
(30, 'sv30', '$2y$12$QiRpnn1I.SR4c1lauSLyGOCny7LDaiJbrcuEvhRkTPx5TH0EE3oUW', NOW(), NOW());

-- 6. Courses (3 Môn thuộc CNTT)
INSERT INTO `courses` (`id`, `code`, `name`, `credits`, `semester_id`, `lecturer_id`, `department_id`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 'INT101', 'Lập trình C++ (T3-T5)', 3, 1, 1, 1, '2026-01-20', '2026-05-20', 'active', NOW(), NOW()),
(2, 'INT102', 'Cấu trúc dữ liệu (T4-T6)', 4, 1, 2, 1, '2026-01-20', '2026-05-20', 'active', NOW(), NOW()),
(3, 'INT103', 'Phát triển dứng dụng Web (T7)', 3, 1, 3, 1, '2026-01-20', '2026-05-20', 'active', NOW(), NOW());

-- 7. Course Enrollments (Phân phối SV)
INSERT INTO `course_enrollments` (`course_id`, `student_id`, `enrolled_at`, `status`, `created_at`, `updated_at`)
SELECT 1, id, NOW(), 'active', NOW(), NOW() FROM `student` WHERE id <= 20;

INSERT INTO `course_enrollments` (`course_id`, `student_id`, `enrolled_at`, `status`, `created_at`, `updated_at`)
SELECT 2, id, NOW(), 'active', NOW(), NOW() FROM `student` WHERE id > 10;

INSERT INTO `course_enrollments` (`course_id`, `student_id`, `enrolled_at`, `status`, `created_at`, `updated_at`)
SELECT 3, id, NOW(), 'active', NOW(), NOW() FROM `student` WHERE id <= 10 OR id > 20;

-- 8. Attendance Sessions (Lịch học - Updated dates for current week of 2026-02-01)
-- Week 5: 26/01 -> 01/02
-- Course 1: Tue (27/01), Thu (29/01)
-- Course 2: Wed (28/01), Fri (30/01)
-- Course 3: Sat (31/01)

INSERT INTO `attendance_sessions` (`course_id`, `session_number`, `session_date`, `day_of_week`, `start_time`, `end_time`, `room`, `status`, `created_at`, `updated_at`) VALUES
-- Course 1
(1, 1, '2026-01-20', 3, '07:00:00', '09:00:00', 'P101', 'completed', NOW(), NOW()),
(1, 2, '2026-01-22', 5, '07:00:00', '09:00:00', 'P101', 'completed', NOW(), NOW()),
(1, 3, '2026-01-27', 3, '07:00:00', '09:00:00', 'P101', 'scheduled', NOW(), NOW()), -- Tue
(1, 4, '2026-01-29', 5, '07:00:00', '09:00:00', 'P101', 'scheduled', NOW(), NOW()), -- Thu
(1, 5, '2026-02-03', 3, '07:00:00', '09:00:00', 'P101', 'scheduled', NOW(), NOW()),

-- Course 2
(2, 1, '2026-01-21', 4, '09:00:00', '11:00:00', 'Lab 2', 'completed', NOW(), NOW()),
(2, 2, '2026-01-23', 6, '09:00:00', '11:00:00', 'Lab 2', 'completed', NOW(), NOW()),
(2, 3, '2026-01-28', 4, '09:00:00', '11:00:00', 'Lab 2', 'scheduled', NOW(), NOW()), -- Wed
(2, 4, '2026-01-30', 6, '09:00:00', '11:00:00', 'Lab 2', 'scheduled', NOW(), NOW()), -- Fri

-- Course 3
(3, 1, '2026-01-24', 7, '13:00:00', '15:00:00', 'P202', 'completed', NOW(), NOW()),
(3, 2, '2026-01-31', 7, '13:00:00', '15:00:00', 'P202', 'scheduled', NOW(), NOW()), -- Sat
(3, 3, '2026-02-07', 7, '13:00:00', '15:00:00', 'P202', 'scheduled', NOW(), NOW());

-- 9. Question Banks & Questions
INSERT INTO `question_banks` (`id`, `course_id`, `lecturer_id`, `name`, `subject_code`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Ngân hàng C++', 'INT101', 'active', NOW(), NOW()),
(2, 2, 2, 'Ngân hàng CTDL', 'INT102', 'active', NOW(), NOW()),
(3, 3, 3, 'Ngân hàng Web', 'INT103', 'active', NOW(), NOW());

INSERT INTO `questions` (`question_bank_id`, `type`, `difficulty`, `content`, `options`, `correct_answer`, `points`, `created_at`, `updated_at`) VALUES
(1, 'multiple_choice', 'easy', 'C++ là ngôn ngữ?', '[{"key":"A","text":"Lập trình"},{"key":"B","text":"Nấu ăn"}]', 'A', 1, NOW(), NOW()),
(1, 'multiple_choice', 'easy', 'int main() trả về?', '[{"key":"A","text":"int"},{"key":"B","text":"void"}]', 'A', 1, NOW(), NOW()),
(1, 'multiple_choice', 'medium', 'Con trỏ dùng để?', '[{"key":"A","text":"Lưu địa chỉ"},{"key":"B","text":"Lưu giá trị"}]', 'A', 1, NOW(), NOW()),
(2, 'multiple_choice', 'easy', 'Stack là gì?', '[{"key":"A","text":"LIFO"},{"key":"B","text":"FIFO"}]', 'A', 1, NOW(), NOW()),
(3, 'multiple_choice', 'easy', 'React là gì?', '[{"key":"A","text":"Library"},{"key":"B","text":"Framework"}]', 'A', 1, NOW(), NOW());

-- 10. Assignments
INSERT INTO `assignments` (`course_id`, `lecturer_id`, `title`, `description`, `type`, `deadline`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Bài tập C++ số 1', 'Viết chương trình Hello World', 'essay', '2026-02-05 23:59:00', 'published', NOW(), NOW()),
(1, 1, 'Bài tập C++ số 2', 'Vòng lặp for', 'essay', '2026-02-15 23:59:00', 'published', NOW(), NOW()),
(2, 2, 'Bài tập CTDL Tuần 1', 'Cài đặt DSLK', 'essay', '2026-02-10 23:59:00', 'published', NOW(), NOW()),
(3, 3, 'Thiết kế giao diện', 'HTML/CSS cơ bản', 'essay', '2026-02-08 23:59:00', 'published', NOW(), NOW());

-- 11. Exams
INSERT INTO `exams` (`question_bank_id`, `course_id`, `lecturer_id`, `title`, `time_limit`, `total_questions`, `max_attempts`, `status`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Kiểm tra 15p C++', 15, 10, 1, 'published', '2026-01-20 00:00:00', '2026-02-20 23:59:00', NOW(), NOW()),
(3, 3, 3, 'Giữa kỳ Web', 60, 40, 1, 'draft', NULL, NULL, NOW(), NOW());

SET FOREIGN_KEY_CHECKS=1;
