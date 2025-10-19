<?php

declare(strict_types=1);

namespace Modules\Task\app\Repositories\Interfaces;

use Modules\Task\app\Models\Reminder;
use Illuminate\Support\Collection;

/**
 * Reminder Repository Interface
 * 
 * Defines contract for reminder data access operations
 */
interface ReminderRepositoryInterface
{
    /**
     * Find reminder by ID
     */
    public function findById(int $id): ?Reminder;

    /**
     * Create new reminder
     */
    public function create(array $data): Reminder;

    /**
     * Update reminder
     */
    public function update(Reminder $reminder, array $data): Reminder;

    /**
     * Delete reminder
     */
    public function delete(Reminder $reminder): bool;

    /**
     * Get reminders for user
     */
    public function getUserReminders(int $userId, string $userType, array $filters = []): array;

    /**
     * Get due reminders
     */
    public function getDueReminders(): Collection;

    /**
     * Get reminders by task
     */
    public function getTaskReminders(int $taskId): Collection;

    /**
     * Get reminders by type
     */
    public function getRemindersByType(string $type): Collection;

    /**
     * Get pending reminders
     */
    public function getPendingReminders(): Collection;

    /**
     * Get failed reminders
     */
    public function getFailedReminders(): Collection;

    /**
     * Get reminders by date range
     */
    public function getRemindersByDateRange(string $startDate, string $endDate): Collection;

    /**
     * Count reminders by status
     */
    public function countRemindersByStatus(string $status): int;

    /**
     * Get reminder statistics
     */
    public function getReminderStatistics(int $userId, string $userType): array;
}
