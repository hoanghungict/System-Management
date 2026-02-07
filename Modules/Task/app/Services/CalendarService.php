<?php

/**
 * Suppress false-positive warnings from Intelephense for variables in closures
 * @phpstan-ignore-next-line
 */

declare(strict_types=1);

namespace Modules\Task\app\Services;

use Modules\Task\app\Repositories\Interfaces\TaskRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Modules\Auth\app\Models\Attendance\AttendanceSession;
use Modules\Task\app\Models\Exam;
use Modules\Task\app\Models\Assignment;

/**
 * Calendar Service - Common calendar operations
 * Tuân thủ Clean Architecture: Business logic layer
 * 
 * @package Modules\Task\app\Services
 * @author System Management Team
 * @version 2.0.0
 */
class CalendarService
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository
    ) {}

    /**
     * Lấy events theo ngày
     * 
     * @param string $date Format: Y-m-d
     * @param int|null $userId User ID nếu cần filter theo user
     * @param string|null $userType User type: 'student', 'lecturer', 'admin'
     * @return array Calendar events data
     */
    public function getEventsByDate(string $date, ?int $userId = null, ?string $userType = null): array
    {
        try {
            $startDate = $date . ' 00:00:00';
            $endDate = $date . ' 23:59:59';
            
            // Lấy tasks từ bảng task
            $tasks = [];
            if ($userId && $userType) {
                $tasks = $this->getTasksForUserByDateRange($userId, $userType, $startDate, $endDate);
            } else {
                // Lấy tất cả tasks trong ngày (cho admin)
                $tasks = \Modules\Task\app\Models\Task::whereBetween('deadline', [$startDate, $endDate])
                    ->with(['receivers'])
                    ->orderBy('deadline', 'asc')
                    ->get();
            }
            
            // Lấy events từ bảng calendar
            $calendarQuery = \Modules\Task\app\Models\Calendar::where(function ($q) use ($startDate, $endDate) {
                /** @var string $startDate */
                /** @var string $endDate */
                $q->whereBetween('start_time', [$startDate, $endDate])
                  ->orWhereBetween('end_time', [$startDate, $endDate]);
            });
            
            // Filter theo user nếu có
            if ($userId && $userType) {
                $calendarQuery->where(function ($q) use ($userId, $userType) {
                    /** @var \Illuminate\Database\Eloquent\Builder $q */
                    $q->where(function ($creatorQuery) use ($userId, $userType) {
                        /** @var \Illuminate\Database\Eloquent\Builder $creatorQuery */
                        $creatorQuery->where('creator_id', $userId)
                                    ->where('creator_type', $userType);
                    })
                    ->orWhere('creator_type', 'admin')
                    ->orWhereNull('task_id');
                });
            }
            
            $calendarEvents = $calendarQuery->orderBy('start_time', 'asc')->get();

            // Lấy Attendance Sessions (Lịch học)
            $attendanceSessions = [];
            if ($userId && $userType) {
                $attendanceSessions = $this->getAttendanceSessionsForUser($userId, $userType, $startDate, $endDate);
            }
            
            // Map và merge events
            $taskEvents = $this->mapTasksToEvents($tasks);
            $calendarEventsArray = $this->mapCalendarToEvents($calendarEvents);
            $attendanceEvents = $this->mapSessionsToEvents($attendanceSessions);

            $attendanceEvents = $this->mapSessionsToEvents($attendanceSessions);

            // [NEW] Get Holidays
            $holidays = $this->getHolidaysForRange($startDate, $endDate);
            $holidayEvents = $this->mapHolidaysToEvents($holidays);

            // [NEW] Get Exams and Assignments
            $exams = $this->getExamsForRange($startDate, $endDate, $userId, $userType);
            $assignments = $this->getAssignmentsForRange($startDate, $endDate, $userId, $userType);
            
            $examEvents = $this->mapExamsToEvents($exams);
            $assignmentEvents = $this->mapAssignmentsToEvents($assignments);

            $events = $this->mergeAndSortEvents($taskEvents, $calendarEventsArray, $attendanceEvents, $holidayEvents, $examEvents, $assignmentEvents);
            
            return [
                'date' => $date,
                'events' => $events,
                'count' => count($events)
            ];
        } catch (\Exception $e) {
            Log::error('CalendarService: Error getting events by date', [
                'date' => $date,
                'user_id' => $userId,
                'user_type' => $userType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'date' => $date,
                'events' => [],
                'count' => 0,
                'error' => 'Failed to retrieve events'
            ];
        }
    }

    /**
     * Lấy events theo khoảng thời gian
     * 
     * @param string $startDate Format: Y-m-d hoặc Y-m-d H:i:s
     * @param string $endDate Format: Y-m-d hoặc Y-m-d H:i:s
     * @param int|null $userId User ID nếu cần filter theo user
     * @param string|null $userType User type: 'student', 'lecturer', 'admin'
     * @return array Calendar events data
     */
    public function getEventsByRange(string $startDate, string $endDate, ?int $userId = null, ?string $userType = null): array
    {
        try {
            // Đảm bảo format datetime đúng
            /** @var string $startDate */
            /** @var string $endDate */
            if (strlen($startDate) === 10) {
                $startDate .= ' 00:00:00';
            }
            if (strlen($endDate) === 10) {
                $endDate .= ' 23:59:59';
            }
            
            // Lấy tasks có deadline trong khoảng thời gian này
            /** @var int|null $userId */
            /** @var string|null $userType */
            if ($userId && $userType) {
                $tasks = $this->getTasksForUserByDateRange($userId, $userType, $startDate, $endDate);
            } else {
                // Lấy tất cả tasks trong khoảng thời gian (cho admin)
                $tasks = \Modules\Task\app\Models\Task::whereBetween('deadline', [$startDate, $endDate])
                    ->with(['receivers'])
                    ->orderBy('deadline', 'asc')
                    ->get();
            }
            
            // Lấy events từ bảng calendar
            $calendarQuery = \Modules\Task\app\Models\Calendar::where(function ($q) use ($startDate, $endDate) {
                /** @var string $startDate */
                /** @var string $endDate */
                $q->whereBetween('start_time', [$startDate, $endDate])
                  ->orWhereBetween('end_time', [$startDate, $endDate])
                  ->orWhere(function ($subQ) use ($startDate, $endDate) {
                      /** @var string $startDate */
                      /** @var string $endDate */
                      $subQ->where('start_time', '<=', $startDate)
                           ->where('end_time', '>=', $endDate);
                  });
            });
            
            // Filter theo user nếu có
            if ($userId && $userType) {
                $calendarQuery->where(function ($q) use ($userId, $userType) {
                    /** @var \Illuminate\Database\Eloquent\Builder $q */
                    $q->where(function ($creatorQuery) use ($userId, $userType) {
                        /** @var \Illuminate\Database\Eloquent\Builder $creatorQuery */
                        $creatorQuery->where('creator_id', $userId)
                                    ->where('creator_type', $userType);
                    })
                    ->orWhere('creator_type', 'admin')
                    ->orWhereNull('task_id');
                });
            }
            
            $calendarEvents = $calendarQuery->orderBy('start_time', 'asc')->get();

            // Lấy Attendance Sessions (Lịch học)
            $attendanceSessions = [];
            if ($userId && $userType) {
                $attendanceSessions = $this->getAttendanceSessionsForUser($userId, $userType, $startDate, $endDate);
            }
            
            // Map và merge events
            $taskEvents = $this->mapTasksToEvents($tasks);
            $calendarEventsArray = $this->mapCalendarToEvents($calendarEvents);
            $attendanceEvents = $this->mapSessionsToEvents($attendanceSessions);

            $attendanceEvents = $this->mapSessionsToEvents($attendanceSessions);

            // [NEW] Get Holidays
            $holidays = $this->getHolidaysForRange($startDate, $endDate);
            $holidayEvents = $this->mapHolidaysToEvents($holidays);

            // [NEW] Get Exams and Assignments
            $exams = $this->getExamsForRange($startDate, $endDate, $userId, $userType);
            $assignments = $this->getAssignmentsForRange($startDate, $endDate, $userId, $userType);
            
            $examEvents = $this->mapExamsToEvents($exams);
            $assignmentEvents = $this->mapAssignmentsToEvents($assignments);

            $events = $this->mergeAndSortEvents($taskEvents, $calendarEventsArray, $attendanceEvents, $holidayEvents, $examEvents, $assignmentEvents);
            
            return [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'events' => $events,
                'count' => count($events)
            ];
        } catch (\Exception $e) {
            Log::error('CalendarService: Error getting events by range', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'user_id' => $userId,
                'user_type' => $userType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'events' => [],
                'count' => 0,
                'error' => 'Failed to retrieve events'
            ];
        }
    }

    /**
     * Lấy events sắp tới (trong 30 ngày)
     * 
     * @param int|null $userId User ID nếu cần filter theo user
     * @param string|null $userType User type
     * @param int $limit Số lượng events tối đa
     * @return array Upcoming events data
     */
    public function getUpcomingEvents(?int $userId = null, ?string $userType = null, int $limit = 10): array
    {
        try {
            // Lấy tasks có deadline sắp tới (từ bây giờ đến 30 ngày tới)
            $startDate = now()->format('Y-m-d H:i:s');
            $endDate = now()->addDays(30)->format('Y-m-d H:i:s');
            
            /** @var int|null $userId */
            /** @var string|null $userType */
            /** @var int $limit */
            if ($userId && $userType) {
                $tasks = $this->getTasksForUserByDateRange($userId, $userType, $startDate, $endDate, $limit);
            } else {
                // Lấy tất cả tasks sắp tới (cho admin)
                $tasks = \Modules\Task\app\Models\Task::whereBetween('deadline', [$startDate, $endDate])
                    ->with(['receivers'])
                    ->orderBy('deadline', 'asc')
                    ->limit($limit)
                    ->get();
            }
            
            // Lấy events từ bảng calendar
            $calendarQuery = \Modules\Task\app\Models\Calendar::where('start_time', '>=', $startDate)
                ->where('start_time', '<=', $endDate);
            
            // Filter theo user nếu có
            if ($userId && $userType) {
                $calendarQuery->where(function ($q) use ($userId, $userType) {
                    /** @var \Illuminate\Database\Eloquent\Builder $q */
                    $q->where(function ($creatorQuery) use ($userId, $userType) {
                        /** @var \Illuminate\Database\Eloquent\Builder $creatorQuery */
                        $creatorQuery->where('creator_id', $userId)
                                    ->where('creator_type', $userType);
                    })
                    ->orWhere('creator_type', 'admin')
                    ->orWhereNull('task_id');
                });
            }
            
            $calendarEvents = $calendarQuery->orderBy('start_time', 'asc')->limit($limit)->get();

            // Lấy Attendance Sessions (Lịch học)
            $attendanceSessions = [];
            if ($userId && $userType) {
                $attendanceSessions = $this->getAttendanceSessionsForUser($userId, $userType, $startDate, $endDate, $limit);
            }
            
            // Map và merge events
            $taskEvents = $this->mapTasksToEvents($tasks);
            $calendarEventsArray = $this->mapCalendarToEvents($calendarEvents);
            $attendanceEvents = $this->mapSessionsToEvents($attendanceSessions);

            $attendanceEvents = $this->mapSessionsToEvents($attendanceSessions);

            // [NEW] Get Holidays
            $holidays = $this->getHolidaysForRange($startDate, $endDate);
            $holidayEvents = $this->mapHolidaysToEvents($holidays);

            // [NEW] Get Exams and Assignments
            $exams = $this->getExamsForRange($startDate, $endDate, $userId, $userType, $limit);
            $assignments = $this->getAssignmentsForRange($startDate, $endDate, $userId, $userType, $limit);
            
            $examEvents = $this->mapExamsToEvents($exams);
            $assignmentEvents = $this->mapAssignmentsToEvents($assignments);

            $events = $this->mergeAndSortEvents($taskEvents, $calendarEventsArray, $attendanceEvents, $holidayEvents, $examEvents, $assignmentEvents);
            
            // Limit kết quả
            $events = array_slice($events, 0, $limit);
            
            return [
                'events' => $events,
                'count' => count($events),
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate
                ]
            ];
        } catch (\Exception $e) {
            Log::error('CalendarService: Error getting upcoming events', [
                'user_id' => $userId,
                'user_type' => $userType,
                'limit' => $limit,
                'error' => $e->getMessage()
            ]);
            
            return [
                'events' => [],
                'count' => 0,
                'error' => 'Failed to retrieve upcoming events'
            ];
        }
    }

    /**
     * Lấy events quá hạn
     * 
     * @param int|null $userId User ID nếu cần filter theo user
     * @param string|null $userType User type
     * @return array Overdue events data
     */
    public function getOverdueEvents(?int $userId = null, ?string $userType = null): array
    {
        try {
            $now = now()->format('Y-m-d H:i:s');
            
            /** @var int|null $userId */
            /** @var string|null $userType */
            if ($userId && $userType) {
                // Lấy tasks quá hạn cho user cụ thể
                $tasks = $this->taskRepository->getTasksByReceiver($userId, $userType, [
                    'overdue' => true,
                    'exclude_completed' => true
                ]);
            } else {
                // Lấy tất cả tasks quá hạn (cho admin)
                $tasks = \Modules\Task\app\Models\Task::where('deadline', '<', $now)
                    ->where('status', '!=', 'completed')
                    ->with(['receivers'])
                    ->orderBy('deadline', 'desc')
                    ->get();
            }
            
            $events = $this->mapTasksToEvents($tasks);
            
            return [
                'events' => $events,
                'count' => count($events)
            ];
        } catch (\Exception $e) {
            Log::error('CalendarService: Error getting overdue events', [
                'user_id' => $userId,
                'user_type' => $userType,
                'error' => $e->getMessage()
            ]);
            
            return [
                'events' => [],
                'count' => 0,
                'error' => 'Failed to retrieve overdue events'
            ];
        }
    }

    /**
     * Lấy số lượng events theo trạng thái
     * 
     * @param int|null $userId User ID nếu cần filter theo user
     * @param string|null $userType User type
     * @return array Events count by status
     */
    public function getEventsCountByStatus(?int $userId = null, ?string $userType = null): array
    {
        try {
            /** @var int|null $userId */
            /** @var string|null $userType */
            if ($userId && $userType) {
                // Lấy tasks của user
                $tasks = $this->taskRepository->getTasksByReceiver($userId, $userType, []);
            } else {
                // Lấy tất cả tasks (cho admin)
                $tasks = \Modules\Task\app\Models\Task::with(['receivers'])->get();
            }
            
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
            
            return [
                'counts' => $counts,
                'total' => $counts['total']
            ];
        } catch (\Exception $e) {
            Log::error('CalendarService: Error getting events count by status', [
                'user_id' => $userId,
                'user_type' => $userType,
                'error' => $e->getMessage()
            ]);
            
            return [
                'counts' => [
                    'total' => 0,
                    'pending' => 0,
                    'in_progress' => 0,
                    'completed' => 0,
                    'overdue' => 0,
                    'upcoming' => 0,
                ],
                'total' => 0,
                'error' => 'Failed to retrieve events count'
            ];
        }
    }

    /**
     * Lấy reminders (tạm thời mock, sẽ implement sau)
     * 
     * @param int|null $userId User ID
     * @param string|null $userType User type
     * @return array Reminders data
     */
    public function getReminders(?int $userId = null, ?string $userType = null): array
    {
        try {
            // TODO: Implement reminder system
            // Hiện tại trả về empty array
            return [
                'reminders' => [],
                'count' => 0
            ];
        } catch (\Exception $e) {
            Log::error('CalendarService: Error getting reminders', [
                'user_id' => $userId,
                'user_type' => $userType,
                'error' => $e->getMessage()
            ]);
            
            return [
                'reminders' => [],
                'count' => 0,
                'error' => 'Failed to retrieve reminders'
            ];
        }
    }

    /**
     * Tạo reminder (tạm thời mock, sẽ implement sau)
     * 
     * @param array $data Reminder data
     * @return array Created reminder
     */
    public function setReminder(array $data): array
    {
        try {
            // TODO: Implement reminder creation
            return [
                'reminder' => [
                    'id' => rand(1000, 9999),
                    'title' => $data['title'] ?? 'Reminder',
                    'remind_at' => $data['remind_at'] ?? now()->addHour(),
                    'user_id' => $data['user_id'] ?? null,
                    'user_type' => $data['user_type'] ?? null,
                    'created_at' => now()->toDateTimeString()
                ],
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
     * 
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array All events with pagination
     */
    public function getAllEvents(int $page = 1, int $perPage = 15): array
    {
        try {
            // Lấy tasks
            $tasks = \Modules\Task\app\Models\Task::with(['receivers'])
                ->orderBy('deadline', 'desc')
                ->get();

            // Lấy calendar events
            $calendarEvents = \Modules\Task\app\Models\Calendar::orderBy('start_time', 'desc')->get();

            // Map và merge events
            $taskEvents = $this->mapTasksToEvents($tasks);
            $calendarEventsArray = $this->mapCalendarToEvents($calendarEvents);
            $allEvents = $this->mergeAndSortEvents($taskEvents, $calendarEventsArray, []);

            // Pagination thủ công
            /** @var int $page */
            /** @var int $perPage */
            $total = count($allEvents);
            $offset = ($page - 1) * $perPage;
            $events = array_slice($allEvents, $offset, $perPage);

            return [
                'data' => $events,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => (int) ceil($total / $perPage),
                    'from' => $total > 0 ? $offset + 1 : null,
                    'to' => min($offset + $perPage, $total)
                ]
            ];
        } catch (\Exception $e) {
            Log::error('CalendarService: Error getting all events', [
                'page' => $page,
                'per_page' => $perPage,
                'error' => $e->getMessage()
            ]);
            
            return [
                'data' => [],
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => 0,
                    'last_page' => 0
                ],
                'error' => 'Failed to retrieve events'
            ];
        }
    }

    /**
     * Lấy events theo loại (Admin only)
     * 
     * @param string $type Event type/priority
     * @return array Events by type
     */
    public function getEventsByType(string $type): array
    {
        try {
            $tasks = \Modules\Task\app\Models\Task::where('priority', $type)
                ->with(['creator', 'receivers'])
                ->orderBy('deadline', 'desc')
                ->get();
            
            $events = $this->mapTasksToEvents($tasks);
            
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
                'error' => 'Failed to retrieve events'
            ];
        }
    }

    /**
     * Lấy recurring events (Admin only) - Tạm thời mock
     * 
     * @return array Recurring events
     */
    public function getRecurringEvents(): array
    {
        try {
            // TODO: Implement recurring events
            return [
                'events' => [],
                'count' => 0
            ];
        } catch (\Exception $e) {
            Log::error('CalendarService: Error getting recurring events', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'events' => [],
                'count' => 0,
                'error' => 'Failed to retrieve recurring events'
            ];
        }
    }



    /**
     * Kiểm tra trùng lịch (Conflict Check)
     * 
     * @param string $date Y-m-d
     * @param string $startTime H:i:s
     * @param string $endTime H:i:s
     * @param string|null $room Room name
     * @param int|null $lecturerId Lecturer ID
     * @param int|null $excludeSessionId ID to exclude (for update)
     * @return array List of conflicts (empty if none)
     */
    public function checkConflict(string $date, string $startTime, string $endTime, ?string $room, ?int $lecturerId, ?int $excludeSessionId = null): array
    {
        $conflicts = [];
        $start = Carbon::parse("$date $startTime");
        $end = Carbon::parse("$date $endTime");

        // Query base: Sessions that overlap with time range
        // (StartA < EndB) AND (EndA > StartB)
        // DB stores start_time and end_time as Time (H:i:s) and session_date as Date. 
        // Simple overlap check on same date.
        
        $baseQuery = AttendanceSession::where('session_date', $data['session_date'] ?? $date) // Assuming same date check for now
            ->where('status', '!=', 'cancelled');
            
        if ($excludeSessionId) {
            $baseQuery->where('id', '!=', $excludeSessionId);
        }

        // Check Room Conflict
        if ($room) {
            $roomConflicts = (clone $baseQuery)->where('room', $room)
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
                })->get();
                
            foreach ($roomConflicts as $c) {
                $conflicts[] = "Trùng phòng {$room} với lớp {$c->course_id} ({$c->start_time}-{$c->end_time})";
            }
        }

        // Check Lecturer Conflict
        if ($lecturerId) {
            // Check AttendanceSessions
            $lecturerConflicts = (clone $baseQuery)->whereHas('course', function($q) use ($lecturerId) {
                $q->where('lecturer_id', $lecturerId);
            })
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                  ->where('end_time', '>', $startTime);
            })->get();

            foreach ($lecturerConflicts as $c) {
                $conflicts[] = "Giảng viên đang dạy lớp {$c->course_id} ({$c->start_time}-{$c->end_time})";
            }
        }

        return $conflicts;
    }

    /**
     * Helper: Fetch Holidays
     */
    private function getHolidaysForRange(string $startDate, string $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        // 1. Fixed Holidays
        $fixed = \Modules\Auth\app\Models\Attendance\Holiday::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where('is_recurring', false)
            ->get();
            
        // 2. Recurring Holidays
        $recurring = \Modules\Auth\app\Models\Attendance\Holiday::where('is_recurring', true)->get();
        $expandedRecurring = collect();
        
        $years = range($start->year, $end->year);
        
        foreach ($recurring as $rec) {
            $recDate = Carbon::parse($rec->date);
            foreach ($years as $year) {
                $currentYearDate = Carbon::create($year, $recDate->month, $recDate->day);
                if ($currentYearDate->between($start, $end)) {
                    $clone = clone $rec;
                    $clone->date = $currentYearDate->toDateString(); // Override date for display
                    $expandedRecurring->push($clone);
                }
            }
        }
        
        return $fixed->merge($expandedRecurring);
    }
    
    /**
     * Helper: Map Holidays to Events
     */
    private function mapHolidaysToEvents($holidays): array
    {
        return $holidays->map(function ($h) {
            return [
                'id' => 'holiday-' . $h->id . '-' . $h->date, // Unique ID for calendar
                'title' => "🏖️ " . $h->name,
                'start' => $h->date,
                'end' => $h->date,
                'start_time' => $h->date . ' 00:00:00',
                'end_time' => $h->date . ' 23:59:59',
                'event_type' => 'holiday',
                'status' => 'holiday',
                'priority' => 'high',
                'description' => $h->description,
                'allDay' => true,
                'classNames' => ['fc-event-holiday'] // Frontend class
            ];
        })->toArray();
    }
    
    /**
     * Helper: Merge and Sort (Updated)
     */
    private function mergeAndSortEvents(array ...$eventArrays): array
    {
        $allEvents = array_merge(...$eventArrays);
        
        usort($allEvents, function ($a, $b) {
            return strcmp($a['start_time'] ?? '', $b['start_time'] ?? '');
        });
        
        return $allEvents;
    }

    /**
     * Helper: Lấy tasks cho user theo date range
     * 
     * @param int $userId User ID
     * @param string $userType User type
     * @param string $startDate Start date
     * @param string $endDate End date
     * @param int|null $limit Limit results
     * @return \Illuminate\Database\Eloquent\Collection|array
     */
    private function getTasksForUserByDateRange(
        int $userId,
        string $userType,
        string $startDate,
        string $endDate,
        ?int $limit = null
    ) {
        $query = \Modules\Task\app\Models\Task::whereBetween('deadline', [$startDate, $endDate])
            ->where(function ($q) use ($userId, $userType) {
                // Tasks được assign cho user (receiver)
                $q->whereHas('receivers', function ($receiverQuery) use ($userId, $userType) {
                    $receiverQuery->where('receiver_id', $userId)
                                  ->where('receiver_type', $userType);
                });
                
                // Nếu là lecturer, cũng lấy tasks họ tạo
                if ($userType === 'lecturer') {
                    $q->orWhere(function ($lecturerQuery) use ($userId) {
                        $lecturerQuery->where('creator_id', $userId)
                                     ->where('creator_type', 'lecturer');
                    });
                }
            })
            ->with(['receivers'])
            ->orderBy('deadline', 'asc');
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }

    /**
     * Helper: Lấy Exams cho user
     */
    private function getExamsForRange($startDate, $endDate, $userId = null, $userType = null, $limit = null)
    {
        $query = Exam::query()
            ->whereBetween('start_time', [Carbon::parse($startDate), Carbon::parse($endDate)]);
            
        if ($userId && $userType) {
            if ($userType === 'student') {
                $query->whereHas('course.enrollments', function ($q) use ($userId) {
                    $q->where('student_id', $userId)->where('status', 'active');
                })->where('status', 'published');
            } elseif ($userType === 'lecturer') {
                $query->where('lecturer_id', $userId);
            }
        }
        
        $query->orderBy('start_time');
        if ($limit) $query->limit($limit);
        
        return $query->get();
    }

    /**
     * Helper: Lấy Assignments cho user
     */
    private function getAssignmentsForRange($startDate, $endDate, $userId = null, $userType = null, $limit = null)
    {
        $query = Assignment::query()
            ->whereBetween('deadline', [Carbon::parse($startDate), Carbon::parse($endDate)]);
            
        if ($userId && $userType) {
            if ($userType === 'student') {
                $query->whereHas('course.enrollments', function ($q) use ($userId) {
                    $q->where('student_id', $userId)->where('status', 'active');
                })->where('status', 'published');
            } elseif ($userType === 'lecturer') {
                $query->where('lecturer_id', $userId);
            }
        }
        
        $query->orderBy('deadline');
        if ($limit) $query->limit($limit);
        
        return $query->get();
    }

    private function mapExamsToEvents($exams): array
    {
        return $exams->map(function ($e) {
            return [
                'id' => 'exam-' . $e->id,
                'title' => '📝 ' . $e->title,
                'start' => $e->start_time?->toDateTimeString(),
                'end' => $e->end_time?->toDateTimeString(),
                'start_time' => $e->start_time?->toDateTimeString(),
                'end_time' => $e->end_time?->toDateTimeString(),
                'event_type' => 'exam',
                'task_id' => $e->id, // Use exam ID
                'status' => $e->status,
                'priority' => 'critical',
                'class_id' => $e->course_id,
                'description' => "Môn: {$e->course?->code}\nThời gian: {$e->time_limit} phút",
                'classNames' => ['fc-event-exam'],
                'extendedProps' => ['course' => $e->course?->code]
            ];
        })->toArray();
    }

    private function mapAssignmentsToEvents($assignments): array
    {
        return $assignments->map(function ($a) {
            return [
                'id' => 'assignment-' . $a->id,
                'title' => '📋 ' . $a->title,
                'start' => $a->deadline?->toDateTimeString(),
                'end' => $a->deadline?->toDateTimeString(),
                'start_time' => $a->deadline?->toDateTimeString(),
                'end_time' => $a->deadline?->toDateTimeString(),
                'event_type' => 'assignment',
                'task_id' => $a->id,
                'status' => $a->status,
                'priority' => 'high',
                'class_id' => $a->course_id,
                'description' => "Môn: {$a->course?->code}\nHạn nộp: {$a->deadline?->format('H:i d/m')}",
                'classNames' => ['fc-event-assignment']
            ];
        })->toArray();
    }

    /**
     * Helper: Lấy Attendance Sessions cho user
     */
    private function getAttendanceSessionsForUser(
        int $userId,
        string $userType,
        string $startDate,
        string $endDate,
        ?int $limit = null
    ) {
        $query = AttendanceSession::query()
            ->with(['course'])
            ->whereBetween('session_date', [
                Carbon::parse($startDate)->toDateString(),
                Carbon::parse($endDate)->toDateString()
            ]);

        if ($userType === 'student') {
            // Student: Get sessions of enrolled courses
            $query->whereHas('course.enrollments', function ($q) use ($userId) {
                $q->where('student_id', $userId)
                  ->where('status', 'active');
            });
        } elseif ($userType === 'lecturer') {
            // Lecturer: Get sessions of courses taught by lecturer
            $query->whereHas('course', function ($q) use ($userId) {
                $q->where('lecturer_id', $userId);
            });
        }
        
        $query->orderBy('session_date', 'asc')
              ->orderBy('start_time', 'asc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Helper: Map tasks thành calendar events format
     * 
     * @param \Illuminate\Database\Eloquent\Collection|array $tasks Tasks collection
     * @return array Calendar events
     */
    private function mapTasksToEvents($tasks): array
    {
        if (!is_iterable($tasks)) {
            return [];
        }
        
        return collect($tasks)->map(function ($task) {
            /** @var \Modules\Task\app\Models\Task $task */
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
                'status' => $task->status,
                'priority' => $task->priority,
                'class_id' => $task->class_id ?? null,
                'creator' => [
                    'id' => $task->creator_id,
                    'type' => $task->creator_type ?? 'unknown',
                    'name' => 'Unknown' // Creator name không có trong relationship
                ],
                'receivers' => $task->receivers?->map(function ($receiver) {
                    return [
                        'id' => $receiver->receiver_id,
                        'type' => $receiver->receiver_type,
                        'name' => $receiver->receiver?->name ?? 'Unknown'
                    ];
                })->toArray() ?? [],
                'files_count' => $task->files?->count() ?? 0,
                'submissions_count' => $task->submissions?->count() ?? 0,
                'created_at' => $task->created_at?->toDateTimeString(),
                'updated_at' => $task->updated_at?->toDateTimeString(),
            ];
        })->toArray();
    }

    /**
     * Helper: Map calendar events thành format chuẩn
     * 
     * @param \Illuminate\Database\Eloquent\Collection|array $calendarEvents Calendar events collection
     * @return array Calendar events
     */
    private function mapCalendarToEvents($calendarEvents): array
    {
        /** @noinspection PhpUndefinedVariableInspection */
        if (!is_iterable($calendarEvents)) {
            return [];
        }
        
        return collect($calendarEvents)->map(function ($event) {
            /** @var \Modules\Task\app\Models\Calendar $event */
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
                'class_id' => null,
                'creator' => [
                    'id' => $event->creator_id,
                    'type' => $event->creator_type ?? 'unknown',
                    'name' => 'Unknown'
                ],
                'receivers' => [],
                'files_count' => 0,
                'submissions_count' => 0,
                'created_at' => null,
                'updated_at' => null,
            ];
        })->toArray();
    }

    /**
     * Helper: Map sessions thành events
     */
    private function mapSessionsToEvents($sessions): array
    {
        if (!is_iterable($sessions)) {
            return [];
        }

        return collect($sessions)->map(function ($session) {
            $date = $session->session_date->format('Y-m-d');
            $startTime = $date . ' ' . $session->start_time;
            $endTime = $date . ' ' . $session->end_time;
            
            return [
                'id' => 'session_' . $session->id,
                'title' => ($session->course->code ?? 'COURSE') . ' - ' . ($session->course->name ?? 'Lesson'),
                'description' => "Phòng: {$session->room}\nChủ đề: {$session->topic}",
                'start' => $startTime,
                'end' => $endTime,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'event_type' => 'schedule', // New event type
                'task_id' => null,
                'status' => $session->status,
                'priority' => 'high',
                'class_id' => $session->course_id,
                'creator' => [
                    'id' => 0,
                    'type' => 'system',
                    'name' => 'System'
                ],
                'receivers' => [],
                'files_count' => 0,
                'submissions_count' => 0,
                'created_at' => $session->created_at?->toDateTimeString(),
                'updated_at' => $session->updated_at?->toDateTimeString(),
                // Extra data for frontend if needed
                'extendedProps' => [
                    'room' => $session->room,
                    'session_number' => $session->session_number,
                    'is_attendance' => true
                ]
            ];
        })->toArray();
    }


}
