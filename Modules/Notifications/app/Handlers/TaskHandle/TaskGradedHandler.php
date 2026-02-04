<?php

declare(strict_types=1);

namespace Modules\Notifications\app\Handlers\TaskHandle;

use Modules\Notifications\app\Services\NotificationService;
use Modules\Notifications\app\Contracts\NotificationEventHandler;
use Illuminate\Support\Facades\Log;

/**
 * Task Graded Handler
 * 
 * Handles notifications when a task is graded.
 * Sends notifications to the student who submitted the task.
 * 
 * @package Modules\Notifications\app\Handlers\TaskHandle
 * @author System Management Team
 * @version 1.0.0
 */
class TaskGradedHandler implements NotificationEventHandler
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Handle task graded notification
     */
    public function handle(string $channel, array $data): void
    {
        try {
            // Validate required data
            if (!isset($data['task_id']) || !isset($data['student_id']) || !isset($data['student_type'])) {
                Log::error('TaskGradedHandler: Missing required data', ['data' => $data]);
                return;
            }

            $taskId = $data['task_id'];
            $studentId = $data['student_id'];
            $studentType = $data['student_type'];
            $taskTitle = $data['task_title'] ?? 'Task';
            $grade = $data['grade'] ?? 0;
            $maxGrade = $data['max_grade'] ?? 100;
            $feedback = $data['feedback'] ?? '';
            $graderName = $data['grader_name'] ?? 'Lecturer';
            $gradedAt = $data['graded_at'] ?? now()->format('Y-m-d H:i:s');

            // Calculate grade percentage
            $gradePercentage = $maxGrade > 0 ? round(($grade / $maxGrade) * 100, 2) : 0;

            // Determine grade status
            $gradeStatus = $this->getGradeStatus($gradePercentage);

            // Prepare template data
            $templateData = [
                'task_id' => $taskId,
                'task_title' => $taskTitle,
                'grade' => $grade,
                'max_grade' => $maxGrade,
                'grade_percentage' => $gradePercentage,
                'grade_status' => $gradeStatus,
                'feedback' => $feedback,
                'grader_name' => $graderName,
                'student_name' => $data['student_name'] ?? 'Student',
                'graded_at' => $gradedAt,
                'task_url' => $data['task_url'] ?? '#',
                'grade_url' => $data['grade_url'] ?? '#',
                'is_pass' => $gradePercentage >= 50,
                'grade_emoji' => $this->getGradeEmoji($gradePercentage),
            ];

            // Determine priority based on grade
            $priority = $this->getNotificationPriority($gradePercentage);

            // Send notification
            $this->notificationService->sendNotification(
                'task_graded', // Template name
                [['user_id' => $studentId, 'user_type' => $studentType]],
                $templateData,
                ['priority' => $priority]
            );

            /* Log::info('Task graded notification sent', [
                'task_id' => $taskId,
                'student_id' => $studentId,
                'student_type' => $studentType,
                'grade' => $grade,
                'grade_percentage' => $gradePercentage,
                'grader' => $graderName
            ]); */

        } catch (\Exception $e) {
            Log::error('TaskGradedHandler: Failed to send notification', [
                'error' => $e->getMessage(),
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Get grade status based on percentage
     */
    private function getGradeStatus(float $percentage): string
    {
        if ($percentage >= 90) return 'Excellent';
        if ($percentage >= 80) return 'Very Good';
        if ($percentage >= 70) return 'Good';
        if ($percentage >= 60) return 'Satisfactory';
        if ($percentage >= 50) return 'Pass';
        return 'Fail';
    }

    /**
     * Get grade emoji based on percentage
     */
    private function getGradeEmoji(float $percentage): string
    {
        if ($percentage >= 90) return '🎉';
        if ($percentage >= 80) return '😊';
        if ($percentage >= 70) return '👍';
        if ($percentage >= 60) return '👌';
        if ($percentage >= 50) return '✅';
        return '❌';
    }

    /**
     * Get notification priority based on grade
     */
    private function getNotificationPriority(float $percentage): string
    {
        // High priority for very high or very low grades
        if ($percentage >= 95 || $percentage < 50) {
            return 'high';
        }

        return 'normal';
    }
}
