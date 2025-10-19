<?php

declare(strict_types=1);

namespace Modules\Task\app\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Task\app\Events\TaskGraded;
use Modules\Task\app\Services\KafkaProducerService;

/**
 * Send Task Graded Notification Job
 * 
 * Handles sending notifications when a task is graded.
 * Dispatches Kafka events for notification processing.
 * 
 * @package Modules\Task\app\Jobs
 * @author System Management Team
 * @version 1.0.0
 */
class SendTaskGradedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public TaskGraded $event
    ) {}

    /**
     * Execute the job.
     */
    public function handle(KafkaProducerService $kafkaProducer): void
    {
        try {
            $task = $this->event->task;
            $submission = $this->event->submission;
            
            // Calculate grade percentage
            $maxGrade = $submission->max_grade ?? 100;
            $gradePercentage = $maxGrade > 0 ? round(($submission->grade / $maxGrade) * 100, 2) : 0;
            
            // Prepare notification data
            $notificationData = [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'grade' => $submission->grade,
                'max_grade' => $maxGrade,
                'grade_percentage' => $gradePercentage,
                'feedback' => $submission->feedback ?? '',
                'grader_id' => $task->creator_id,
                'grader_type' => $task->creator_type,
                'grader_name' => $this->getGraderName($task),
                'student_id' => $submission->user_id,
                'student_type' => $submission->user_type,
                'student_name' => $this->getStudentName($submission),
                'graded_at' => $submission->updated_at->format('Y-m-d H:i:s'),
                'task_url' => $this->getTaskUrl($task),
                'grade_url' => $this->getGradeUrl($task, $submission),
                'is_pass' => $gradePercentage >= 50,
            ];

            // Send to Kafka
            $kafkaProducer->publish('task.graded', $notificationData);

            Log::info('Task graded notification job dispatched', [
                'task_id' => $task->id,
                'student_id' => $submission->user_id,
                'grade' => $submission->grade,
                'grade_percentage' => $gradePercentage
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send task graded notification job', [
                'task_id' => $this->event->task->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->fail($e);
        }
    }

    /**
     * Get grader name
     */
    private function getGraderName($task): string
    {
        // TODO: Implement based on user type and ID
        return 'Lecturer';
    }

    /**
     * Get student name
     */
    private function getStudentName($submission): string
    {
        // TODO: Implement based on user type and ID
        return 'Student';
    }

    /**
     * Get task URL
     */
    private function getTaskUrl($task): string
    {
        return config('app.url') . "/tasks/{$task->id}";
    }

    /**
     * Get grade URL
     */
    private function getGradeUrl($task, $submission): string
    {
        return config('app.url') . "/tasks/{$task->id}/submissions/{$submission->id}/grade";
    }
}
