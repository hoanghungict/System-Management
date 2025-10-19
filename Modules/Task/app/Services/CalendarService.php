<?php

namespace Modules\Task\app\Services;

use Modules\Task\app\Repositories\Interfaces\TaskRepositoryInterface;
use Illuminate\Support\Facades\Log;

/**
 * Calendar Service - Common calendar operations
 * Tuân thủ Clean Architecture: Business logic layer
 */
class CalendarService
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository
    ) {}

    /**
     * Lấy events theo ngày
     */
    public function getEventsByDate(string $date, int $userId = null, string $userType = null): array
    {
        try {
            $startDate = $date . ' 00:00:00';
            $endDate = $date . ' 23:59:59';
            
            // Lấy tasks theo user nếu có, nếu không lấy tất cả
            if ($userId && $userType) {
                $tasks = $this->taskRepository->getTasksByDateRange($userId, $userType, $startDate, $endDate);
            } else {
                // Lấy tất cả tasks trong ngày (cho admin) - sử dụng Task model trực tiếp
                $tasks = \Modules\Task\app\Models\Task::whereBetween('deadline', [$startDate, $endDate])
                    ->with(['creator', 'receivers'])
                    ->get();
            }
            
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
                'date' => $date,
                'events' => $events,
                'count' => $events->count()
            ];
        } catch (\Exception $e) {
            Log::error('CalendarService: Error getting events by date', [
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            return [
                'date' => $date,
                'events' => [],
                'count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Lấy events theo khoảng thời gian
     */
    public function getEventsByRange(string $startDate, string $endDate, int $userId = null, string $userType = null): array
    {
        try {
            // Lấy tasks có deadline trong khoảng thời gian này
            if ($userId && $userType) {
                $tasks = $this->taskRepository->getTasksByDateRange($userId, $userType, $startDate, $endDate);
            } else {
                // Lấy tất cả tasks trong khoảng thời gian (cho admin)
                $tasks = \Modules\Task\app\Models\Task::whereBetween('deadline', [$startDate, $endDate])
                    ->with(['creator', 'receivers'])
                    ->get();
            }
            
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
                'start_date' => $startDate,
                'end_date' => $endDate,
                'events' => $events,
                'count' => $events->count()
            ];
        } catch (\Exception $e) {
            Log::error('CalendarService: Error getting events by range', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'error' => $e->getMessage()
            ]);
            return [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'events' => [],
                'count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Lấy events sắp tới
     */
    public function getUpcomingEvents(int $userId = null, string $userType = null): array
    {
        try {
            // Lấy tasks có deadline sắp tới (trong 30 ngày tới)
            $startDate = now()->format('Y-m-d H:i:s');
            $endDate = now()->addDays(30)->format('Y-m-d H:i:s');
            
            if ($userId && $userType) {
                $tasks = $this->taskRepository->getTasksByDateRange($userId, $userType, $startDate, $endDate);
            } else {
                // Lấy tất cả tasks sắp tới (cho admin)
                $tasks = \Modules\Task\app\Models\Task::whereBetween('deadline', [$startDate, $endDate])
                    ->with(['creator', 'receivers'])
                    ->get();
            }
            
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
                'events' => $events,
                'count' => $events->count()
            ];
        } catch (\Exception $e) {
            Log::error('CalendarService: Error getting upcoming events', [
                'error' => $e->getMessage()
            ]);
            return [
                'events' => [],
                'count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Lấy events quá hạn
     */
    public function getOverdueEvents(): array
    {
        try {
            // Simulate overdue events
            $events = []; // Simplified for now
            
            return [
                'events' => $events,
                'count' => count($events)
            ];
        } catch (\Exception $e) {
            Log::error('CalendarService: Error getting overdue events', [
                'error' => $e->getMessage()
            ]);
            return [
                'events' => [],
                'count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Lấy số lượng events theo trạng thái
     */
    public function getEventsCountByStatus(): array
    {
        try {
            // Simulate events count by status
            $counts = []; // Simplified for now
            
            return [
                'counts' => $counts,
                'total' => array_sum($counts)
            ];
        } catch (\Exception $e) {
            Log::error('CalendarService: Error getting events count by status', [
                'error' => $e->getMessage()
            ]);
            return [
                'counts' => [],
                'total' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Lấy reminders
     */
    public function getReminders(): array
    {
        try {
            // Simulate reminders
            $reminders = []; // Simplified for now
            
            return [
                'reminders' => $reminders,
                'count' => count($reminders)
            ];
        } catch (\Exception $e) {
            Log::error('CalendarService: Error getting reminders', [
                'error' => $e->getMessage()
            ]);
            return [
                'reminders' => [],
                'count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Tạo reminder
     */
    public function setReminder(array $data): array
    {
        try {
            // Simulate setting reminder
            $reminder = [
                'id' => rand(1, 1000),
                'task_id' => $data['task_id'] ?? null,
                'reminder_time' => $data['reminder_time'] ?? null,
                'created_at' => now()->toISOString()
            ];
            
            return [
                'reminder' => $reminder,
                'success' => true
            ];
        } catch (\Exception $e) {
            Log::error('CalendarService: Error setting reminder', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            return [
                'reminder' => null,
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Lấy tất cả events (Admin only)
     */
    public function getAllEvents(): array
    {
        try {
            $events = []; // Simplified for now
            
            return [
                'events' => $events,
                'count' => count($events)
            ];
        } catch (\Exception $e) {
            Log::error('CalendarService: Error getting all events', [
                'error' => $e->getMessage()
            ]);
            return [
                'events' => [],
                'count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Lấy events theo loại (Admin only)
     */
    public function getEventsByType(string $type): array
    {
        try {
            $events = []; // Simplified for now
            
            return [
                'type' => $type,
                'events' => $events,
                'count' => count($events)
            ];
        } catch (\Exception $e) {
            Log::error('CalendarService: Error getting events by type', [
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return [
                'type' => $type,
                'events' => [],
                'count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Lấy recurring events (Admin only)
     */
    public function getRecurringEvents(): array
    {
        try {
            $events = []; // Simplified for now
            
            return [
                'events' => $events,
                'count' => count($events)
            ];
        } catch (\Exception $e) {
            Log::error('CalendarService: Error getting recurring events', [
                'error' => $e->getMessage()
            ]);
            return [
                'events' => [],
                'count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
}
