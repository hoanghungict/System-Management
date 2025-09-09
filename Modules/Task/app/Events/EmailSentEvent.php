<?php

namespace Modules\Task\app\Events;

use Modules\Task\app\DTOs\EmailReportDTO;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmailSentEvent
{
    use Dispatchable, SerializesModels;

    public EmailReportDTO $emailDTO;
    public string $timestamp;

    public function __construct(EmailReportDTO $emailDTO)
    {
        $this->emailDTO = $emailDTO;
        $this->timestamp = now()->toISOString();
    }

    /**
     * Lấy tên event
     *
     * @return string
     */
    public function getEventName(): string
    {
        return 'email.sent';
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
            'timestamp' => $this->timestamp,
            'metadata' => $this->emailDTO->metadata
        ];
    }
}
