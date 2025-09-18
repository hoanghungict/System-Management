<?php

namespace Modules\Task\app\Student\UseCases;

use Modules\Task\app\Student\Repositories\StudentClassRepository;
use Modules\Task\app\Student\Exceptions\StudentTaskException;

/**
 * Student Class Use Case
 */
class StudentClassUseCase
{
    protected $studentClassRepository;

    public function __construct(StudentClassRepository $studentClassRepository)
    {
        $this->studentClassRepository = $studentClassRepository;
    }

    public function getStudentClass($studentId)
    {
        try {
            $class = $this->studentClassRepository->getStudentClass($studentId);
            return $class;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve student class: ' . $e->getMessage(), 500);
        }
    }

    public function getClassmates($studentId, $filters = [])
    {
        try {
            $classmates = $this->studentClassRepository->getClassmates($studentId, $filters);
            return $classmates;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve classmates: ' . $e->getMessage(), 500);
        }
    }

    public function getClassLecturers($studentId)
    {
        try {
            $lecturers = $this->studentClassRepository->getClassLecturers($studentId);
            return $lecturers;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve class lecturers: ' . $e->getMessage(), 500);
        }
    }

    public function getClassAnnouncements($studentId, $filters = [])
    {
        try {
            $announcements = $this->studentClassRepository->getClassAnnouncements($studentId, $filters);
            return $announcements;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve class announcements: ' . $e->getMessage(), 500);
        }
    }

    public function getClassSchedule($studentId, $filters = [])
    {
        try {
            $schedule = $this->studentClassRepository->getClassSchedule($studentId, $filters);
            return $schedule;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve class schedule: ' . $e->getMessage(), 500);
        }
    }

    public function getAttendance($studentId, $filters = [])
    {
        try {
            $attendance = $this->studentClassRepository->getAttendance($studentId, $filters);
            return $attendance;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve student attendance: ' . $e->getMessage(), 500);
        }
    }
}
