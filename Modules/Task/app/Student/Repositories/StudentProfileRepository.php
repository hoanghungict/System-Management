<?php

namespace Modules\Task\app\Student\Repositories;

use Modules\Task\app\Student\DTOs\StudentProfileDTO;
use Modules\Task\app\Student\Exceptions\StudentTaskException;

/**
 * Student Profile Repository
 */
class StudentProfileRepository
{
    public function getProfile($studentId)
    {
        try {
            // Giả sử có logic lấy profile từ database
            return [
                'student_id' => $studentId,
                'full_name' => 'Student Name',
                'email' => 'student@university.edu.vn',
                'phone' => '0123456789',
                'address' => 'Student Address',
                'class_id' => 1,
                'student_code' => 'SV001',
                'date_of_birth' => '2000-01-01',
                'avatar' => null,
                'bio' => 'Student bio'
            ];
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve student profile: ' . $e->getMessage(), 500);
        }
    }

    public function updateProfile($studentId, StudentProfileDTO $profileDTO)
    {
        try {
            // Giả sử có logic cập nhật profile
            return $profileDTO->toArray();
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to update student profile: ' . $e->getMessage(), 500);
        }
    }

    public function getClassInfo($studentId)
    {
        try {
            return [
                'class_id' => 1,
                'class_name' => 'Class A',
                'department' => 'Computer Science',
                'faculty' => 'Engineering',
                'semester' => 'Fall 2024'
            ];
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve class information: ' . $e->getMessage(), 500);
        }
    }

    public function getGrades($studentId)
    {
        try {
            return [
                'total_subjects' => 5,
                'average_grade' => 8.5,
                'grades' => [
                    ['subject' => 'Math', 'grade' => 9.0],
                    ['subject' => 'Physics', 'grade' => 8.0],
                    ['subject' => 'Chemistry', 'grade' => 8.5],
                    ['subject' => 'English', 'grade' => 9.5],
                    ['subject' => 'Computer Science', 'grade' => 7.5]
                ]
            ];
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve student grades: ' . $e->getMessage(), 500);
        }
    }
}
