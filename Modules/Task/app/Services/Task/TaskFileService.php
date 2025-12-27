<?php

namespace Modules\Task\app\Services\Task;

use Modules\Task\app\Models\Task;
use Modules\Task\app\Repositories\Interfaces\TaskRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * Service xử lý logic upload/delete files cho task
 */
class TaskFileService
{
    protected $taskRepository;

    public function __construct(TaskRepositoryInterface $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    /**
     * Upload files cho task
     */
    public function uploadTaskFiles(Task $task, array $files): array
    {
        $uploadedFiles = [];
        
        foreach ($files as $file) {
            $fileData = [
                'task_id' => $task->id,
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'path' => $file->store("task-files/{$task->id}", 'public'),
            ];
            
            $uploadedFile = $this->taskRepository->createTaskFile($fileData);
            $uploadedFiles[] = $uploadedFile;
        }

        Log::info('Task files uploaded', [
            'task_id' => $task->id,
            'files_count' => count($uploadedFiles)
        ]);
        
        return $uploadedFiles;
    }

    /**
     * Xóa file của task
     */
    public function deleteTaskFile(Task $task, int $fileId): bool
    {
        $file = $this->taskRepository->findTaskFile($fileId);
        
        if ($file && $file->path) {
            Storage::disk('public')->delete($file->path);
        }
        
        return $this->taskRepository->deleteTaskFile($fileId);
    }

    /**
     * Kiểm tra quyền upload files
     */
    public function canUploadFiles($user, Task $task): bool
    {
        if (!$user || !isset($user->id)) {
            return false;
        }

        $userType = $user->user_type ?? 'unknown';

        // Admin luôn có quyền
        if ($userType === 'admin') {
            return true;
        }

        // Creator có quyền
        if ($task->creator_id === $user->id && $task->creator_type === $userType) {
            return true;
        }

        // Receiver có quyền
        if ($task->receivers) {
            foreach ($task->receivers as $receiver) {
                if ($receiver->receiver_id == $user->id && $receiver->receiver_type == $userType) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Kiểm tra quyền xóa file
     */
    public function canDeleteFile($user, Task $task, int $fileId): bool
    {
        $file = $this->taskRepository->findTaskFile($fileId);
        
        if (!$file) {
            return false;
        }

        $userType = $user->user_type ?? 'unknown';

        // Admin có thể xóa mọi file
        if ($userType === 'admin') {
            return true;
        }

        // Creator có thể xóa file của task họ tạo
        if ($task->creator_id === $user->id && $task->creator_type === $userType) {
            return true;
        }

        return false;
    }
}
