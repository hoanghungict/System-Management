<?php

declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers\Lecturer\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Task\app\Services\FileService;
use Modules\Task\app\Models\Task;
use Modules\Task\app\Models\TaskFile;

/**
 * Action: Upload files to task
 */
class UploadFilesAction extends BaseLecturerAction
{
    public function __construct(
        private readonly FileService $fileService
    ) {}

    public function __invoke(Request $request, int $taskId): JsonResponse
    {
        try {
            $taskModel = Task::with('receivers')->find($taskId);

            if (!$taskModel) {
                return $this->notFoundResponse('Task');
            }

            $userId = $this->getUserId($request);
            $userType = $this->getUserType($request);

            if (!$userId) {
                return $this->unauthorizedResponse();
            }

            $user = (object) [
                'id' => $userId,
                'user_type' => $userType
            ];

            if (!$this->fileService->canUserUploadFiles($taskModel, $user)) {
                return $this->accessDeniedResponse('Bạn không có quyền upload files cho task này');
            }

            // Handle both single and multiple files
            $uploadedFiles = $request->file('files') ?? $request->file('file');
            $files = [];
            
            if ($uploadedFiles) {
                $files = is_array($uploadedFiles) ? $uploadedFiles : [$uploadedFiles];
            }

            if (empty($files)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có files nào được upload'
                ], 400);
            }

            $result = $this->fileService->uploadFilesToTask($taskModel, $files, $user);

            return response()->json([
                'success' => true,
                'message' => 'File(s) uploaded successfully',
                'data' => count($result['files']) === 1 
                    ? $result['files'][0]
                    : $result['files'],
                'count' => $result['count']
            ], 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while uploading files', $e);
        }
    }
}
