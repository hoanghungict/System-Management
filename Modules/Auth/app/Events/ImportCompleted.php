<?php

declare(strict_types=1);

namespace Modules\Auth\app\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImportCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $importJobId;
    public int $userId;
    public int $total;
    public int $success;
    public int $failed;

    public function __construct(
        int $importJobId,
        int $userId,
        int $total,
        int $success,
        int $failed
    ) {
        $this->importJobId = $importJobId;
        $this->userId = $userId;
        $this->total = $total;
        $this->success = $success;
        $this->failed = $failed;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("import-job.{$this->importJobId}");
    }

    public function broadcastAs(): string
    {
        return 'import.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'import_job_id' => $this->importJobId,
            'status' => 'done',
            'total' => $this->total,
            'success' => $this->success,
            'failed' => $this->failed,
        ];
    }
}


