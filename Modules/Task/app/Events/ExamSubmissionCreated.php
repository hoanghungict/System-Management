<?php

namespace Modules\Task\app\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Task\app\Models\ExamSubmission;

class ExamSubmissionCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ExamSubmission $submission
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('exams.' . $this->submission->exam_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'submission.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'submission' => $this->submission->load('student')
        ];
    }
}
