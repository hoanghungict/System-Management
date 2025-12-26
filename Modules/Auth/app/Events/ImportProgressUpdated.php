<?php

declare(strict_types=1);

namespace Modules\Auth\app\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImportProgressUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $importJobId;
    public int $userId;
    public string $status;
    public int $total;
    public int $processed;
    public int $success;
    public int $failed;
    public float $percent;

    public function __construct(
        int $importJobId,
        int $userId,
        string $status,
        int $total,
        int $processed,
        int $success,
        int $failed
    ) {
        $this->importJobId = $importJobId;
        $this->userId = $userId;
        $this->status = $status;
        $this->total = $total;
        $this->processed = $processed;
        $this->success = $success;
        $this->failed = $failed;
        $this->percent = $total > 0 ? round(($processed / $total) * 100, 2) : 0.0;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("import-job.{$this->importJobId}");
    }

    public function broadcastAs(): string
    {
        return 'import.progress';
    }

    public function broadcastWith(): array
    {
        return [
            'import_job_id' => $this->importJobId,
            'status' => $this->status,
            'total' => $this->total,
            'processed' => $this->processed,
            'success' => $this->success,
            'failed' => $this->failed,
            'percent' => $this->percent,
        ];
    }
}


