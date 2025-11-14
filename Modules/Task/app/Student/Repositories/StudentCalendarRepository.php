<?php

namespace Modules\Task\app\Student\Repositories;

use Modules\Task\app\Student\Exceptions\StudentTaskException;
use Modules\Task\app\Repositories\Interfaces\TaskRepositoryInterface;
use Illuminate\Support\Facades\Log;

/**
 * Student Calendar Repository
 */
class StudentCalendarRepository
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository
    ) {}

    public function getStudentEvents($studentId, $filters = [])
    {
        try {
            // Lấy tasks của student với pagination
            $page = $filters['page'] ?? 1;
            $perPage = $filters['per_page'] ?? 15;
            
            $tasks = $this->taskRepository->getTasksByReceiver($studentId, 'student', $filters, $perPage);
            
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
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $tasks->count(),
                    'last_page' => ceil($tasks->count() / $perPage)
                ]
            ];
        } catch (\Exception $e) {
            Log::error('StudentCalendarRepository: Error getting student events', [
                'student_id' => $studentId,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw new StudentTaskException('Failed to retrieve student events: ' . $e->getMessage(), 500);
        }
    }

    public function getEventsByDate($studentId, $date)
    {
        try {
            $startDate = $date . ' 00:00:00';
            $endDate = $date . ' 23:59:59';
            
            $tasks = \Modules\Task\app\Models\Task::whereBetween('deadline', [$startDate, $endDate])
                ->whereHas('receivers', function ($query) use ($studentId) {
                    $query->where('receiver_id', $studentId)
                          ->where('receiver_type', 'student');
                })
                ->with(['creator', 'receivers'])
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
            Log::error('StudentCalendarRepository: Error getting events by date', [
                'student_id' => $studentId,
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            throw new StudentTaskException('Failed to retrieve events by date: ' . $e->getMessage(), 500);
        }
    }

    public function getEventsByRange($studentId, $startDate, $endDate)
    {
        try {
            // Ensure datetime format
            if (strlen($startDate) === 10) {
                $startDate .= ' 00:00:00';
            }
            if (strlen($endDate) === 10) {
                $endDate .= ' 23:59:59';
            }
            
            // Get tasks assigned to student
            $tasks = \Modules\Task\app\Models\Task::whereBetween('deadline', [$startDate, $endDate])
                ->whereHas('receivers', function ($query) use ($studentId) {
                    $query->where('receiver_id', $studentId)
                          ->where('receiver_type', 'student');
                })
                ->with(['creator', 'receivers'])
                ->get();
            
            // Also get calendar events (system-wide events from admin)
            $studentIdForQuery = $studentId; // Extract to avoid IDE warnings
            $calendarEvents = \Modules\Task\app\Models\Calendar::where(function ($q) use ($studentIdForQuery) {
                // Events created by student
                $q->where(function ($creatorQuery) use ($studentIdForQuery) {
                    $creatorQuery->where('creator_id', $studentIdForQuery)
                                 ->where('creator_type', 'student');
                })
                // OR system-wide events (created by admin)
                ->orWhere('creator_type', 'admin')
                // OR standalone calendar events (not linked to task = system-wide announcements)
                ->orWhereNull('task_id');
            })
            ->whereBetween('start_time', [$startDate, $endDate])
            ->get();
            
            // Map tasks to events
            $taskEvents = $tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'start' => $task->deadline,
                    'end' => $task->deadline,
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
            
            // Map calendar events
            $calendarEventsArray = $calendarEvents->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description ?? '',
                    'start' => $event->start_time?->toDateTimeString(),
                    'end' => $event->end_time?->toDateTimeString(),
                    'start_time' => $event->start_time?->toDateTimeString(),
                    'end_time' => $event->end_time?->toDateTimeString(),
                    'event_type' => $event->event_type ?? 'event',
                    'task_id' => $event->task_id,
                    'status' => 'scheduled',
                    'priority' => 'medium',
                    'creator' => [
                        'id' => $event->creator_id,
                        'type' => $event->creator_type ?? 'unknown',
                    ],
                ];
            })->toArray();
            
            // Merge and sort
            $allEvents = array_merge($taskEvents, $calendarEventsArray);
            usort($allEvents, function ($a, $b) {
                return strtotime($a['start'] ?? $a['start_time']) <=> strtotime($b['start'] ?? $b['start_time']);
            });
            
            return $allEvents;
        } catch (\Exception $e) {
            Log::error('StudentCalendarRepository: Error getting events by range', [
                'student_id' => $studentId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'error' => $e->getMessage()
            ]);
            throw new StudentTaskException('Failed to retrieve events by range: ' . $e->getMessage(), 500);
        }
    }

    public function getUpcomingEvents($studentId, $limit = 10)
    {
        try {
            $startDate = now()->format('Y-m-d H:i:s');
            $endDate = now()->addDays(30)->format('Y-m-d H:i:s');
            
            $tasks = \Modules\Task\app\Models\Task::whereBetween('deadline', [$startDate, $endDate])
                ->whereHas('receivers', function ($query) use ($studentId) {
                    $query->where('receiver_id', $studentId)
                          ->where('receiver_type', 'student');
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
            Log::error('StudentCalendarRepository: Error getting upcoming events', [
                'student_id' => $studentId,
                'limit' => $limit,
                'error' => $e->getMessage()
            ]);
            throw new StudentTaskException('Failed to retrieve upcoming events: ' . $e->getMessage(), 500);
        }
    }

    public function getOverdueEvents($studentId)
    {
        try {
            $endDate = now()->format('Y-m-d H:i:s');
            
            // Lấy tasks quá hạn (deadline < now)
            $tasks = $this->taskRepository->getTasksByReceiver($studentId, 'student', ['overdue' => true]);
            
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
            Log::error('StudentCalendarRepository: Error getting overdue events', [
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);
            throw new StudentTaskException('Failed to retrieve overdue events: ' . $e->getMessage(), 500);
        }
    }

    public function getEventsCountByStatus($studentId)
    {
        try {
            // Lấy tất cả tasks của student
            $allTasks = $this->taskRepository->getTasksByReceiver($studentId, 'student', []);
            
            $counts = [
                'total' => $allTasks->count(),
                'pending' => $allTasks->where('status', 'pending')->count(),
                'completed' => $allTasks->where('status', 'completed')->count(),
                'overdue' => $allTasks->where('deadline', '<', now())->where('status', '!=', 'completed')->count()
            ];
            
            return $counts;
        } catch (\Exception $e) {
            Log::error('StudentCalendarRepository: Error getting events count by status', [
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);
            throw new StudentTaskException('Failed to retrieve events count by status: ' . $e->getMessage(), 500);
        }
    }

    public function getReminders($studentId, $filters = [])
    {
        try {
            return [
                'data' => [
                    ['id' => 1, 'title' => 'Reminder 1', 'remind_at' => now()->addHours(1)],
                    ['id' => 2, 'title' => 'Reminder 2', 'remind_at' => now()->addHours(2)]
                ],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => 15,
                    'total' => 2,
                    'last_page' => 1
                ]
            ];
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve student reminders: ' . $e->getMessage(), 500);
        }
    }

    public function setReminder($data)
    {
        try {
            return [
                'id' => 1,
                'title' => $data['title'],
                'remind_at' => $data['remind_at'],
                'user_id' => $data['user_id'],
                'user_type' => $data['user_type'],
                'created_at' => now()
            ];
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to set reminder: ' . $e->getMessage(), 500);
        }
    }
}
