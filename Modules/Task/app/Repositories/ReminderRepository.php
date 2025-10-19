<?php

declare(strict_types=1);

namespace Modules\Task\app\Repositories;

use Modules\Task\app\Models\Reminder;
use Modules\Task\app\Repositories\Interfaces\ReminderRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reminder Repository
 * 
 * Handles reminder data access operations
 */
class ReminderRepository implements ReminderRepositoryInterface
{
    public function findById(int $id): ?Reminder
    {
        return Reminder::with(['task', 'user'])->find($id);
    }

    public function create(array $data): Reminder
    {
        return Reminder::create($data);
    }

    public function update(Reminder $reminder, array $data): Reminder
    {
        $reminder->update($data);
        return $reminder->fresh();
    }

    public function delete(Reminder $reminder): bool
    {
        return $reminder->delete();
    }

    public function getUserReminders(int $userId, string $userType, array $filters = []): array
    {
        $query = Reminder::with(['task', 'user'])
            ->byUser($userId, $userType);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['reminder_type'])) {
            $query->where('reminder_type', $filters['reminder_type']);
        }

        if (isset($filters['start_date'])) {
            $query->where('reminder_time', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('reminder_time', '<=', $filters['end_date']);
        }

        if (isset($filters['task_id'])) {
            $query->where('task_id', $filters['task_id']);
        }

        // Order by reminder time
        $query->orderBy('reminder_time', 'asc');

        // Pagination
        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;

        $reminders = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $reminders->items(),
            'pagination' => [
                'current_page' => $reminders->currentPage(),
                'per_page' => $reminders->perPage(),
                'total' => $reminders->total(),
                'last_page' => $reminders->lastPage()
            ]
        ];
    }

    public function getDueReminders(): Collection
    {
        return Reminder::with(['task', 'user'])
            ->pending()
            ->where('reminder_time', '<=', now())
            ->get();
    }

    public function getTaskReminders(int $taskId): Collection
    {
        return Reminder::with(['user'])
            ->where('task_id', $taskId)
            ->orderBy('reminder_time', 'asc')
            ->get();
    }

    public function getRemindersByType(string $type): Collection
    {
        return Reminder::with(['task', 'user'])
            ->byType($type)
            ->orderBy('reminder_time', 'asc')
            ->get();
    }

    public function getPendingReminders(): Collection
    {
        return Reminder::with(['task', 'user'])
            ->where('status', 'pending')
            ->orderBy('reminder_time', 'asc')
            ->get();
    }

    public function getFailedReminders(): Collection
    {
        return Reminder::with(['task', 'user'])
            ->where('status', 'failed')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getRemindersByDateRange(string $startDate, string $endDate): Collection
    {
        return Reminder::with(['task', 'user'])
            ->whereBetween('reminder_time', [$startDate, $endDate])
            ->orderBy('reminder_time', 'asc')
            ->get();
    }

    public function countRemindersByStatus(string $status): int
    {
        return Reminder::where('status', $status)->count();
    }

    public function getReminderStatistics(int $userId, string $userType): array
    {
        $totalReminders = Reminder::byUser($userId, $userType)->count();
        $pendingReminders = Reminder::byUser($userId, $userType)
            ->where('status', 'pending')->count();
        $sentReminders = Reminder::byUser($userId, $userType)
            ->where('status', 'sent')->count();
        $failedReminders = Reminder::byUser($userId, $userType)
            ->where('status', 'failed')->count();

        // Reminders by type
        $remindersByType = Reminder::byUser($userId, $userType)
            ->selectRaw('reminder_type, COUNT(*) as count')
            ->groupBy('reminder_type')
            ->pluck('count', 'reminder_type')
            ->toArray();

        // Recent reminders (last 30 days)
        $recentReminders = Reminder::byUser($userId, $userType)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        return [
            'total_reminders' => $totalReminders,
            'pending_reminders' => $pendingReminders,
            'sent_reminders' => $sentReminders,
            'failed_reminders' => $failedReminders,
            'reminders_by_type' => $remindersByType,
            'recent_reminders' => $recentReminders,
            'success_rate' => $totalReminders > 0 ? 
                round(($sentReminders / $totalReminders) * 100, 2) : 0
        ];
    }
}
