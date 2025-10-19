<?php

declare(strict_types=1);

namespace Modules\Task\app\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Task\app\Events\TaskCreated;
use Modules\Task\app\Services\KafkaProducerService;

/**
 * Send Task Created Notification Job
 * 
 * Handles sending notifications when a task is created.
 * Dispatches Kafka events for notification processing.
 * 
 * @package Modules\Task\app\Jobs
 * @author System Management Team
 * @version 1.0.0
 */
class SendTaskCreatedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public TaskCreated $event
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
                'creator_id' => $task->creator_id,
                'creator_type' => $task->creator_type,
                'creator_name' => $this->getCreatorName($task),
                'receiver_id' => $task->receiver_id,
                'receiver_type' => $task->receiver_type,
                'receiver_name' => $this->getReceiverName($task),
                'task_url' => $this->getTaskUrl($task),
                'created_at' => $task->created_at->format('Y-m-d H:i:s'),
            ];

            // Send to Kafka
            $kafkaProducer->publish('task.created', $notificationData);

            Log::info('Task created notification job dispatched', [
                'task_id' => $task->id,
                'receiver_id' => $task->receiver_id,
                'receiver_type' => $task->receiver_type
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send task created notification job', [
                'task_id' => $this->event->task->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->fail($e);
        }
    }

    /**
     * Get creator name
     */
    private function getCreatorName($task): string
    {
        // TODO: Implement based on user type and ID
        return 'System';
    }

    /**
     * Get receiver name
     */
    private function getReceiverName($task): string
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
