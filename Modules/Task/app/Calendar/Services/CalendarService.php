<?php

namespace Modules\Task\app\Calendar\Services;

use Modules\Task\app\Calendar\Contracts\CalendarRepositoryInterface;
use Illuminate\Support\Facades\Log;

/**
 * Calendar Service
 * 
 * Service layer theo Clean Architecture
 * Xử lý business logic cho Calendar
 */
class CalendarService
{
    public function __construct(
        private CalendarRepositoryInterface $calendarRepository
    ) {}

    /**
     * Get events by date
     */
    public function getEventsByDate(string $date, int $userId, string $userType): array
    {
        try {
            $start = $date . ' 00:00:00';
            $end = $date . ' 23:59:59';
            
            return $this->calendarRepository->getEventsByRange($start, $end, $userId, $userType);
        } catch (\Exception $e) {
            Log::error('Error getting events by date: ' . $e->getMessage(), [
                'date' => $date,
                'user_id' => $userId,
                'user_type' => $userType
            ]);
            return [];
        }
    }

    /**
     * Get events by range
     */
    public function getEventsByRange(string $start, string $end, int $userId, string $userType): array
    {
        try {
            return $this->calendarRepository->getEventsByRange($start, $end, $userId, $userType);
        } catch (\Exception $e) {
            Log::error('Error getting events by range: ' . $e->getMessage(), [
                'start' => $start,
                'end' => $end,
                'user_id' => $userId,
                'user_type' => $userType
            ]);
            return [];
        }
    }

    /**
     * Get upcoming events
     */
    public function getUpcomingEvents(int $userId, string $userType, int $limit = 10): array
    {
        try {
            return $this->calendarRepository->getUpcomingEvents($userId, $userType, $limit);
        } catch (\Exception $e) {
            Log::error('Error getting upcoming events: ' . $e->getMessage(), [
                'user_id' => $userId,
                'user_type' => $userType,
                'limit' => $limit
            ]);
            return [];
        }
    }

    /**
     * Get overdue events
     */
    public function getOverdueEvents(int $userId, string $userType): array
    {
        try {
            return $this->calendarRepository->getOverdueEvents($userId, $userType);
        } catch (\Exception $e) {
            Log::error('Error getting overdue events: ' . $e->getMessage(), [
                'user_id' => $userId,
                'user_type' => $userType
            ]);
            return [];
        }
    }

    /**
     * Get events count by status
     */
    public function getEventsCountByStatus(int $userId, string $userType): array
    {
        try {
            return $this->calendarRepository->getEventsCountByStatus($userId, $userType);
        } catch (\Exception $e) {
            Log::error('Error getting events count by status: ' . $e->getMessage(), [
                'user_id' => $userId,
                'user_type' => $userType
            ]);
            return [
                'total' => 0,
                'upcoming' => 0,
                'overdue' => 0,
                'ongoing' => 0
            ];
        }
    }

    /**
     * Get all events (Admin)
     */
    public function getAllEvents(int $page = 1, int $perPage = 15): array
    {
        try {
            return $this->calendarRepository->getAllEvents($page, $perPage);
        } catch (\Exception $e) {
            Log::error('Error getting all events: ' . $e->getMessage(), [
                'page' => $page,
                'per_page' => $perPage
            ]);
            return [
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'last_page' => 1
                ]
            ];
        }
    }

    /**
     * Get events by type
     */
    public function getEventsByType(string $type): array
    {
        try {
            return $this->calendarRepository->getEventsByType($type);
        } catch (\Exception $e) {
            Log::error('Error getting events by type: ' . $e->getMessage(), [
                'type' => $type
            ]);
            return [];
        }
    }

    /**
     * Get reminders
     */
    public function getReminders(int $userId, string $userType): array
    {
        // Tạm thời trả về empty array vì chưa có reminder system
        return [];
    }

    /**
     * Set reminder
     */
    public function setReminder(int $taskId, string $reminderTime): bool
    {
        // Tạm thời trả về true vì chưa có reminder system
        return true;
    }
}
