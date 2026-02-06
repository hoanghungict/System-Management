<?php

namespace Modules\Task\app\Http\Controllers\Task;

use App\Http\Controllers\Controller;
use Modules\Task\app\Models\Task;
use Modules\Task\app\Services\Interfaces\TaskServiceInterface;
use Modules\Task\app\Services\UserContextService;
use Modules\Task\app\Http\Requests\TaskSubmitRequest;
use Modules\Task\app\Transformers\TaskResource;
use Modules\Task\app\DTOs\TaskDTO;
use Modules\Task\app\UseCases\SubmitTaskUseCase;
use Modules\Task\app\Exceptions\TaskException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Task\app\Jobs\ProcessTaskJob;
use Modules\Task\app\Jobs\ProcessTaskFileJob;
use Modules\Task\app\Services\FileService;
use Illuminate\Support\Facades\Log;

/**
 * TaskSubmitController - Controller xử lý submit task cho cả sinh viên và giảng viên
 * 
 * Tuân thủ Clean Architecture:
 * - Chỉ xử lý HTTP requests/responses
 * - Không chứa business logic
 * - Sử dụng dependency injection
 * - Delegate business logic cho Services và Use Cases
 */
class TaskSubmitController extends Controller
{
    protected TaskServiceInterface $taskService;
    protected SubmitTaskUseCase $submitTaskUseCase;
    protected UserContextService $userContextService;

    /**
     * Khởi tạo controller với dependency injection
     */
    public function __construct(
        TaskServiceInterface $taskService,
        SubmitTaskUseCase $submitTaskUseCase,
        UserContextService $userContextService
    ) {
        $this->taskService = $taskService;
        $this->submitTaskUseCase = $submitTaskUseCase;
        $this->userContextService = $userContextService;
    }

    /**
     * Submit task - API chung cho cả sinh viên và giảng viên
     * 
     * @param TaskSubmitRequest $request
     * @param int $taskId
     * @return JsonResponse
     */
    public function submitTask(TaskSubmitRequest $request, int $taskId): JsonResponse
    {
        try {
            // Lấy thông tin user từ JWT
            $userId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type', 'student');

            // Create basic user context (fallback)
            $userContext = (object) [
                'id' => $userId,
                'type' => $userType,
                'name' => 'User ' . $userId,
                'email' => 'user' . $userId . '@example.com'
            ];

            // Lấy dữ liệu từ request
            $data = $request->validated();
            $data['task_id'] = $taskId;
            $data['user_id'] = $userId;
            $data['user_type'] = $userType;

            // Submit task thông qua Use Case
            $result = $this->submitTaskUseCase->execute($data, $userContext);

            // Log submission
            /* Log::info('Task submitted', [
                'task_id' => $taskId,
                'user_id' => $userId,
                'user_type' => $userType,
                'submission_type' => $data['submission_type'] ?? 'task_completion'
            ]); */

            // Dispatch background job để xử lý post-submission tasks
            ProcessTaskJob::dispatch($taskId, $userId, $userType, 'submitted');

            return response()->json([
                'success' => true,
                'message' => 'Task submitted successfully',
                'data' => $result
            ]);
        } catch (TaskException $e) {
            Log::error('Task submission failed', [
                'task_id' => $taskId,
                'user_id' => $userId ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 400);
        } catch (\Exception $e) {
            Log::error('Unexpected error during task submission', [
                'task_id' => $taskId,
                'user_id' => $userId ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get submission details - API chung cho cả sinh viên và giảng viên
     * 
     * @param Request $request
     * @param int $taskId
     * @return JsonResponse
     */
    public function getSubmission(Request $request, int $taskId): JsonResponse
    {
        try {
            $userId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type', 'student');

            // Get submission through repository or service
            $submission = null; // TODO: Implement getTaskSubmission method

            if (!$submission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Submission not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Submission retrieved successfully',
                'data' => $submission
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get submission', [
                'task_id' => $taskId,
                'user_id' => $userId ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get submission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update submission - API chung cho cả sinh viên và giảng viên
     * 
     * @param TaskSubmitRequest $request
     * @param int $taskId
     * @return JsonResponse
     */
    public function updateSubmission(TaskSubmitRequest $request, int $taskId): JsonResponse
    {
        try {
            $userId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type', 'student');

            $data = $request->validated();
            $data['task_id'] = $taskId;
            $data['user_id'] = $userId;
            $data['user_type'] = $userType;

            $result = $this->submitTaskUseCase->updateSubmission($data, $userId, $userType);

            /* Log::info('Task submission updated', [
                'task_id' => $taskId,
                'user_id' => $userId,
                'user_type' => $userType
            ]); */

            return response()->json([
                'success' => true,
                'message' => 'Submission updated successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update submission', [
                'task_id' => $taskId,
                'user_id' => $userId ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update submission: ' . $e->getMessage()
            ], 500);
        }
    }
}
