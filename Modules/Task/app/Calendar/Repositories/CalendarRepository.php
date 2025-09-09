<?php

namespace Modules\Task\app\Calendar\Repositories;

use Modules\Task\app\Calendar\Contracts\CalendarRepositoryInterface;
use Modules\Task\app\Models\Calendar;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Calendar Repository Implementation
 * 
 * Repository layer theo Clean Architecture
 * Xử lý data access cho Calendar
 */
class CalendarRepository implements CalendarRepositoryInterface
{
    /**
     * Get events by date range
     */
    public function getEventsByRange(string $start, string $end, int $userId, string $userType): array
    {
        return Calendar::whereBetween('start_time', [$start, $end])
            ->where('creator_id', $userId)
            ->where('creator_type', $userType)
            ->with('task')
            ->get()
            ->toArray();
    }

    /**
     * Get upcoming events
     */
    public function getUpcomingEvents(int $userId, string $userType, int $limit = 10): array
    {
        return Calendar::where('start_time', '>', Carbon::now())
            ->where('creator_id', $userId)
            ->where('creator_type', $userType)
            ->with('task')
            ->orderBy('start_time', 'asc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get overdue events
     */
    public function getOverdueEvents(int $userId, string $userType): array
    {
        return Calendar::where('end_time', '<', Carbon::now())
            ->where('creator_id', $userId)
            ->where('creator_type', $userType)
            ->with('task')
            ->orderBy('end_time', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get events count by status
     */
    public function getEventsCountByStatus(int $userId, string $userType): array
    {
        $result = Calendar::where('creator_id', $userId)
            ->where('creator_type', $userType)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN start_time > NOW() THEN 1 ELSE 0 END) as upcoming,
                SUM(CASE WHEN end_time < NOW() THEN 1 ELSE 0 END) as overdue,
                SUM(CASE WHEN start_time <= NOW() AND end_time >= NOW() THEN 1 ELSE 0 END) as ongoing
            ')
            ->first();

        return $result ? $result->toArray() : [
            'total' => 0,
            'upcoming' => 0,
            'overdue' => 0,
            'ongoing' => 0
        ];
    }

    /**
     * Get all events (Admin)
     */
    public function getAllEvents(int $page = 1, int $perPage = 15): array
    {
        $events = Calendar::with('task')
            ->orderBy('start_time', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $events->items(),
            'pagination' => [
                'current_page' => $events->currentPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
                'last_page' => $events->lastPage()
            ]
        ];
    }

    /**
     * Get events by type
     */
    public function getEventsByType(string $type): array
    {
        return Calendar::where('event_type', $type)
            ->with('task')
            ->orderBy('start_time', 'desc')
            ->get()
            ->toArray();
    }
}
