<?php

namespace Modules\Task\app\Repositories;

use Modules\Task\app\Repositories\Interfaces\EmailRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmailRepository implements EmailRepositoryInterface
{
    private const EMAIL_LOGS_TABLE = 'email_logs';
    private const EMAIL_TEMPLATES_TABLE = 'email_templates';

    /**
     * Lưu log hoạt động email
     *
     * @param array $data
     * @return bool
     */
    public function logEmailActivity(array $data): bool
    {
        try {
            $logData = [
                'recipients' => json_encode($data['recipients'] ?? []),
                'subject' => $data['subject'] ?? '',
                'template' => $data['template'] ?? '',
                'success' => $data['success'] ?? false,
                'error' => $data['error'] ?? null,
                'sent_at' => $data['sent_at'] ?? now(),
                'metadata' => json_encode($data['metadata'] ?? []),
                'created_at' => now(),
                'updated_at' => now()
            ];

            DB::table(self::EMAIL_LOGS_TABLE)->insert($logData);

            Log::info('EmailRepository: Email activity logged successfully', [
                'recipients_count' => count($data['recipients'] ?? []),
                'success' => $data['success'] ?? false
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('EmailRepository: Failed to log email activity', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Lấy danh sách email logs
     *
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getEmailLogs(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        try {
            $query = DB::table(self::EMAIL_LOGS_TABLE);

            // Áp dụng filters
            if (isset($filters['success'])) {
                $query->where('success', $filters['success']);
            }

            if (isset($filters['template'])) {
                $query->where('template', $filters['template']);
            }

            if (isset($filters['date_from'])) {
                $query->where('sent_at', '>=', $filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $query->where('sent_at', '<=', $filters['date_to']);
            }

            $logs = $query->orderBy('sent_at', 'desc')
                         ->limit($limit)
                         ->offset($offset)
                         ->get()
                         ->toArray();

            // Decode JSON fields
            foreach ($logs as &$log) {
                $log->recipients = json_decode($log->recipients, true);
                $log->metadata = json_decode($log->metadata, true);
            }

            return $logs;

        } catch (\Exception $e) {
            Log::error('EmailRepository: Failed to get email logs', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Lấy thống kê email
     *
     * @param array $filters
     * @return array
     */
    public function getEmailStatistics(array $filters = []): array
    {
        try {
            $query = DB::table(self::EMAIL_LOGS_TABLE);

            // Áp dụng filters
            if (isset($filters['date_from'])) {
                $query->where('sent_at', '>=', $filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $query->where('sent_at', '<=', $filters['date_to']);
            }

            $stats = [
                'total_sent' => $query->count(),
                'successful' => $query->where('success', true)->count(),
                'failed' => $query->where('success', false)->count(),
                'success_rate' => 0,
                'by_template' => [],
                'by_date' => []
            ];

            // Tính tỷ lệ thành công
            if ($stats['total_sent'] > 0) {
                $stats['success_rate'] = round(($stats['successful'] / $stats['total_sent']) * 100, 2);
            }

            // Thống kê theo template
            $templateStats = DB::table(self::EMAIL_LOGS_TABLE)
                ->select('template', DB::raw('count(*) as count'))
                ->groupBy('template')
                ->get();

            foreach ($templateStats as $template) {
                $stats['by_template'][$template->template] = $template->count;
            }

            // Thống kê theo ngày
            $dateStats = DB::table(self::EMAIL_LOGS_TABLE)
                ->select(DB::raw('DATE(sent_at) as date'), DB::raw('count(*) as count'))
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->limit(30)
                ->get();

            foreach ($dateStats as $date) {
                $stats['by_date'][$date->date] = $date->count;
            }

            return $stats;

        } catch (\Exception $e) {
            Log::error('EmailRepository: Failed to get email statistics', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Lưu template email
     *
     * @param array $data
     * @return bool
     */
    public function saveEmailTemplate(array $data): bool
    {
        try {
            $templateData = [
                'name' => $data['name'] ?? '',
                'subject' => $data['subject'] ?? '',
                'content' => $data['content'] ?? '',
                'variables' => json_encode($data['variables'] ?? []),
                'is_active' => $data['is_active'] ?? true,
                'created_at' => now(),
                'updated_at' => now()
            ];

            // Kiểm tra template đã tồn tại
            $existing = DB::table(self::EMAIL_TEMPLATES_TABLE)
                ->where('name', $templateData['name'])
                ->first();

            if ($existing) {
                // Update existing template
                DB::table(self::EMAIL_TEMPLATES_TABLE)
                    ->where('name', $templateData['name'])
                    ->update([
                        'subject' => $templateData['subject'],
                        'content' => $templateData['content'],
                        'variables' => $templateData['variables'],
                        'is_active' => $templateData['is_active'],
                        'updated_at' => now()
                    ]);
            } else {
                // Insert new template
                DB::table(self::EMAIL_TEMPLATES_TABLE)->insert($templateData);
            }

            Log::info('EmailRepository: Email template saved successfully', [
                'name' => $templateData['name']
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('EmailRepository: Failed to save email template', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Lấy template email
     *
     * @param string $name
     * @return array|null
     */
    public function getEmailTemplate(string $name): ?array
    {
        try {
            $template = DB::table(self::EMAIL_TEMPLATES_TABLE)
                ->where('name', $name)
                ->where('is_active', true)
                ->first();

            if (!$template) {
                return null;
            }

            return [
                'name' => $template->name,
                'subject' => $template->subject,
                'content' => $template->content,
                'variables' => json_decode($template->variables, true)
            ];

        } catch (\Exception $e) {
            Log::error('EmailRepository: Failed to get email template', [
                'name' => $name,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Xóa email log cũ
     *
     * @param int $daysOld
     * @return int
     */
    public function cleanOldEmailLogs(int $daysOld = 30): int
    {
        try {
            $cutoffDate = now()->subDays($daysOld);

            $deletedCount = DB::table(self::EMAIL_LOGS_TABLE)
                ->where('sent_at', '<', $cutoffDate)
                ->delete();

            Log::info('EmailRepository: Cleaned old email logs', [
                'deleted_count' => $deletedCount,
                'days_old' => $daysOld
            ]);

            return $deletedCount;

        } catch (\Exception $e) {
            Log::error('EmailRepository: Failed to clean old email logs', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
}
