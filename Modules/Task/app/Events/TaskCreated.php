<?php

declare(strict_types=1);

namespace Modules\Task\app\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Task\app\Models\Task;

/**
 * Task Created Event
 * 
 * Dispatched when a new task is created.
 * Used to trigger notifications and other side effects.
 * 
 * @package Modules\Task\app\Events
 * @author System Management Team
 * @version 1.0.0
 */
class TaskCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Task $task,
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
