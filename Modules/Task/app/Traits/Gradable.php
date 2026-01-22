<?php

namespace Modules\Task\app\Traits;

use Modules\Auth\app\Models\AuditLog;

/**
 * Gradable Trait - Unified logic for grading
 */
trait Gradable
{
    /**
     * Get the final score (prefers manual_score if set)
     */
    public function getFinalScoreAttribute(): float
    {
        return (float) ($this->manual_score ?? $this->auto_score ?? 0);
    }

    /**
     * Check if the submission is graded
     */
    public function isGraded(): bool
    {
        return $this->status === 'graded';
    }

    /**
     * Check if the submission is submitted
     */
    public function isSubmitted(): bool
    {
        return in_array($this->status, ['submitted', 'graded']);
    }

    /**
     * Unified grading method
     */
    public function performManualGrading(int $graderId, float $score, ?string $note = null): bool
    {
        $this->manual_score = $score;
        $this->graded_by = $graderId;
        $this->graded_at = now();
        $this->status = 'graded';

        // Support both 'grader_note' (Exams) and 'feedback' (Assignments)
        if ($this->hasColumn('grader_note')) {
            $this->grader_note = $note;
        } elseif ($this->hasColumn('feedback')) {
            $this->feedback = $note;
        }

        $saved = $this->save();

        if ($saved) {
            AuditLog::log(
                'submission_graded',
                $graderId,
                class_basename($this),
                $this->id,
                [
                    'manual_score' => $score,
                    'auto_score' => $this->auto_score,
                    'final_score' => $this->final_score,
                ]
            );
        }

        return $saved;
    }

    /**
     * Helper to check if a column exists in the model's table
     */
    protected function hasColumn(string $column): bool
    {
        return \Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), $column);
    }
}
