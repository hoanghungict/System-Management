<?php

declare(strict_types=1);

namespace Modules\Task\app\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Task\app\Models\Task;
use Modules\Task\app\Models\TaskSubmission;

/**
 * Task Graded Event
 * 
 * Dispatched when a task is graded by a lecturer.
 * Used to trigger notifications to the student.
 * 
 * @package Modules\Task\app\Events
 * @author System Management Team
 * @version 1.0.0
 */
class TaskGraded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Task $task,
        public readonly TaskSubmission $submission,
        public readonly array $metadata = []
    ) {}

    /**
     * Get the channels the event should be broadcast on.
     */
    public function broadcastOn(): array
    {
        return [];
    }
}
