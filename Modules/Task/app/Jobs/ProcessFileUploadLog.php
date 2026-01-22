<?php

namespace Modules\Task\app\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Auth\app\Models\AuditLog;

class ProcessFileUploadLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $filePath;
    protected $status;
    protected $errorMessage;

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId, string $filePath, string $status = 'success', ?string $errorMessage = null)
    {
        $this->userId = $userId;
        $this->filePath = $filePath;
        $this->status = $status;
        $this->errorMessage = $errorMessage;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $action = $this->status === 'success' ? 'upload_file_success' : 'upload_file_failed';
        
        $data = [
            'file_path' => $this->filePath,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($this->errorMessage) {
            $data['error'] = $this->errorMessage;
        }

        AuditLog::log(
            $action,
            $this->userId,
            'file',
            0, // No specific ID for generic file
            $data
        );
    }
}
