<?php

namespace Modules\Task\app\Lecturer\UseCases;

use Modules\Task\app\Lecturer\Repositories\LecturerClassRepository;
use Modules\Task\app\Lecturer\Exceptions\LecturerTaskException;

/**
 * Lecturer Class Use Case
 */
class LecturerClassUseCase
{
    protected $lecturerClassRepository;

    public function __construct(LecturerClassRepository $lecturerClassRepository)
    {
        $this->lecturerClassRepository = $lecturerClassRepository;
    }

    public function getLecturerClasses($lecturerId, $filters = [])
    {
        try {
            $classes = $this->lecturerClassRepository->getLecturerClasses($lecturerId, $filters);
            return $classes;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to retrieve lecturer classes: ' . $e->getMessage(), 500);
        }
    }

    public function getClassStudents($classId, $lecturerId, $filters = [])
    {
        try {
            $students = $this->lecturerClassRepository->getClassStudents($classId, $lecturerId, $filters);
            return $students;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to retrieve class students: ' . $e->getMessage(), 500);
        }
    }

    public function createAnnouncement($data)
    {
        try {
            $announcement = $this->lecturerClassRepository->createAnnouncement($data);
            return $announcement;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to create announcement: ' . $e->getMessage(), 500);
        }
    }
}
