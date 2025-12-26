<?php

declare(strict_types=1);

namespace Modules\Auth\app\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImportFailed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $importJobId;
    public int $userId;
    public string $error;
    public int $failedCount;

    public function __construct(
        int $importJobId,
        int $userId,
        string $error,
        int $failedCount = 0
    ) {
        $this->importJobId = $importJobId;
        $this->userId = $userId;
        $this->error = $error;
        $this->failedCount = $failedCount;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("import-job.{$this->importJobId}");
    }

    public function broadcastAs(): string
    {
        return 'import.failed';
    }

    public function broadcastWith(): array
    {
        return [
            'import_job_id' => $this->importJobId,
            'status' => 'failed',
            'error' => $this->error,
            'failed_count' => $this->failedCount,
        ];
    }
}


