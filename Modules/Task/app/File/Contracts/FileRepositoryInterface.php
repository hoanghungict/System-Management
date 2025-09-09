<?php

namespace Modules\Task\app\File\Contracts;

use Modules\Task\app\Models\Task;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface cho File Repository
 * 
 * @package Modules\Task\app\File\Contracts
 */
interface FileRepositoryInterface
{
    /**
     * Lấy tất cả files của task
     * 
     * @param Task $task
     * @return Collection
     */
    public function getTaskFiles(Task $task): Collection;

    /**
     * Lưu file vào task
     * 
     * @param Task $task
     * @param array $fileData
     * @return mixed
     */
    public function saveTaskFile(Task $task, array $fileData);

    /**
     * Xóa file khỏi task
     * 
     * @param int $fileId
     * @return bool
     */
    public function deleteTaskFile(int $fileId): bool;

    /**
     * Kiểm tra user có quyền truy cập file không
     * 
     * @param int $fileId
     * @param int $userId
     * @param string $userType
     * @return bool
     */
    public function canAccessFile(int $fileId, int $userId, string $userType): bool;

    /**
     * Lấy file theo ID
     * 
     * @param int $fileId
     * @return mixed
     */
    public function getFileById(int $fileId);
}
