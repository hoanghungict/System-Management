<?php

declare(strict_types=1);

namespace Modules\Task\app\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Task\app\Events\TaskSubmitted;
use Modules\Task\app\Services\KafkaProducerService;

/**
 * Send Task Submitted Notification Job
 * 
 * Handles sending notifications when a task is submitted.
 * Dispatches Kafka events for notification processing.
 * 
 * @package Modules\Task\app\Jobs
 * @author System Management Team
 * @version 1.0.0
 */
class SendTaskSubmittedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public TaskSubmitted $event
    ) {}

    /**
     * Execute the job.
     */
    public function handle(KafkaProducerService $kafkaProducer): void
    {
        try {
            $task = $this->event->task;
            $submission = $this->event->submission;
            
            // Check if submission is late
            $isLate = $task->deadline && $submission->created_at > $task->deadline;
            $daysLate = $isLate ? $task->deadline->diffInDays($submission->created_at) : 0;
            
            // Prepare notification data
            $notificationData = [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'submitter_id' => $submission->user_id,
                'submitter_type' => $submission->user_type,
                'submitter_name' => $this->getSubmitterName($submission),
                'submission_content' => $submission->content,
                'submitted_at' => $submission->created_at->format('Y-m-d H:i:s'),
                'creator_id' => $task->creator_id,
                'creator_type' => $task->creator_type,
                'creator_name' => $this->getCreatorName($task),
                'task_url' => $this->getTaskUrl($task),
                'submission_url' => $this->getSubmissionUrl($task, $submission),
                'is_late' => $isLate,
                'days_late' => $daysLate,
            ];

            // Send to Kafka
            $kafkaProducer->publish('task.submitted', $notificationData);

            Log::info('Task submitted notification job dispatched', [
                'task_id' => $task->id,
                'submitter_id' => $submission->user_id,
                'creator_id' => $task->creator_id,
                'is_late' => $isLate
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send task submitted notification job', [
                'task_id' => $this->event->task->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->fail($e);
        }
    }

    /**
     * Get submitter name
     */
    private function getSubmitterName($submission): string
    {
        // TODO: Implement based on user type and ID
        return 'Student';
    }

    /**
     * Get creator name
     */
    private function getCreatorName($task): string
    {
        // TODO: Implement based on user type and ID
        return 'Lecturer';
    }

    /**
     * Get task URL
     */
    private function getTaskUrl($task): string
    {
        return config('app.url') . "/tasks/{$task->id}";
    }

    /**
     * Get submission URL
     */
    private function getSubmissionUrl($task, $submission): string
    {
        return config('app.url') . "/tasks/{$task->id}/submissions/{$submission->id}";
    }
}
