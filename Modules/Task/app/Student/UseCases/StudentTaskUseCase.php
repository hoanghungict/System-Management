<?php

namespace Modules\Task\app\Student\UseCases;

use Modules\Task\app\Student\Repositories\StudentTaskRepository;
use Modules\Task\app\Student\Exceptions\StudentTaskException;

/**
 * Student Task Use Case
 * 
 * Use Case chính để quản lý tasks của sinh viên
 * Tuân theo Clean Architecture
 */
class StudentTaskUseCase
{
    protected $studentTaskRepository;

    public function __construct(StudentTaskRepository $studentTaskRepository)
    {
        $this->studentTaskRepository = $studentTaskRepository;
    }

    /**
     * Lấy task theo ID
     */
    public function getTaskById($taskId, $studentId, $userType)
    {
        try {
            $task = $this->studentTaskRepository->getTaskById($taskId, $studentId, $userType);
            if (!$task) {
                throw StudentTaskException::taskNotFound($taskId);
            }

            return $task;
        } catch (StudentTaskException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve task: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy task submission
     */
    public function getTaskSubmission($taskId, $studentId)
    {
        try {
            $submission = $this->studentTaskRepository->getTaskSubmission($taskId, $studentId);
            if (!$submission) {
                throw StudentTaskException::submissionNotFound($taskId, $studentId);
            }

            return $submission;
        } catch (StudentTaskException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve task submission: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Kiểm tra quyền truy cập task
     */
    public function checkTaskAccess($taskId, $studentId)
    {
        try {
            $task = $this->studentTaskRepository->getTaskById($taskId, $studentId, 'student');
            return $task !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Lấy task files
     */
    public function getTaskFiles($taskId, $studentId)
    {
        try {
            // Kiểm tra quyền truy cập
            if (!$this->checkTaskAccess($taskId, $studentId)) {
                throw StudentTaskException::accessDenied('view task files');
            }

            $files = $this->studentTaskRepository->getTaskFiles($taskId, $studentId);
            return $files;
        } catch (StudentTaskException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve task files: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Upload file cho task
     */
    public function uploadTaskFile($taskId, $file, $studentId)
    {
        try {
            // Kiểm tra quyền truy cập
            if (!$this->checkTaskAccess($taskId, $studentId)) {
                throw StudentTaskException::accessDenied('upload files to task');
            }

            // Kiểm tra task chưa submit
            $submission = $this->studentTaskRepository->getTaskSubmission($taskId, $studentId);
            if ($submission) {
                throw StudentTaskException::taskAlreadySubmitted($taskId);
            }

            $uploadedFile = $this->studentTaskRepository->uploadTaskFile($taskId, $file, $studentId);
            return $uploadedFile;
        } catch (StudentTaskException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw StudentTaskException::fileUploadFailed($file->getClientOriginalName());
        }
    }

    /**
     * Xóa file của task
     */
    public function deleteTaskFile($taskId, $fileId, $studentId)
    {
        try {
            // Kiểm tra quyền truy cập
            if (!$this->checkTaskAccess($taskId, $studentId)) {
                throw StudentTaskException::accessDenied('delete task files');
            }

            // Kiểm tra file thuộc về student
            $file = $this->studentTaskRepository->getTaskFile($fileId, $studentId);
            if (!$file) {
                throw StudentTaskException::fileNotFound($fileId);
            }

            $this->studentTaskRepository->deleteTaskFile($fileId, $studentId);
            return true;
        } catch (StudentTaskException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to delete task file: ' . $e->getMessage(), 500);
        }
    }
}
