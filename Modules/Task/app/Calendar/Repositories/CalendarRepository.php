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
        // Calendar events nên hiển thị cho người nhận task, không phải người tạo
        // Sử dụng Task model để lấy tasks có deadline trong khoảng thời gian
        $tasks = \Modules\Task\app\Models\Task::whereBetween('deadline', [$start, $end])
            ->whereHas('receivers', function ($query) use ($userId, $userType) {
                $query->where('receiver_id', $userId)
                      ->where('receiver_type', $userType);
            })
            ->with(['creator', 'receivers'])
            ->get();
            
        return $tasks->map(function ($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'start_time' => $task->deadline,
                'end_time' => $task->deadline,
                'event_type' => 'task',
                'task_id' => $task->id,
                'status' => $task->status,
                'priority' => $task->priority,
                'creator' => [
                    'id' => $task->creator_id,
                    'type' => $task->creator_type
                ]
            ];
        })->toArray();
    }

    /**
     * Get upcoming events
     */
    public function getUpcomingEvents(int $userId, string $userType, int $limit = 10): array
    {
        $tasks = \Modules\Task\app\Models\Task::where('deadline', '>', Carbon::now())
            ->whereHas('receivers', function ($query) use ($userId, $userType) {
                $query->where('receiver_id', $userId)
                      ->where('receiver_type', $userType);
            })
            ->with(['creator', 'receivers'])
            ->orderBy('deadline', 'asc')
            ->limit($limit)
            ->get();
            
        return $tasks->map(function ($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'start_time' => $task->deadline,
                'end_time' => $task->deadline,
                'event_type' => 'task',
                'task_id' => $task->id,
                'status' => $task->status,
                'priority' => $task->priority,
                'creator' => [
                    'id' => $task->creator_id,
                    'type' => $task->creator_type
                ]
            ];
        })->toArray();
    }

    /**
     * Get overdue events
     */
    public function getOverdueEvents(int $userId, string $userType): array
    {
        $tasks = \Modules\Task\app\Models\Task::where('deadline', '<', Carbon::now())
            ->where('status', '!=', 'completed')
            ->whereHas('receivers', function ($query) use ($userId, $userType) {
                $query->where('receiver_id', $userId)
                      ->where('receiver_type', $userType);
            })
            ->with(['creator', 'receivers'])
            ->orderBy('deadline', 'desc')
            ->get();
            
        return $tasks->map(function ($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'start_time' => $task->deadline,
                'end_time' => $task->deadline,
                'event_type' => 'task',
                'task_id' => $task->id,
                'status' => $task->status,
                'priority' => $task->priority,
                'creator' => [
                    'id' => $task->creator_id,
                    'type' => $task->creator_type
                ]
            ];
        })->toArray();
    }

    /**
     * Get events count by status
     */
    public function getEventsCountByStatus(int $userId, string $userType): array
    {
        $tasks = \Modules\Task\app\Models\Task::whereHas('receivers', function ($query) use ($userId, $userType) {
            $query->where('receiver_id', $userId)
                  ->where('receiver_type', $userType);
        })->get();

        $total = $tasks->count();
        $upcoming = $tasks->where('deadline', '>', Carbon::now())->count();
        $overdue = $tasks->where('deadline', '<', Carbon::now())->where('status', '!=', 'completed')->count();
        $ongoing = $tasks->where('status', 'pending')->count();

        return [
            'total' => $total,
            'upcoming' => $upcoming,
            'overdue' => $overdue,
            'ongoing' => $ongoing
        ];
    }

    /**
     * Get all events (Admin)
     */
    public function getAllEvents(int $page = 1, int $perPage = 15): array
    {
        $tasks = \Modules\Task\app\Models\Task::with(['creator', 'receivers'])
            ->orderBy('deadline', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $events = $tasks->map(function ($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'start_time' => $task->deadline,
                'end_time' => $task->deadline,
                'event_type' => 'task',
                'task_id' => $task->id,
                'status' => $task->status,
                'priority' => $task->priority,
                'creator' => [
                    'id' => $task->creator_id,
                    'type' => $task->creator_type
                ]
            ];
        });

        return [
            'data' => $events,
            'pagination' => [
                'current_page' => $tasks->currentPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
                'last_page' => $tasks->lastPage()
            ]
        ];
    }

    /**
     * Get events by type
     */
    public function getEventsByType(string $type): array
    {
        $tasks = \Modules\Task\app\Models\Task::where('priority', $type)
            ->with(['creator', 'receivers'])
            ->orderBy('deadline', 'desc')
            ->get();
            
        return $tasks->map(function ($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'start_time' => $task->deadline,
                'end_time' => $task->deadline,
                'event_type' => 'task',
                'task_id' => $task->id,
                'status' => $task->status,
                'priority' => $task->priority,
                'creator' => [
                    'id' => $task->creator_id,
                    'type' => $task->creator_type
                ]
            ];
        })->toArray();
    }
}
