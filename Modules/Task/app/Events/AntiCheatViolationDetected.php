<?php

namespace Modules\Task\app\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Task\app\Models\ExamSubmission;

class AntiCheatViolationDetected implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ExamSubmission $submission,
        public string $violationType,
        public array $details = []
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('exams.' . $this->submission->exam_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'anti-cheat.violation';
    }

    public function broadcastWith(): array
    {
        return [
            'submission_id' => $this->submission->id,
            'student_id' => $this->submission->student_id,
            'violation_type' => $this->violationType,
            'details' => $this->details,
            // Calculate total violations dynamically
            'total_violations' => is_array($this->submission->anti_cheat_violations) ? count($this->submission->anti_cheat_violations) : 0,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
