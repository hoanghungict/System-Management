<?php

declare(strict_types=1);

namespace Modules\Task\app\Services;

use Modules\Task\app\Models\Reminder;
use Modules\Task\app\Models\Task;
use Modules\Task\app\Repositories\Interfaces\ReminderRepositoryInterface;
use Modules\Notifications\app\Services\NotificationService\NotificationService;
use Modules\Notifications\app\Services\KafkaService\KafkaProducerService;
use Modules\Task\app\Jobs\SendReminderNotificationJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Reminder Service
 * 
 * Handles reminder creation, scheduling, and sending
 */
class ReminderService
{
    public function __construct(
        private readonly ReminderRepositoryInterface $reminderRepository,
        private readonly NotificationService $notificationService,
        private readonly KafkaProducerService $kafkaProducer
    ) {}

    /**
     * Create a new reminder
     */
    public function createReminder(array $data): Reminder
    {
        try {
            // Validate reminder data
            $this->validateReminderData($data);

            // Check if task exists
            $task = Task::find($data['task_id']);
            if (!$task) {
                throw new \InvalidArgumentException('Task not found');
            }

            // Create reminder
            $reminder = $this->reminderRepository->create($data);

            // Schedule reminder if it's in the future
            if ($reminder->reminder_time > now()) {
                $this->scheduleReminder($reminder);
            } else {
                // Send immediately if reminder time has passed
                $this->sendReminder($reminder);
            }

            Log::info('ReminderService: Reminder created successfully', [
                'reminder_id' => $reminder->id,
                'task_id' => $data['task_id'],
                'reminder_time' => $reminder->reminder_time
            ]);

            return $reminder;

        } catch (\Exception $e) {
            Log::error('ReminderService: Failed to create reminder', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get reminders for user
     */
    public function getUserReminders(int $userId, string $userType, array $filters = []): array
    {
        try {
            return $this->reminderRepository->getUserReminders($userId, $userType, $filters);
        } catch (\Exception $e) {
            Log::error('ReminderService: Failed to get user reminders', [
                'user_id' => $userId,
                'user_type' => $userType,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Update reminder
     */
    public function updateReminder(int $reminderId, array $data): Reminder
    {
        try {
            $reminder = $this->reminderRepository->findById($reminderId);
            if (!$reminder) {
                throw new \InvalidArgumentException('Reminder not found');
            }

            $updatedReminder = $this->reminderRepository->update($reminder, $data);

            Log::info('ReminderService: Reminder updated successfully', [
                'reminder_id' => $reminderId,
                'data' => $data
            ]);

            return $updatedReminder;

        } catch (\Exception $e) {
            Log::error('ReminderService: Failed to update reminder', [
                'reminder_id' => $reminderId,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Delete reminder
     */
    public function deleteReminder(int $reminderId): bool
    {
        try {
            $reminder = $this->reminderRepository->findById($reminderId);
            if (!$reminder) {
                throw new \InvalidArgumentException('Reminder not found');
            }

            $result = $this->reminderRepository->delete($reminder);

            Log::info('ReminderService: Reminder deleted successfully', [
                'reminder_id' => $reminderId
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('ReminderService: Failed to delete reminder', [
                'reminder_id' => $reminderId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Process due reminders
     */
    public function processDueReminders(): int
    {
        try {
            $dueReminders = $this->reminderRepository->getDueReminders();
            $processedCount = 0;

            foreach ($dueReminders as $reminder) {
                try {
                    $this->sendReminder($reminder);
                    $processedCount++;
                } catch (\Exception $e) {
                    Log::error('ReminderService: Failed to send reminder', [
                        'reminder_id' => $reminder->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('ReminderService: Processed due reminders', [
                'processed_count' => $processedCount,
                'total_due' => $dueReminders->count()
            ]);

            return $processedCount;

        } catch (\Exception $e) {
            Log::error('ReminderService: Failed to process due reminders', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Create automatic reminders for task
     */
    public function createAutomaticReminders(Task $task): void
    {
        try {
            // Create reminders based on task deadline
            $deadline = Carbon::parse($task->deadline);
            $now = now();

            // 1 week before deadline
            if ($deadline->diffInDays($now) >= 7) {
                $this->createReminder([
                    'task_id' => $task->id,
                    'user_id' => $task->assigned_to_id,
                    'user_type' => $task->assigned_to,
                    'reminder_type' => 'email',
                    'reminder_time' => $deadline->subDays(7),
                    'message' => "Task '{$task->title}' is due in 1 week",
                    'status' => 'pending'
                ]);
            }

            // 1 day before deadline
            if ($deadline->diffInDays($now) >= 1) {
                $this->createReminder([
                    'task_id' => $task->id,
                    'user_id' => $task->assigned_to_id,
                    'user_type' => $task->assigned_to,
                    'reminder_type' => 'email',
                    'reminder_time' => $deadline->subDay(),
                    'message' => "Task '{$task->title}' is due tomorrow",
                    'status' => 'pending'
                ]);
            }

            // 1 hour before deadline
            $this->createReminder([
                'task_id' => $task->id,
                'user_id' => $task->assigned_to_id,
                'user_type' => $task->assigned_to,
                'reminder_type' => 'push',
                'reminder_time' => $deadline->subHour(),
                'message' => "Task '{$task->title}' is due in 1 hour",
                'status' => 'pending'
            ]);

            Log::info('ReminderService: Automatic reminders created for task', [
                'task_id' => $task->id,
                'deadline' => $task->deadline
            ]);

        } catch (\Exception $e) {
            Log::error('ReminderService: Failed to create automatic reminders', [
                'task_id' => $task->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send reminder notification via Kafka
     */
    private function sendReminder(Reminder $reminder): void
    {
        try {
            // Prepare event data for Kafka
            $eventData = [
                'reminder_id' => $reminder->id,
                'user_id' => $reminder->user_id,
                'user_type' => $reminder->user_type,
                'task_id' => $reminder->task_id,
                'reminder_type' => $reminder->reminder_type,
                'message' => $reminder->message,
                'reminder_time' => $reminder->reminder_time->toISOString(),
                'task_name' => $reminder->task->title ?? 'Task',
                'task_description' => $reminder->task->description ?? '',
                'deadline' => $reminder->task->deadline?->toISOString() ?? '',
                'user_name' => $reminder->user->name ?? 'User',
                'task_url' => config('app.url') . "/tasks/{$reminder->task_id}",
                'sender_id' => $reminder->task->creator_id ?? null,
                'sender_type' => $reminder->task->creator_type ?? 'system'
            ];

            // Determine event topic based on reminder type and timing
            $topic = $this->getEventTopic($reminder);

            // Publish event to Kafka
            $this->kafkaProducer->send($topic, $eventData);

            // Mark reminder as sent
            $reminder->markAsSent();

            Log::info('ReminderService: Reminder event published successfully', [
                'reminder_id' => $reminder->id,
                'user_id' => $reminder->user_id,
                'topic' => $topic
            ]);

        } catch (\Exception $e) {
            // Mark reminder as failed
            $reminder->markAsFailed();

            Log::error('ReminderService: Failed to send reminder', [
                'reminder_id' => $reminder->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Get event topic based on reminder type and timing
     */
    private function getEventTopic(Reminder $reminder): string
    {
        $deadline = $reminder->task->deadline;
        
        if ($deadline && $deadline->isPast()) {
            return 'reminder.task.overdue';
        }

        return 'reminder.task.deadline';
    }

    /**
     * Schedule reminder (placeholder for future implementation)
     */
    private function scheduleReminder(Reminder $reminder): void
    {
        // TODO: Implement scheduling logic
        // This could use Laravel's task scheduler or a queue job
        Log::info('ReminderService: Reminder scheduled', [
            'reminder_id' => $reminder->id,
            'reminder_time' => $reminder->reminder_time
        ]);
    }

    /**
     * Validate reminder data
     */
    private function validateReminderData(array $data): void
    {
        $required = ['task_id', 'user_id', 'user_type', 'reminder_type', 'reminder_time'];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        // Validate reminder type
        if (!in_array($data['reminder_type'], array_keys(Reminder::REMINDER_TYPES))) {
            throw new \InvalidArgumentException('Invalid reminder type');
        }

        // Validate user type
        if (!in_array($data['user_type'], ['student', 'lecturer', 'admin'])) {
            throw new \InvalidArgumentException('Invalid user type');
        }
    }
}
