<?php

namespace Modules\Task\app\DTOs;

class EmailReportDTO
{
    public array $recipients;
    public string $subject;
    public string $content;
    public array $reportData;
    public string $template;
    public array $attachments;
    public ?string $fromEmail;
    public ?string $fromName;
    public array $metadata;

    public function __construct(
        array $recipients,
        string $subject,
        string $content,
        array $reportData = [],
        string $template = 'emails.reports.default',
        array $attachments = [],
        ?string $fromEmail = null,
        ?string $fromName = null,
        array $metadata = []
    ) {
        $this->recipients = $recipients;
        $this->subject = $subject;
        $this->content = $content;
        $this->reportData = $reportData;
        $this->template = $template;
        $this->attachments = $attachments;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
        $this->metadata = $metadata;
    }

    /**
     * Tạo DTO từ array
     *
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        return new static(
            recipients: $data['recipients'] ?? [],
            subject: $data['subject'] ?? '',
            content: $data['content'] ?? '',
            reportData: $data['report_data'] ?? [],
            template: $data['template'] ?? 'emails.reports.default',
            attachments: $data['attachments'] ?? [],
            fromEmail: $data['from_email'] ?? null,
            fromName: $data['from_name'] ?? null,
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Chuyển đổi thành array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'recipients' => $this->recipients,
            'subject' => $this->subject,
            'content' => $this->content,
            'report_data' => $this->reportData,
            'template' => $this->template,
            'attachments' => $this->attachments,
            'from_email' => $this->fromEmail,
            'from_name' => $this->fromName,
            'metadata' => $this->metadata
        ];
    }

    /**
     * Kiểm tra tính hợp lệ của DTO
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return !empty($this->recipients) && 
               !empty($this->subject) && 
               !empty($this->content);
    }

    /**
     * Lấy danh sách email recipients
     *
     * @return array
     */
    public function getEmailRecipients(): array
    {
        $emails = [];
        foreach ($this->recipients as $recipient) {
            if (is_string($recipient)) {
                $emails[] = $recipient;
            } elseif (is_array($recipient) && isset($recipient['email'])) {
                $emails[] = $recipient['email'];
            }
        }
        return array_unique($emails);
    }

    /**
     * Thêm attachment
     *
     * @param string $path
     * @param string $name
     * @return void
     */
    public function addAttachment(string $path, string $name = ''): void
    {
        $this->attachments[] = [
            'path' => $path,
            'name' => $name ?: basename($path)
        ];
    }

    /**
     * Thêm metadata
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function addMetadata(string $key, mixed $value): void
    {
        $this->metadata[$key] = $value;
    }
}
