<?php

declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers\Lecturer\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Task\app\Services\FileService;

/**
 * Action: Delete a file from task
 */
class DeleteFileAction extends BaseLecturerAction
{
    public function __construct(
        private readonly FileService $fileService
    ) {}

    public function __invoke(Request $request, int $taskId, int $fileId): JsonResponse
    {
        try {
            $userId = $this->getUserId($request);
            $userType = $this->getUserType($request);

            if (!$userId) {
                return $this->unauthorizedResponse();
            }

            $user = (object) [
                'id' => $userId,
                'user_type' => $userType
            ];

            $result = $this->fileService->deleteFile($fileId, $user);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'File deleted successfully'
                ]);
            }

            return $this->notFoundResponse('File');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete file', $e);
        }
    }
}
