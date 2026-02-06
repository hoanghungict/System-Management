-- Disable FK checks
SET FOREIGN_KEY_CHECKS=0;

-- Update existing assignments to have a grade column
UPDATE assignments SET grade_column = 'TX1' WHERE id = 1;
UPDATE assignments SET grade_column = 'GK' WHERE id = 101; 
UPDATE assignments SET grade_column = 'DK1' WHERE id = 2;
UPDATE assignments SET grade_column = 'DK2' WHERE id = 3;

-- Make sure we have a student linked to the logged in user (assuming user id 1 is linked to student 1)
-- Check student_account table.
-- If user is testing with 'sv1', that is student_id 1.

-- Re-enable FK checks
SET FOREIGN_KEY_CHECKS=1;

SELECT 'Updated grade_column for test assignments' as status;
