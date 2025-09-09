<?php

namespace Modules\Notifications\app\Services\EmailService;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;
use Modules\Notifications\app\Jobs\SendEmailNotificationJob;

class EmailService implements EmailServiceInterface
{
    /**
     * Gửi email notification
     */
    public function send(
        int $userId,
        string $userType,
        string $content,
        string $subject = 'Notification'
    ): bool {
        try {
            // Queue email để xử lý background
            SendEmailNotificationJob::dispatch($userId, $userType, $content, $subject)
                ->onQueue('emails');

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to queue email notification', [
                'user_id' => $userId,
                'user_type' => $userType,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Gửi email trực tiếp (không qua queue)
     */
    public function sendImmediate(
        int $userId,
        string $userType,
        string $content,
        string $subject = 'Notification'
    ): bool {
        try {
            // Lấy email của user (implement logic lấy email)
            $userEmail = $this->getUserEmail($userId, $userType);
            
            if (!$userEmail) {
                Log::warning('User email not found', ['user_id' => $userId]);
                return false;
            }

            // Gửi email
            Mail::raw($content, function ($message) use ($userEmail, $subject) {
                $message->to($userEmail)
                        ->subject($subject);
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send immediate email', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Gửi email với template
     */
    public function sendTemplateEmail(
        string $to,
        string $template,
        array $data,
        array $attachments = []
    ): bool {
        try {
            Log::info('EmailService: Sending template email', [
                'to' => $to,
                'template' => $template
            ]);

            // Validate email
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                Log::error('EmailService: Invalid email address', ['email' => $to]);
                return false;
            }

            // Gửi email với template
            Mail::send($template, $data, function ($message) use ($to, $data, $attachments) {
                $message->to($to)
                        ->subject($data['subject'] ?? 'Notification');

                // Thêm attachments nếu có
                foreach ($attachments as $attachment) {
                    if (isset($attachment['path']) && file_exists($attachment['path'])) {
                        $message->attach($attachment['path'], [
                            'as' => $attachment['name'] ?? basename($attachment['path'])
                        ]);
                    }
                }
            });

            Log::info('EmailService: Template email sent successfully', ['to' => $to]);
            return true;

        } catch (\Exception $e) {
            Log::error('EmailService: Failed to send template email', [
                'to' => $to,
                'template' => $template,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Gửi email hàng loạt
     */
    public function sendBulkEmail(
        array $recipients,
        string $subject,
        string $content,
        array $attachments = []
    ): bool {
        try {
            Log::info('EmailService: Sending bulk email', [
                'recipients_count' => count($recipients)
            ]);

            $successCount = 0;
            $failedCount = 0;

            foreach ($recipients as $recipient) {
                $email = is_string($recipient) ? $recipient : ($recipient['email'] ?? null);
                
                if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $sent = $this->sendNotificationEmail($email, $subject, $content, $attachments);
                    if ($sent) {
                        $successCount++;
                    } else {
                        $failedCount++;
                    }
                } else {
                    $failedCount++;
                    Log::warning('EmailService: Invalid recipient email', ['recipient' => $recipient]);
                }
            }

            Log::info('EmailService: Bulk email completed', [
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'total_count' => count($recipients)
            ]);

            return $successCount > 0;

        } catch (\Exception $e) {
            Log::error('EmailService: Failed to send bulk email', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Gửi email thông báo với attachments
     */
    public function sendNotificationEmail(
        string $to,
        string $subject,
        string $content,
        array $attachments = []
    ): bool {
        try {
            Log::info('EmailService: Sending notification email', [
                'to' => $to,
                'subject' => $subject
            ]);

            // Validate email
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                Log::error('EmailService: Invalid email address', ['email' => $to]);
                return false;
            }

            // Gửi email trực tiếp
            Mail::raw($content, function ($message) use ($to, $subject, $attachments) {
                $message->to($to)
                        ->subject($subject);

                // Thêm attachments nếu có
                foreach ($attachments as $attachment) {
                    if (isset($attachment['path']) && file_exists($attachment['path'])) {
                        $message->attach($attachment['path'], [
                            'as' => $attachment['name'] ?? basename($attachment['path'])
                        ]);
                    }
                }
            });

            Log::info('EmailService: Notification email sent successfully', ['to' => $to]);
            return true;

        } catch (\Exception $e) {
            Log::error('EmailService: Failed to send notification email', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Kiểm tra kết nối email
     */
    public function testConnection(): bool
    {
        try {
            // Kiểm tra cấu hình email
            $config = config('mail');
            if (empty($config)) {
                Log::error('EmailService: Mail configuration not found');
                return false;
            }

            // Test connection bằng cách gửi email test
            $testEmail = config('mail.test_email', 'test@example.com');
            
            Mail::raw('Test email connection', function ($message) use ($testEmail) {
                $message->to($testEmail)
                        ->subject('Test Connection');
            });

            Log::info('EmailService: Connection test successful');
            return true;

        } catch (\Exception $e) {
            Log::error('EmailService: Connection test failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Gửi email báo cáo (cho Task module)
     */
    public function sendReportEmail(array $emailData): bool
    {
        try {
            Log::info('EmailService: Sending report email', [
                'recipients_count' => count($emailData['recipients'] ?? []),
                'subject' => $emailData['subject'] ?? 'Report'
            ]);

            $recipients = $emailData['recipients'] ?? [];
            $subject = $emailData['subject'] ?? 'Report';
            $content = $emailData['content'] ?? '';
            $template = $emailData['template'] ?? null;
            $attachments = $emailData['attachments'] ?? [];

            if (empty($recipients)) {
                Log::error('EmailService: No recipients provided');
                return false;
            }

            $successCount = 0;
            foreach ($recipients as $recipient) {
                $email = is_string($recipient) ? $recipient : ($recipient['email'] ?? null);
                
                if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    if ($template) {
                        $sent = $this->sendTemplateEmail($email, $template, $emailData['data'] ?? [], $attachments);
                    } else {
                        $sent = $this->sendNotificationEmail($email, $subject, $content, $attachments);
                    }
                    
                    if ($sent) {
                        $successCount++;
                    }
                }
            }

            Log::info('EmailService: Report email completed', [
                'success_count' => $successCount,
                'total_count' => count($recipients)
            ]);

            return $successCount > 0;

        } catch (\Exception $e) {
            Log::error('EmailService: Failed to send report email', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Lấy email của user
     */
    public function getUserEmail(int $userId, string $userType): ?string
    {
        // Implement logic lấy email dựa trên user_type
        // Có thể query từ các bảng khác nhau
        return match($userType) {
            'student' => $this->getStudentEmail($userId),
            'lecturer' => $this->getLecturerEmail($userId),
            'admin' => $this->getAdminEmail($userId),
            default => null
        };
    }

    private function getStudentEmail(int $userId): ?string
    {
        // Query từ bảng student (vì email nằm trong bảng student)
        return DB::table('student')
            ->where('id', $userId)
            ->value('email');
    }

    private function getLecturerEmail(int $userId): ?string
    {
        // Query từ bảng lecturer (vì email nằm trong bảng lecturer)
        return DB::table('lecturer')
            ->where('id', $userId)
            ->value('email');
    }

    private function getAdminEmail(int $userId): ?string
    {
        // Query từ bảng users
        return DB::table('users')
            ->where('id', $userId)
            ->value('email');
    }
}
