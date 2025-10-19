<?php

declare(strict_types=1);

namespace Modules\Task\app\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Task\app\Events\TaskAssigned;
use Modules\Task\app\Services\KafkaProducerService;

/**
 * Send Task Assigned Notification Job
 * 
 * Handles sending notifications when a task is assigned to a user.
 * Dispatches Kafka events for notification processing.
 * 
 * @package Modules\Task\app\Jobs
 * @author System Management Team
 * @version 1.0.0
 */
class SendTaskAssignedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public TaskAssigned $event
    ) {}

    /**
     * Execute the job.
     */
    public function handle(KafkaProducerService $kafkaProducer): void
    {
        try {
            $task = $this->event->task;
            
            // Prepare notification data
            $notificationData = [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'task_description' => $task->description,
                'deadline' => $task->deadline?->format('Y-m-d H:i:s'),
                'priority' => $task->priority,
                'assigner_id' => $task->creator_id,
                'assigner_type' => $task->creator_type,
                'assigner_name' => $this->getAssignerName($task),
                'receiver_id' => $this->event->receiverId,
                'receiver_type' => $this->event->receiverType,
                'receiver_name' => $this->getReceiverName($this->event->receiverId, $this->event->receiverType),
                'task_url' => $this->getTaskUrl($task),
                'assigned_at' => now()->format('Y-m-d H:i:s'),
            ];

            // Send to Kafka
            $kafkaProducer->publish('task.assigned', $notificationData);

            Log::info('Task assigned notification job dispatched', [
                'task_id' => $task->id,
                'receiver_id' => $this->event->receiverId,
                'receiver_type' => $this->event->receiverType
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send task assigned notification job', [
                'task_id' => $this->event->task->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->fail($e);
        }
    }

    /**
     * Get assigner name
     */
    private function getAssignerName($task): string
    {
        // TODO: Implement based on user type and ID
        return 'System';
    }

    /**
     * Get receiver name
     */
    private function getReceiverName(int $receiverId, string $receiverType): string
    {
        // TODO: Implement based on user type and ID
        return 'User';
    }

    /**
     * Get task URL
     */
    private function getTaskUrl($task): string
    {
        return config('app.url') . "/tasks/{$task->id}";
    }
}
