<?php

namespace Modules\Notifications\app\Services\EmailService;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;
use Modules\Notifications\app\Jobs\SendEmailNotificationJob;

class EmailService
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
