-- Cập nhật các bài tập hiện có
UPDATE assignments SET grade_column = 'ĐK1' WHERE grade_column = 'GK';
UPDATE assignments SET title = 'Kiểm tra định kỳ 1', grade_column = 'ĐK1' WHERE grade_column = 'GK' OR title LIKE '%giữa kỳ%';

-- Tạo thêm bài tập cho ĐK2 nếu chưa có (Dành cho Lớp 1 - Course 1)
INSERT INTO assignments (course_id, lecturer_id, title, description, deadline, grade_column, created_at, updated_at)
SELECT 1, lecturer_id, 'Kiểm tra định kỳ 2', 'Bài kiểm tra định kỳ số 2', deadline, 'ĐK2', NOW(), NOW()
FROM assignments 
WHERE grade_column = 'ĐK1' AND course_id = 1
LIMIT 1;
