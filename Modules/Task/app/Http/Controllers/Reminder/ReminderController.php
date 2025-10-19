<?php

declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers\Reminder;

use App\Http\Controllers\Controller;
use Modules\Task\app\Services\ReminderService;
use Modules\Task\app\Http\Requests\ReminderRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Reminder Controller
 * 
 * Handles reminder management endpoints
 */
class ReminderController extends Controller
{
    public function __construct(
        private readonly ReminderService $reminderService
    ) {}

    /**
     * Get user reminders
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type');

            if (!$userId || !$userType) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $filters = $request->only([
                'status',
                'reminder_type',
                'start_date',
                'end_date',
                'task_id',
                'page',
                'per_page'
            ]);

            $reminders = $this->reminderService->getUserReminders($userId, $userType, $filters);

            return response()->json([
                'success' => true,
                'data' => $reminders,
                'message' => 'Reminders retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('ReminderController: Failed to get reminders', [
                'user_id' => $userId ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reminders'
            ], 500);
        }
    }

    /**
     * Create new reminder
     */
    public function store(ReminderRequest $request): JsonResponse
    {
        try {
            $userId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type');

            if (!$userId || !$userType) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $data = $request->validated();
            $data['user_id'] = $userId;
            $data['user_type'] = $userType;

            $reminder = $this->reminderService->createReminder($data);

            return response()->json([
                'success' => true,
                'data' => $reminder,
                'message' => 'Reminder created successfully'
            ], 201);

        } catch (\Exception $e) {
            Log::error('ReminderController: Failed to create reminder', [
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create reminder: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get specific reminder
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $userId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type');

            if (!$userId || !$userType) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $reminder = $this->reminderService->getUserReminders($userId, $userType, ['reminder_id' => $id]);

            if (empty($reminder['data'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reminder not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $reminder['data'][0],
                'message' => 'Reminder retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('ReminderController: Failed to get reminder', [
                'reminder_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reminder'
            ], 500);
        }
    }

    /**
     * Update reminder
     */
    public function update(ReminderRequest $request, int $id): JsonResponse
    {
        try {
            $userId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type');

            if (!$userId || !$userType) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $data = $request->validated();
            $reminder = $this->reminderService->updateReminder($id, $data);

            return response()->json([
                'success' => true,
                'data' => $reminder,
                'message' => 'Reminder updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('ReminderController: Failed to update reminder', [
                'reminder_id' => $id,
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update reminder: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete reminder
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $userId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type');

            if (!$userId || !$userType) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $result = $this->reminderService->deleteReminder($id);

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete reminder'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Reminder deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('ReminderController: Failed to delete reminder', [
                'reminder_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete reminder: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Process due reminders (Admin only)
     */
    public function processDue(Request $request): JsonResponse
    {
        try {
            $userType = $request->attributes->get('jwt_user_type');

            if ($userType !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Admin role required.'
                ], 403);
            }

            $processedCount = $this->reminderService->processDueReminders();

            return response()->json([
                'success' => true,
                'data' => ['processed_count' => $processedCount],
                'message' => "Processed {$processedCount} due reminders"
            ]);

        } catch (\Exception $e) {
            Log::error('ReminderController: Failed to process due reminders', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process due reminders'
            ], 500);
        }
    }
}
