<?php

namespace Modules\Task\app\Lecturer\Repositories;

use Modules\Task\app\Lecturer\Exceptions\LecturerTaskException;
use Illuminate\Support\Facades\Log;

/**
 * Lecturer Calendar Repository
 */
class LecturerCalendarRepository
{
    public function getLecturerEvents($lecturerId, $filters = [])
    {
        try {
            // Lấy tasks của lecturer với pagination
            $page = $filters['page'] ?? 1;
            $perPage = $filters['per_page'] ?? 15;
            
            $tasks = \Modules\Task\app\Models\Task::where('creator_id', $lecturerId)
                ->where('creator_type', 'lecturer')
                ->with(['receivers'])
                ->orderBy('deadline', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);
            
            $events = $tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'start' => $task->deadline,
                    'end' => $task->deadline,
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
        } catch (\Exception $e) {
            Log::error('LecturerCalendarRepository: Error getting lecturer events', [
                'lecturer_id' => $lecturerId,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw new LecturerTaskException('Failed to retrieve lecturer events: ' . $e->getMessage(), 500);
        }
    }

    public function createEvent($data)
    {
        try {
            $event = new \Modules\Task\app\Models\Calendar();
            $event->title = $data['title'] ?? 'Event';
            $event->description = $data['description'] ?? '';
            $event->start_time = $data['start_time'] ?? now();
            $event->end_time = $data['end_time'] ?? now()->addHour();
            $event->creator_id = $data['creator_id'] ?? 1;
            $event->creator_type = $data['creator_type'] ?? 'lecturer';
            $event->event_type = $data['event_type'] ?? 'event';
            $event->save();
            
            return [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'start_time' => $event->start_time,
                'end_time' => $event->end_time,
                'creator_id' => $event->creator_id,
                'creator_type' => $event->creator_type
            ];
        } catch (\Exception $e) {
            Log::error('LecturerCalendarRepository: Error creating event', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw new LecturerTaskException('Failed to create event: ' . $e->getMessage(), 500);
        }
    }

    public function updateEvent($eventId, $data, $lecturerId, $userType)
    {
        try {
            // Mock implementation
            return [
                'id' => $eventId,
                'title' => $data['title'] ?? 'Updated Event',
                'description' => $data['description'] ?? '',
                'updated_at' => now()
            ];
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to update event: ' . $e->getMessage(), 500);
        }
    }

    public function deleteEvent($eventId, $lecturerId, $userType)
    {
        try {
            // Mock implementation
            return true;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to delete event: ' . $e->getMessage(), 500);
        }
    }

    public function getEventsByDate($lecturerId, $date)
    {
        try {
            $startDate = $date . ' 00:00:00';
            $endDate = $date . ' 23:59:59';
            
            $tasks = \Modules\Task\app\Models\Task::where('creator_id', $lecturerId)
                ->where('creator_type', 'lecturer')
                ->whereBetween('deadline', [$startDate, $endDate])
                ->with(['receivers'])
                ->get();
            
            return $tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'start' => $task->deadline,
                    'end' => $task->deadline,
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
        } catch (\Exception $e) {
            Log::error('LecturerCalendarRepository: Error getting events by date', [
                'lecturer_id' => $lecturerId,
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            throw new LecturerTaskException('Failed to retrieve events by date: ' . $e->getMessage(), 500);
        }
    }

    public function getEventsByRange($lecturerId, $startDate, $endDate)
    {
        try {
            // Lấy tasks
            $tasks = \Modules\Task\app\Models\Task::where('creator_id', $lecturerId)
                ->where('creator_type', 'lecturer')
                ->whereBetween('deadline', [$startDate, $endDate])
                ->with(['receivers'])
                ->get();
            
            // Lấy events từ bảng calendar
            $events = \Modules\Task\app\Models\Calendar::where('creator_id', $lecturerId)
                ->where('creator_type', 'lecturer')
                ->whereBetween('start_time', [$startDate, $endDate])
                ->get();
            
            $taskEvents = $tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'start' => $task->deadline,
                    'end' => $task->deadline,
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
            
            $calendarEvents = $events->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'start' => $event->start_time,
                    'end' => $event->end_time,
                    'event_type' => $event->event_type ?? 'event',
                    'task_id' => null,
                    'status' => 'scheduled',
                    'priority' => 'medium',
                    'creator' => [
                        'id' => $event->creator_id,
                        'type' => $event->creator_type
                    ]
                ];
            });
            
            // Gộp tasks và events, sắp xếp theo thời gian
            $allEvents = $taskEvents->concat($calendarEvents)->sortBy('start');
            
            return $allEvents->values()->toArray();
        } catch (\Exception $e) {
            Log::error('LecturerCalendarRepository: Error getting events by range', [
                'lecturer_id' => $lecturerId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'error' => $e->getMessage()
            ]);
            throw new LecturerTaskException('Failed to retrieve events by range: ' . $e->getMessage(), 500);
        }
    }

    public function getUpcomingEvents($lecturerId, $limit = 10)
    {
        try {
            $startDate = now()->format('Y-m-d H:i:s');
            $endDate = now()->addDays(30)->format('Y-m-d H:i:s');
            
            $tasks = \Modules\Task\app\Models\Task::where('creator_id', $lecturerId)
                ->where('creator_type', 'lecturer')
                ->whereBetween('deadline', [$startDate, $endDate])
                ->with(['receivers'])
                ->orderBy('deadline', 'asc')
                ->limit($limit)
                ->get();
            
            return $tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'start' => $task->deadline,
                    'end' => $task->deadline,
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
        } catch (\Exception $e) {
            Log::error('LecturerCalendarRepository: Error getting upcoming events', [
                'lecturer_id' => $lecturerId,
                'limit' => $limit,
                'error' => $e->getMessage()
            ]);
            throw new LecturerTaskException('Failed to retrieve upcoming events: ' . $e->getMessage(), 500);
        }
    }

    public function getOverdueEvents($lecturerId)
    {
        try {
            $endDate = now()->format('Y-m-d H:i:s');
            
            $tasks = \Modules\Task\app\Models\Task::where('creator_id', $lecturerId)
                ->where('creator_type', 'lecturer')
                ->where('deadline', '<', $endDate)
                ->where('status', '!=', 'completed')
                ->with(['receivers'])
                ->orderBy('deadline', 'desc')
                ->get();
            
            return $tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'start' => $task->deadline,
                    'end' => $task->deadline,
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
        } catch (\Exception $e) {
            Log::error('LecturerCalendarRepository: Error getting overdue events', [
                'lecturer_id' => $lecturerId,
                'error' => $e->getMessage()
            ]);
            throw new LecturerTaskException('Failed to retrieve overdue events: ' . $e->getMessage(), 500);
        }
    }

    public function getEventsCountByStatus($lecturerId)
    {
        try {
            $tasks = \Modules\Task\app\Models\Task::where('creator_id', $lecturerId)
                ->where('creator_type', 'lecturer')
                ->get();

            $counts = [
                'total' => $tasks->count(),
                'pending' => $tasks->where('status', 'pending')->count(),
                'completed' => $tasks->where('status', 'completed')->count(),
                'overdue' => $tasks->where('deadline', '<', now())->where('status', '!=', 'completed')->count()
            ];
            
            return $counts;
        } catch (\Exception $e) {
            Log::error('LecturerCalendarRepository: Error getting events count by status', [
                'lecturer_id' => $lecturerId,
                'error' => $e->getMessage()
            ]);
            throw new LecturerTaskException('Failed to retrieve events count by status: ' . $e->getMessage(), 500);
        }
    }
}
