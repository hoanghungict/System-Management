<?php

namespace Modules\Task\app\Student\UseCases;

use Modules\Task\app\Student\DTOs\StudentTaskFilterDTO;
use Modules\Task\app\Student\Repositories\StudentTaskRepository;
use Modules\Task\app\Student\Exceptions\StudentTaskException;

/**
 * Get Student Tasks Use Case
 * 
 * Use Case để lấy danh sách tasks của sinh viên
 * Tuân theo Clean Architecture
 */
class GetStudentTasksUseCase
{
    protected $studentTaskRepository;

    public function __construct(StudentTaskRepository $studentTaskRepository)
    {
        $this->studentTaskRepository = $studentTaskRepository;
    }

    /**
     * Lấy tất cả tasks được giao cho sinh viên
     */
    public function execute($studentId, $filters = [])
    {
        try {
            $filterDTO = new StudentTaskFilterDTO($filters);
            $tasks = $this->studentTaskRepository->getStudentTasks($studentId, $filterDTO);
            
            return [
                'data' => $tasks['data'],
                'pagination' => $tasks['pagination']
            ];
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve student tasks: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy tasks đang chờ submit
     */
    public function getPendingTasks($studentId, $filters = [])
    {
        try {
            $filterDTO = new StudentTaskFilterDTO($filters);
            $tasks = $this->studentTaskRepository->getPendingTasks($studentId, $filterDTO);
            
            return [
                'data' => $tasks['data'],
                'pagination' => $tasks['pagination']
            ];
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve pending tasks: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy tasks đã submit
     */
    public function getSubmittedTasks($studentId, $filters = [])
    {
        try {
            $filterDTO = new StudentTaskFilterDTO($filters);
            $tasks = $this->studentTaskRepository->getSubmittedTasks($studentId, $filterDTO);
            
            return [
                'data' => $tasks['data'],
                'pagination' => $tasks['pagination']
            ];
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve submitted tasks: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy tasks quá hạn
     */
    public function getOverdueTasks($studentId, $filters = [])
    {
        try {
            $filterDTO = new StudentTaskFilterDTO($filters);
            $tasks = $this->studentTaskRepository->getOverdueTasks($studentId, $filterDTO);
            
            return [
                'data' => $tasks['data'],
                'pagination' => $tasks['pagination']
            ];
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve overdue tasks: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy tasks theo priority
     */
    public function getTasksByPriority($studentId, $priority, $filters = [])
    {
        try {
            $filterDTO = new StudentTaskFilterDTO($filters);
            $filterDTO->priority = $priority;
            $tasks = $this->studentTaskRepository->getStudentTasks($studentId, $filterDTO);
            
            return [
                'data' => $tasks['data'],
                'pagination' => $tasks['pagination']
            ];
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve tasks by priority: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy tasks theo class
     */
    public function getTasksByClass($studentId, $classId, $filters = [])
    {
        try {
            $filterDTO = new StudentTaskFilterDTO($filters);
            $filterDTO->class_id = $classId;
            $tasks = $this->studentTaskRepository->getStudentTasks($studentId, $filterDTO);
            
            return [
                'data' => $tasks['data'],
                'pagination' => $tasks['pagination']
            ];
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve tasks by class: ' . $e->getMessage(), 500);
        }
    }
}
