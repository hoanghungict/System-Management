<?php

namespace Modules\Task\app\Lecturer\UseCases;

use Modules\Task\app\Lecturer\DTOs\TaskFilterDTO;
use Modules\Task\app\Lecturer\Repositories\LecturerTaskRepository;
use Modules\Task\app\Lecturer\Exceptions\LecturerTaskException;

/**
 * Get Lecturer Tasks Use Case
 * 
 * Use Case để lấy danh sách tasks của giảng viên
 * Tuân theo Clean Architecture
 */
class GetLecturerTasksUseCase
{
    protected $lecturerTaskRepository;

    public function __construct(LecturerTaskRepository $lecturerTaskRepository)
    {
        $this->lecturerTaskRepository = $lecturerTaskRepository;
    }

    /**
     * Lấy tất cả tasks của giảng viên
     */
    public function execute($lecturerId, $filters = [])
    {
        try {
            $filterDTO = new TaskFilterDTO($filters);
            $tasks = $this->lecturerTaskRepository->getLecturerTasks($lecturerId, $filterDTO);
            
            return [
                'data' => $tasks['data'],
                'pagination' => $tasks['pagination']
            ];
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to retrieve lecturer tasks: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy tasks đã tạo bởi giảng viên
     */
    public function getCreatedTasks($lecturerId, $filters = [])
    {
        try {
            $filterDTO = new TaskFilterDTO($filters);
            $tasks = $this->lecturerTaskRepository->getCreatedTasks($lecturerId, $filterDTO);
            
            return [
                'data' => $tasks['data'],
                'pagination' => $tasks['pagination']
            ];
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to retrieve created tasks: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy tasks được giao cho giảng viên
     */
    public function getAssignedTasks($lecturerId, $filters = [])
    {
        try {
            $filterDTO = new TaskFilterDTO($filters);
            $tasks = $this->lecturerTaskRepository->getAssignedTasks($lecturerId, $filterDTO);
            
            return [
                'data' => $tasks['data'],
                'pagination' => $tasks['pagination']
            ];
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to retrieve assigned tasks: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy thống kê tasks của giảng viên
     */
    public function getLecturerStatistics($lecturerId)
    {
        try {
            $statistics = $this->lecturerTaskRepository->getLecturerStatistics($lecturerId);
            
            return $statistics;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to retrieve lecturer statistics: ' . $e->getMessage(), 500);
        }
    }
}
