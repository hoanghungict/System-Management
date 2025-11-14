<?php

namespace Modules\Task\app\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FileService
{
    /**
     * Xử lý upload file
     *
     * @param array $fileData
     * @return array
     */
    public function processUpload(array $fileData): array
    {
        try {
            Log::info('FileService: Processing upload', $fileData);
            
            // Simulate file processing
            $processedData = [
                'original_name' => $fileData['name'],
                'processed_name' => 'processed_' . $fileData['name'],
                'size' => $fileData['size'],
                'mime_type' => $fileData['mime_type'],
                'path' => $fileData['path'],
                'status' => 'processed'
            ];
            
            Log::info('FileService: Upload processed successfully', $processedData);
            return $processedData;
        } catch (\Exception $e) {
            Log::error('FileService: Upload processing failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Nén file
     *
     * @param string $filePath
     * @return string
     */
    public function compressFile(string $filePath): string
    {
        try {
            Log::info('FileService: Compressing file', ['path' => $filePath]);
            
            // Simulate compression
            $compressedPath = $filePath . '.compressed';
            
            Log::info('FileService: File compressed successfully', ['compressed_path' => $compressedPath]);
            return $compressedPath;
        } catch (\Exception $e) {
            Log::error('FileService: File compression failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Kiểm tra quyền upload files cho task
     *
     * @param mixed $task
     * @param mixed $user
     * @return bool
     */
    public function canUserUploadFiles($task, $user): bool
    {
        try {
            Log::info('FileService: Checking upload permission', [
                'task_id' => $task->id ?? null,
                'user_id' => $user->id ?? null,
                'user_type' => $user->user_type ?? null
            ]);
            
            // Admin có thể upload files cho mọi task
            if ($user->user_type === 'lecturer' && $user->id === 1) {
                return true;
            }
            
            // Người nhận task có thể upload files
            if ($task && $task->receivers) {
                foreach ($task->receivers as $receiver) {
                    if ($receiver->receiver_id == $user->id && $receiver->receiver_type == $user->user_type) {
                        return true;
                    }
                }
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('FileService: Permission check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Upload files to task
     *
     * @param mixed $task
     * @param array $files
     * @param mixed $user
     * @return array
     */
    public function uploadFilesToTask($task, array $files, $user): array
    {
        try {
            Log::info('FileService: Uploading files to task', [
                'task_id' => $task->id ?? null,
                'task_type' => get_class($task),
                'files_count' => count($files),
                'user_id' => $user->id ?? null
            ]);
            $uploadedFiles = [];
            foreach ($files as $file) {
                $savePath = $file->store("task-files/{$task->id}", 'public');
                $taskFile = \Modules\Task\app\Models\TaskFile::create([
                    'task_id'   => $task->id,
                    'name'      => $file->getClientOriginalName(), // Tên file gốc
                    'path'      => $savePath,                      // Đường dẫn trong storage
                    'size'      => $file->getSize(),               // Kích thước file
                ]);
                $uploadedFiles[] = [
                    'id'         => $taskFile->id,
                    'file_name'  => $taskFile->name,
                    'file_url'   => $taskFile->file_url,
                    'created_at' => $taskFile->created_at?->toDateTimeString(),
                ];
            }
            Log::info('FileService: Files uploaded successfully', [
                'task_id' => $task->id,
                'uploaded_count' => count($uploadedFiles)
            ]);
            return [
                'files' => $uploadedFiles,
                'count' => count($uploadedFiles)
            ];
        } catch (\Exception $e) {
            Log::error('FileService: Upload failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Chuyển đổi file
     *
     * @param string $filePath
     * @param string $targetFormat
     * @return string
     */
    public function convertFile(string $filePath, string $targetFormat): string
    {
        try {
            Log::info('FileService: Converting file', ['path' => $filePath, 'format' => $targetFormat]);
            
            // Simulate conversion
            $convertedPath = str_replace('.', '_converted.', $filePath) . '.' . $targetFormat;
            
            Log::info('FileService: File converted successfully', ['converted_path' => $convertedPath]);
            return $convertedPath;
        } catch (\Exception $e) {
            Log::error('FileService: File conversion failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Validate file
     *
     * @param array $fileData
     * @return bool
     */
    public function validateFile(array $fileData): bool
    {
        try {
            Log::info('FileService: Validating file', $fileData);
            
            // Simulate validation
            $isValid = !empty($fileData['name']) && $fileData['size'] > 0;
            
            Log::info('FileService: File validation result', ['is_valid' => $isValid]);
            return $isValid;
        } catch (\Exception $e) {
            Log::error('FileService: File validation failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Quét virus
     *
     * @param string $filePath
     * @return bool
     */
    public function scanVirus(string $filePath): bool
    {
        try {
            Log::info('FileService: Scanning file for viruses', ['path' => $filePath]);
            
            // Simulate virus scan
            $isClean = true; // Assume clean for demo
            
            Log::info('FileService: Virus scan completed', ['is_clean' => $isClean]);
            return $isClean;
        } catch (\Exception $e) {
            Log::error('FileService: Virus scan failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Trích xuất metadata
     *
     * @param string $filePath
     * @return array
     */
    public function extractMetadata(string $filePath): array
    {
        try {
            Log::info('FileService: Extracting metadata', ['path' => $filePath]);
            
            // Simulate metadata extraction
            $metadata = [
                'file_size' => filesize($filePath),
                'created_at' => now(),
                'modified_at' => now(),
                'file_type' => pathinfo($filePath, PATHINFO_EXTENSION)
            ];
            
            Log::info('FileService: Metadata extracted successfully', $metadata);
            return $metadata;
        } catch (\Exception $e) {
            Log::error('FileService: Metadata extraction failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Tạo thumbnail
     *
     * @param string $filePath
     * @return string
     */
    public function generateThumbnail(string $filePath): string
    {
        try {
            Log::info('FileService: Generating thumbnail', ['path' => $filePath]);
            
            // Simulate thumbnail generation
            $thumbnailPath = str_replace('.', '_thumb.', $filePath);
            
            Log::info('FileService: Thumbnail generated successfully', ['thumbnail_path' => $thumbnailPath]);
            return $thumbnailPath;
        } catch (\Exception $e) {
            Log::error('FileService: Thumbnail generation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Backup file
     *
     * @param string $filePath
     * @return string
     */
    /**
     * Xóa file từ task
     */
    public function deleteFile(int $fileId, object $user): bool
    {
        try {
            Log::info('FileService: Deleting file', ['file_id' => $fileId, 'user_id' => $user->id]);
            
            // Find file record
            $file = \Modules\Task\app\Models\TaskFile::find($fileId);
            
            if (!$file) {
                Log::warning('FileService: File not found', ['file_id' => $fileId]);
                return false;
            }
            
            // Check permissions
            if (!$this->canUserDeleteFile($file, $user)) {
                Log::warning('FileService: User not authorized to delete file', [
                    'file_id' => $fileId, 
                    'user_id' => $user->id
                ]);
                return false;
            }
            
            // Delete physical file
            if ($file->path && Storage::disk('public')->exists($file->path)) {
                Storage::disk('public')->delete($file->path);
            }
            
            // Delete database record
            $file->delete();
            
            Log::info('FileService: File deleted successfully', ['file_id' => $fileId]);
            return true;
            
        } catch (\Exception $e) {
            Log::error('FileService: Error deleting file', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Kiểm tra quyền xóa file
     * 
     * @param object $file TaskFile instance
     * @param object $user User object với id và user_type
     * @return bool
     */
    public function canUserDeleteFile($file, $user): bool
    {
        // Admin có thể xóa mọi file
        if ($user->user_type === 'admin') {
            return true;
        }
        
        // Giảng viên có thể xóa file của task họ tạo
        if ($user->user_type === 'lecturer') {
            return $file->task->creator_id === $user->id && $file->task->creator_type === 'lecturer';
        }
        
        // Sinh viên có thể xóa file của task họ được assign
        // Vì không có uploaded_by trong schema, kiểm tra qua task receivers
        if ($user->user_type === 'student') {
            $task = $file->task;
            if ($task && $task->receivers) {
                foreach ($task->receivers as $receiver) {
                    if ($receiver->receiver_id == $user->id && $receiver->receiver_type == 'student') {
                        return true;
                    }
                }
            }
            return false;
        }
        
        return false;
    }

    /**
     * Kiểm tra quyền download file
     * 
     * Download permissions rộng hơn delete permissions:
     * - Admin có thể download mọi file
     * - Giảng viên có thể download file của task họ tạo HOẶC task họ được assign
     * - Sinh viên có thể download file của task họ được assign
     * 
     * @param object $file TaskFile instance
     * @param object $user User object với id và user_type
     * @return bool
     */
    public function canUserDownloadFile($file, $user): bool
    {
        try {
            Log::info('FileService: Checking download permission', [
                'file_id' => $file->id ?? null,
                'task_id' => $file->task_id ?? null,
                'user_id' => $user->id ?? null,
                'user_type' => $user->user_type ?? null
            ]);

            // Admin có thể download mọi file
            if ($user->user_type === 'admin') {
                Log::info('FileService: Download allowed - Admin user');
                return true;
            }
            
            // Load task with receivers
            $task = $file->task;
            if (!$task) {
                Log::warning('FileService: Download denied - Task not found', [
                    'file_id' => $file->id,
                    'task_id' => $file->task_id
                ]);
                return false;
            }

            // Giảng viên có thể download file của:
            // 1. Task họ tạo (creator)
            // 2. Task họ được assign (receiver)
            if ($user->user_type === 'lecturer') {
                // Check if lecturer is creator
                if ($task->creator_id === $user->id && $task->creator_type === 'lecturer') {
                    Log::info('FileService: Download allowed - Lecturer is creator');
                    return true;
                }

                // Check if lecturer is receiver
                if ($task->receivers) {
                    foreach ($task->receivers as $receiver) {
                        if ($receiver->receiver_id == $user->id && $receiver->receiver_type == 'lecturer') {
                            Log::info('FileService: Download allowed - Lecturer is receiver');
                            return true;
                        }
                    }
                }

                Log::warning('FileService: Download denied - Lecturer not creator or receiver', [
                    'user_id' => $user->id,
                    'task_creator_id' => $task->creator_id,
                    'task_creator_type' => $task->creator_type
                ]);
                return false;
            }
            
            // Sinh viên có thể download file của task họ được assign
            if ($user->user_type === 'student') {
                if ($task->receivers) {
                    foreach ($task->receivers as $receiver) {
                        if ($receiver->receiver_id == $user->id && $receiver->receiver_type == 'student') {
                            Log::info('FileService: Download allowed - Student is receiver');
                            return true;
                        }
                    }
                }

                Log::warning('FileService: Download denied - Student not receiver', [
                    'user_id' => $user->id,
                    'task_id' => $task->id
                ]);
                return false;
            }
            
            Log::warning('FileService: Download denied - Unknown user type', [
                'user_type' => $user->user_type ?? 'null'
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('FileService: Download permission check failed', [
                'file_id' => $file->id ?? null,
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function backupFile(string $filePath): string
    {
        try {
            Log::info('FileService: Backing up file', ['path' => $filePath]);
            
            // Simulate backup
            $backupPath = 'backups/' . basename($filePath) . '_' . date('Y-m-d_H-i-s');
            
            Log::info('FileService: File backed up successfully', ['backup_path' => $backupPath]);
            return $backupPath;
        } catch (\Exception $e) {
            Log::error('FileService: File backup failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
