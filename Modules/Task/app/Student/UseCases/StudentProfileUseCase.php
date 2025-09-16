<?php

namespace Modules\Task\app\Student\UseCases;

use Modules\Task\app\Student\DTOs\StudentProfileDTO;
use Modules\Task\app\Student\Repositories\StudentProfileRepository;
use Modules\Task\app\Student\Exceptions\StudentTaskException;

/**
 * Student Profile Use Case
 */
class StudentProfileUseCase
{
    protected $studentProfileRepository;

    public function __construct(StudentProfileRepository $studentProfileRepository)
    {
        $this->studentProfileRepository = $studentProfileRepository;
    }

    public function getProfile($studentId)
    {
        try {
            $profile = $this->studentProfileRepository->getProfile($studentId);
            return $profile;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve student profile: ' . $e->getMessage(), 500);
        }
    }

    public function updateProfile($studentId, $data)
    {
        try {
            // Thêm student_id vào data từ JWT token
            $data['student_id'] = $studentId;
            
            $profileDTO = new StudentProfileDTO($data);
            $errors = $profileDTO->validate();
            if (!empty($errors)) {
                throw StudentTaskException::validationFailed($errors);
            }

            $profile = $this->studentProfileRepository->updateProfile($studentId, $profileDTO);
            return $profile;
        } catch (StudentTaskException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to update student profile: ' . $e->getMessage(), 500);
        }
    }

    public function getClassInfo($studentId)
    {
        try {
            $classInfo = $this->studentProfileRepository->getClassInfo($studentId);
            return $classInfo;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve class information: ' . $e->getMessage(), 500);
        }
    }

    public function getGrades($studentId)
    {
        try {
            $grades = $this->studentProfileRepository->getGrades($studentId);
            return $grades;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve student grades: ' . $e->getMessage(), 500);
        }
    }
}
