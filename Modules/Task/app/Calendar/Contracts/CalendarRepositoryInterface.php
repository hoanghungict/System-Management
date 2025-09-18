<?php

namespace Modules\Task\app\Calendar\Contracts;

/**
 * Calendar Repository Contract
 * 
 * Interface định nghĩa các method cần thiết cho Calendar operations
 */
interface CalendarRepositoryInterface
{
    /**
     * Get events by date range
     */
    public function getEventsByRange(string $start, string $end, int $userId, string $userType): array;

    /**
     * Get upcoming events
     */
    public function getUpcomingEvents(int $userId, string $userType, int $limit = 10): array;

    /**
     * Get overdue events
     */
    public function getOverdueEvents(int $userId, string $userType): array;

    /**
     * Get events count by status
     */
    public function getEventsCountByStatus(int $userId, string $userType): array;

    /**
     * Get all events (Admin)
     */
    public function getAllEvents(int $page = 1, int $perPage = 15): array;

    /**
     * Get events by type
     */
    public function getEventsByType(string $type): array;
}
