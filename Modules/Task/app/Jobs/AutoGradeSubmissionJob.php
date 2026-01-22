<?php

namespace Modules\Task\app\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Task\app\Models\AssignmentSubmission;
use Modules\Auth\app\Models\AuditLog;
use Illuminate\Support\Facades\Log;

/**
 * Job Auto Grade Submission
 * Automatically grades multiple choice and short answer questions
 */
class AutoGradeSubmissionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $submissionId;

    public function __construct(int $submissionId)
    {
        $this->submissionId = $submissionId;
    }

    public function handle(): void
    {
        $submission = AssignmentSubmission::with(['answers.question', 'assignment'])->find($this->submissionId);

        if (!$submission) {
            Log::error("Submission not found: {$this->submissionId}");
            return;
        }

        try {
            // Check each answer
            foreach ($submission->answers as $answer) {
                if ($answer->question->is_auto_gradable) {
                    $answer->checkAnswer();
                }
            }
            
            $autoScore = $submission->calculateAutoScore();

            // If assignment is mixed (has essays), status is 'submitted', otherwise 'graded'
            $hasEssay = $submission->assignment->type === 'essay' || 
                        ($submission->assignment->type === 'mixed' && $submission->answers()->whereHas('question', fn($q) => $q->essay())->exists());
            
            if ($hasEssay) {
                // Must wait for manual grading
                $submission->update([
                    'auto_score' => $autoScore,
                    'status' => 'submitted' // Pending grading
                ]);
            } else {
                // Fully auto-graded
                $submission->update([
                    'auto_score' => $autoScore,
                    'total_score' => $autoScore,
                    'status' => 'graded',
                    'graded_at' => now(),
                    'graded_by' => null // System
                ]);
                
                AuditLog::log('submission_graded', null, 'AssignmentSubmission', $submission->id, [
                    'auto_score' => $autoScore,
                    'total_score' => $autoScore,
                    'type' => 'auto'
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Auto-grade failed for submission {$this->submissionId}: " . $e->getMessage());
        }
    }
}
