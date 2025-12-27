<?php

namespace Modules\Task\app\Http\Controllers\Task;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Task\app\Models\Task;
use Modules\Task\app\Http\Controllers\Task\Actions\{
    IndexAction,
    ShowAction,
    GetMyTasksAction,
    UpdateStatusAction,
    GetMyStatisticsAction
};
use Modules\Task\app\Services\FileService;
use Modules\Task\app\Transformers\TaskResource;

/**
 * Slim Task Controller - Delegates to Action classes
 * 
 * Note: departments, classes, students, lecturers routes are now handled by Auth module
 */
class TaskController extends Controller
{
    public function __construct(
        private readonly IndexAction $indexAction,
        private readonly ShowAction $showAction,
        private readonly GetMyTasksAction $getMyTasksAction,
        private readonly UpdateStatusAction $updateStatusAction,
        private readonly GetMyStatisticsAction $getMyStatisticsAction,
        private readonly FileService $fileService
    ) {}

    public function index(Request $request): JsonResponse
    {
        return ($this->indexAction)($request);
    }

    public function show($taskId): JsonResponse
    {
        return ($this->showAction)((int) $taskId);
    }

    public function getMyTasks(Request $request): JsonResponse
    {
        return ($this->getMyTasksAction)($request);
    }

    public function updateStatus(Request $request, $taskId): JsonResponse
    {
        return ($this->updateStatusAction)($request, (int) $taskId);
    }

    public function getMyStatistics(Request $request): JsonResponse
    {
        return ($this->getMyStatisticsAction)($request);
    }

    public function uploadFiles(Request $request, $taskId): JsonResponse
    {
        try {
            $task = Task::with('receivers')->find($taskId);

            if (!$task) {
                return response()->json(['success' => false, 'message' => 'Task not found'], 404);
            }

            $userId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type');

            if (!$userId) {
                return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
            }

            $user = (object) ['id' => $userId, 'user_type' => $userType];

            if (!$this->fileService->canUserUploadFiles($task, $user)) {
                return response()->json(['success' => false, 'message' => 'Permission denied'], 403);
            }

            $uploadedFiles = $request->file('files');
            $files = $uploadedFiles ? (is_array($uploadedFiles) ? $uploadedFiles : [$uploadedFiles]) : [];

            if (empty($files)) {
                return response()->json(['success' => false, 'message' => 'No files provided'], 400);
            }

            $result = $this->fileService->uploadFilesToTask($task, $files, $user);

            return response()->json([
                'success' => true,
                'message' => 'Files uploaded successfully',
                'data' => $result['files'],
                'count' => $result['count']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload files',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteFile(Request $request, Task $task, int $fileId): JsonResponse
    {
        try {
            $userId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type');

            if (!$userId) {
                return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
            }

            $user = (object) ['id' => $userId, 'user_type' => $userType];

            $result = $this->fileService->deleteFile($fileId, $user);

            if ($result) {
                return response()->json(['success' => true, 'message' => 'File deleted successfully']);
            }

            return response()->json(['success' => false, 'message' => 'File not found'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function downloadFile(Request $request, Task $task, int $fileId)
    {
        try {
            $taskFile = $task->files()->where('id', $fileId)->first();

            if (!$taskFile) {
                return response()->json(['success' => false, 'message' => 'File not found'], 404);
            }

            $path = $taskFile->path;
            
            if (!\Illuminate\Support\Facades\Storage::exists($path)) {
                return response()->json(['success' => false, 'message' => 'File not found on disk'], 404);
            }

            return \Illuminate\Support\Facades\Storage::download($path, $taskFile->name);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download file',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
