<?php

namespace Modules\Task\app\Events;

use Modules\Task\app\DTOs\EmailReportDTO;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmailFailedEvent
{
    use Dispatchable, SerializesModels;

    public EmailReportDTO $emailDTO;
    public string $errorMessage;
    public string $timestamp;

    public function __construct(EmailReportDTO $emailDTO, string $errorMessage)
    {
        $this->emailDTO = $emailDTO;
        $this->errorMessage = $errorMessage;
        $this->timestamp = now()->toISOString();
    }

    /**
     * Lấy tên event
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'email.failed';
    }

    /**
     * Lấy dữ liệu event
     *
     * @return array
     */
    public function getEventData(): array
    {
        return [
            'event_name' => $this->getEventName(),
            'recipients_count' => count($this->emailDTO->recipients),
            'subject' => $this->emailDTO->subject,
            'template' => $this->emailDTO->template,
            'error_message' => $this->errorMessage,
            'timestamp' => $this->timestamp,
            'metadata' => $this->emailDTO->metadata
        ];
    }
}
