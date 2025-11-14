<?php

declare(strict_types=1);

namespace Modules\Task\app\Lecturer\Repositories;

use Modules\Task\app\Lecturer\Exceptions\LecturerTaskException;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Lecturer Calendar Repository
 * 
 * Repository xử lý calendar events cho Lecturer
 * Lấy cả tasks họ tạo VÀ tasks được assign cho họ
 * 
 * @package Modules\Task\app\Lecturer\Repositories
 * @version 2.0.0
 */
class LecturerCalendarRepository
{
    /**
     * Lấy events của lecturer (cả created và assigned)
     * 
     * @param int $lecturerId Lecturer ID
     * @param array $filters Filters (page, per_page, status, priority, etc.)
     * @return array Events với pagination
     */
    public function getLecturerEvents(int $lecturerId, array $filters = []): array
    {
        try {
            $page = $filters['page'] ?? 1;
            $perPage = $filters['per_page'] ?? 15;
            
            // Lấy tasks lecturer tạo HOẶC được assign
            $query = \Modules\Task\app\Models\Task::where(function ($q) use ($lecturerId) {
                // Tasks lecturer tạo
                $q->where(function ($createdQuery) use ($lecturerId) {
                    $createdQuery->where('creator_id', $lecturerId)
                                 ->where('creator_type', 'lecturer');
                })
                // HOẶC tasks được assign cho lecturer
                ->orWhereHas('receivers', function ($receiverQuery) use ($lecturerId) {
                    $receiverQuery->where('receiver_id', $lecturerId)
                                  ->where('receiver_type', 'lecturer');
                });
            })
            ->with(['creator', 'receivers'])
            ->orderBy('deadline', 'desc');
            
            // Apply filters
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            
            if (isset($filters['priority'])) {
                $query->where('priority', $filters['priority']);
            }
            
            if (isset($filters['date_from'])) {
                $query->where('deadline', '>=', $filters['date_from']);
            }
            
            if (isset($filters['date_to'])) {
                $query->where('deadline', '<=', $filters['date_to']);
            }
            
            if (isset($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            $tasks = $query->paginate($perPage, ['*'], 'page', $page);
            
            $events = $this->mapTasksToEvents($tasks->items());
            
            return [
                'data' => $events,
                'pagination' => [
                    'current_page' => $tasks->currentPage(),
                    'per_page' => $tasks->perPage(),
                    'total' => $tasks->total(),
                    'last_page' => $tasks->lastPage(),
                    'from' => $tasks->firstItem(),
                    'to' => $tasks->lastItem()
                ]
            ];
        } catch (\Exception $e) {
            Log::error('LecturerCalendarRepository: Error getting lecturer events', [
                'lecturer_id' => $lecturerId,
                'filters' => $filters,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new LecturerTaskException('Failed to retrieve lecturer events: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Tạo calendar event mới
     * 
     * @param array $data Event data
     * @return array Created event
     */
    public function createEvent(array $data): array
    {
        try {
            $event = new \Modules\Task\app\Models\Calendar();
            $event->title = $data['title'] ?? 'Event';
            $event->description = $data['description'] ?? '';
            $event->start_time = $data['start_time'] ?? now();
            $event->end_time = $data['end_time'] ?? now()->addHour();
            $event->event_type = $data['event_type'] ?? 'event';
            $event->task_id = $data['task_id'] ?? null;
            $event->creator_id = $data['creator_id'] ?? 1;
            $event->creator_type = $data['creator_type'] ?? 'lecturer';
            $event->save();
            
            return $this->mapCalendarToEvent($event);
        } catch (\Exception $e) {
            Log::error('LecturerCalendarRepository: Error creating event', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw new LecturerTaskException('Failed to create event: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cập nhật calendar event
     * 
     * @param int $eventId Event ID
     * @param array $data Update data
     * @param int $lecturerId Lecturer ID
     * @param string $userType User type
     * @return array Updated event
     */
    public function updateEvent(int $eventId, array $data, int $lecturerId, string $userType): array
    {
        try {
            $event = \Modules\Task\app\Models\Calendar::find($eventId);
            
            if (!$event) {
                throw new LecturerTaskException('Event not found', 404);
            }
            
            // Check permission
            if ($event->creator_id !== $lecturerId || $event->creator_type !== $userType) {
                throw new LecturerTaskException('Access denied', 403);
            }
            
            if (isset($data['title'])) {
                $event->title = $data['title'];
            }
            
            if (isset($data['description'])) {
                $event->description = $data['description'];
            }
            
            if (isset($data['start_time'])) {
                $event->start_time = $data['start_time'];
            }
            
            if (isset($data['end_time'])) {
                $event->end_time = $data['end_time'];
            }
            
            if (isset($data['event_type'])) {
                $event->event_type = $data['event_type'];
            }
            
            $event->save();
            
            return $this->mapCalendarToEvent($event);
        } catch (LecturerTaskException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('LecturerCalendarRepository: Error updating event', [
                'event_id' => $eventId,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw new LecturerTaskException('Failed to update event: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Xóa calendar event
     * 
     * @param int $eventId Event ID
     * @param int $lecturerId Lecturer ID
     * @param string $userType User type
     * @return bool Success
     */
    public function deleteEvent(int $eventId, int $lecturerId, string $userType): bool
    {
        try {
            $event = \Modules\Task\app\Models\Calendar::find($eventId);
            
            if (!$event) {
                throw new LecturerTaskException('Event not found', 404);
            }
            
            // Check permission
            if ($event->creator_id !== $lecturerId || $event->creator_type !== $userType) {
                throw new LecturerTaskException('Access denied', 403);
            }
            
            return $event->delete();
        } catch (LecturerTaskException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('LecturerCalendarRepository: Error deleting event', [
                'event_id' => $eventId,
                'error' => $e->getMessage()
            ]);
            throw new LecturerTaskException('Failed to delete event: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy events theo ngày
     * 
     * @param int $lecturerId Lecturer ID
     * @param string $date Date (Y-m-d)
     * @return array Events
     */
    public function getEventsByDate(int $lecturerId, string $date): array
    {
        try {
            $startDate = $date . ' 00:00:00';
            $endDate = $date . ' 23:59:59';
            
            $tasks = $this->getLecturerTasksByDateRange($lecturerId, $startDate, $endDate);
            
            return $this->mapTasksToEvents($tasks);
        } catch (\Exception $e) {
            Log::error('LecturerCalendarRepository: Error getting events by date', [
                'lecturer_id' => $lecturerId,
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            throw new LecturerTaskException('Failed to retrieve events by date: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy events theo khoảng thời gian
     * 
     * @param int $lecturerId Lecturer ID
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Events
     */
    public function getEventsByRange(int $lecturerId, string $startDate, string $endDate): array
    {
        try {
            // Ensure datetime format
            if (strlen($startDate) === 10) {
                $startDate .= ' 00:00:00';
            }
            if (strlen($endDate) === 10) {
                $endDate .= ' 23:59:59';
            }
            
            $tasks = $this->getLecturerTasksByDateRange($lecturerId, $startDate, $endDate);
            
            // Also get calendar events (non-task events)
            // Hiển thị: events lecturer tạo + events system-wide (admin tạo hoặc standalone calendar events)
            $lecturerIdForQuery = $lecturerId; // Extract to avoid IDE warnings in closures
            $calendarEvents = \Modules\Task\app\Models\Calendar::where(function ($q) use ($lecturerIdForQuery) {
                // Events do lecturer tạo
                $q->where(function ($creatorQuery) use ($lecturerIdForQuery) {
                    $creatorQuery->where('creator_id', $lecturerIdForQuery)
                                 ->where('creator_type', 'lecturer');
                })
                // HOẶC events system-wide (do admin tạo)
                ->orWhere('creator_type', 'admin')
                // HOẶC standalone calendar events (không liên kết với task = system-wide announcements)
                ->orWhereNull('task_id');
            })
            ->whereBetween('start_time', [$startDate, $endDate])
            ->get();
            
            $taskEvents = $this->mapTasksToEvents($tasks);
            $calendarEventsArray = $calendarEvents->map(function ($event) {
                return $this->mapCalendarToEvent($event);
            })->toArray();
            
            // Merge và sort
            $allEvents = array_merge($taskEvents, $calendarEventsArray);
            usort($allEvents, function ($a, $b) {
                return strtotime($a['start'] ?? $a['start_time']) <=> strtotime($b['start'] ?? $b['start_time']);
            });
            
            return $allEvents;
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

    /**
     * Lấy events sắp tới
     * 
     * @param int $lecturerId Lecturer ID
     * @param int $limit Limit results
     * @return array Upcoming events
     */
    public function getUpcomingEvents(int $lecturerId, int $limit = 10): array
    {
        try {
            $startDate = now()->format('Y-m-d H:i:s');
            $endDate = now()->addDays(30)->format('Y-m-d H:i:s');
            
            $tasks = $this->getLecturerTasksByDateRange($lecturerId, $startDate, $endDate, $limit);
            
            return $this->mapTasksToEvents($tasks);
        } catch (\Exception $e) {
            Log::error('LecturerCalendarRepository: Error getting upcoming events', [
                'lecturer_id' => $lecturerId,
                'limit' => $limit,
                'error' => $e->getMessage()
            ]);
            throw new LecturerTaskException('Failed to retrieve upcoming events: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy events quá hạn
     * 
     * @param int $lecturerId Lecturer ID
     * @return array Overdue events
     */
    public function getOverdueEvents(int $lecturerId): array
    {
        try {
            $now = now()->format('Y-m-d H:i:s');
            
            $tasks = \Modules\Task\app\Models\Task::where(function ($q) use ($lecturerId) {
                $q->where(function ($createdQuery) use ($lecturerId) {
                    $createdQuery->where('creator_id', $lecturerId)
                                 ->where('creator_type', 'lecturer');
                })
                ->orWhereHas('receivers', function ($receiverQuery) use ($lecturerId) {
                    $receiverQuery->where('receiver_id', $lecturerId)
                                  ->where('receiver_type', 'lecturer');
                });
            })
            ->where('deadline', '<', $now)
            ->where('status', '!=', 'completed')
            ->with(['creator', 'receivers'])
            ->orderBy('deadline', 'desc')
            ->get();
            
            return $this->mapTasksToEvents($tasks);
        } catch (\Exception $e) {
            Log::error('LecturerCalendarRepository: Error getting overdue events', [
                'lecturer_id' => $lecturerId,
                'error' => $e->getMessage()
            ]);
            throw new LecturerTaskException('Failed to retrieve overdue events: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Đếm events theo status
     * 
     * @param int $lecturerId Lecturer ID
     * @return array Events count by status
     */
    public function getEventsCountByStatus(int $lecturerId): array
    {
        try {
            $tasks = \Modules\Task\app\Models\Task::where(function ($q) use ($lecturerId) {
                $q->where(function ($createdQuery) use ($lecturerId) {
                    $createdQuery->where('creator_id', $lecturerId)
                                 ->where('creator_type', 'lecturer');
                })
                ->orWhereHas('receivers', function ($receiverQuery) use ($lecturerId) {
                    $receiverQuery->where('receiver_id', $lecturerId)
                                  ->where('receiver_type', 'lecturer');
                });
            })
            ->with(['creator', 'receivers'])
            ->get();

            $now = now();
            
            $counts = [
                'total' => $tasks->count(),
                'pending' => $tasks->where('status', 'pending')->count(),
                'in_progress' => $tasks->where('status', 'in_progress')->count(),
                'completed' => $tasks->where('status', 'completed')->count(),
                'overdue' => $tasks->filter(function ($task) use ($now) {
                    return $task->deadline < $now && $task->status !== 'completed';
                })->count(),
                'upcoming' => $tasks->filter(function ($task) use ($now) {
                    return $task->deadline > $now && $task->status !== 'completed';
                })->count(),
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

    /**
     * Helper: Lấy tasks của lecturer theo date range
     * 
     * @param int $lecturerId Lecturer ID
     * @param string $startDate Start date
     * @param string $endDate End date
     * @param int|null $limit Limit results
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getLecturerTasksByDateRange(
        int $lecturerId,
        string $startDate,
        string $endDate,
        ?int $limit = null
    ) {
        $query = \Modules\Task\app\Models\Task::whereBetween('deadline', [$startDate, $endDate])
            ->where(function ($q) use ($lecturerId) {
                // Tasks lecturer tạo
                $q->where(function ($createdQuery) use ($lecturerId) {
                    $createdQuery->where('creator_id', $lecturerId)
                                 ->where('creator_type', 'lecturer');
                })
                // HOẶC tasks được assign cho lecturer
                ->orWhereHas('receivers', function ($receiverQuery) use ($lecturerId) {
                    $receiverQuery->where('receiver_id', $lecturerId)
                                  ->where('receiver_type', 'lecturer');
                });
            })
            ->with(['creator', 'receivers'])
            ->orderBy('deadline', 'asc');
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }

    /**
     * Helper: Map tasks thành events format
     * 
     * @param array|\Illuminate\Database\Eloquent\Collection<\Modules\Task\app\Models\Task> $tasks Tasks
     * @return array<array<string, mixed>> Events
     */
    private function mapTasksToEvents($tasks): array
    {
        if (!is_iterable($tasks)) {
            return [];
        }
        
        return collect($tasks)->map(function ($task) {
            /** @var \Modules\Task\app\Models\Task $task */
            // Get creator info
            $creatorData = [
                'id' => $task->creator_id,
                'type' => $task->creator_type ?? 'unknown',
            ];
            
            // Load creator relationship if available
            if ($task->relationLoaded('creator') && $task->creator) {
              $creatorData['name'] = $task->creator->full_name ?? $task->creator->name ?? null;
                $creatorData['email'] = $task->creator->email ?? null;
            }
            
            // Get receivers safely
            $receivers = [];
            if ($task->relationLoaded('receivers') && $task->receivers) {
                $receivers = $task->receivers->map(function ($receiver) {
                    return [
                        'id' => $receiver->receiver_id,
                        'type' => $receiver->receiver_type,
                    ];
                })->toArray();
            }
            
            return [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description ?? '',
                'start' => $task->deadline?->toDateTimeString(),
                'end' => $task->deadline?->toDateTimeString(),
                'start_time' => $task->deadline?->toDateTimeString(),
                'end_time' => $task->deadline?->toDateTimeString(),
                'event_type' => 'task',
                'task_id' => $task->id,
                'status' => $task->status ?? 'pending',
                'priority' => $task->priority ?? 'medium',
                'class_id' => $task->class_id ?? null,
                'creator' => $creatorData,
                'receivers' => $receivers,
                'files_count' => $task->relationLoaded('files') && $task->files ? $task->files->count() : 0,
                'submissions_count' => $task->relationLoaded('submissions') && $task->submissions ? $task->submissions->count() : 0,
            ];
        })->toArray();
    }

    /**
     * Helper: Map Calendar model thành event format
     * 
     * @param \Modules\Task\app\Models\Calendar $calendar Calendar model
     * @return array<string, mixed> Event data
     */
    private function mapCalendarToEvent(\Modules\Task\app\Models\Calendar $calendar): array
    {
        /** @var \Modules\Task\app\Models\Calendar $calendar */
        return [
            'id' => $calendar->id,
            'title' => $calendar->title,
            'description' => $calendar->description ?? '',
            'start' => $calendar->start_time?->toDateTimeString(),
            'end' => $calendar->end_time?->toDateTimeString(),
            'start_time' => $calendar->start_time?->toDateTimeString(),
            'end_time' => $calendar->end_time?->toDateTimeString(),
            'event_type' => $calendar->event_type ?? 'event',
            'task_id' => $calendar->task_id,
            'status' => 'scheduled',
            'priority' => 'medium',
            'creator' => [
                'id' => $calendar->creator_id,
                'type' => $calendar->creator_type ?? 'unknown',
            ],
        ];
    }
}
