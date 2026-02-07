<?php

namespace Modules\Auth\app\Services\AttendanceService;

use Modules\Auth\app\Repositories\AttendanceRepository\AttendanceSessionRepository;
use Modules\Auth\app\Repositories\AttendanceRepository\CourseRepository;
use Modules\Auth\app\Repositories\AttendanceRepository\CourseEnrollmentRepository;
use Modules\Auth\app\Models\Attendance\AttendanceSession;
use Modules\Auth\app\Models\Attendance\Course;
use Modules\Task\app\Models\Calendar;
use Modules\Task\app\Models\Task;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service xử lý thời khóa biểu
 * Tổng hợp dữ liệu từ attendance_sessions, courses, calendar, tasks
 */
class TimetableService
{
    protected AttendanceSessionRepository $sessionRepository;
    protected CourseRepository $courseRepository;
    protected CourseEnrollmentRepository $enrollmentRepository;

    /**
     * Mapping thời gian sang tiết học theo chuẩn Bộ GD
     * Tiết 1: 07:00 - 07:45
     * Tiết 2: 07:50 - 08:35
     * Tiết 3: 08:40 - 09:25
     * Tiết 4: 09:35 - 10:20
     * Tiết 5: 10:30 - 11:15
     * Tiết 6: 11:25 - 12:10
     * Tiết 7: 13:00 - 13:45
     * Tiết 8: 13:50 - 14:35
     * Tiết 9: 14:40 - 15:25
     * Tiết 10: 15:35 - 16:20
     */
    private const PERIODS = [
        1 => ['start' => '07:00', 'end' => '07:45'],
        2 => ['start' => '07:50', 'end' => '08:35'],
        3 => ['start' => '08:40', 'end' => '09:25'],
        4 => ['start' => '09:35', 'end' => '10:20'],
        5 => ['start' => '10:30', 'end' => '11:15'],
        6 => ['start' => '11:25', 'end' => '12:10'],
        7 => ['start' => '13:00', 'end' => '13:45'],
        8 => ['start' => '13:50', 'end' => '14:35'],
        9 => ['start' => '14:40', 'end' => '15:25'],
        10 => ['start' => '15:35', 'end' => '16:20'],
    ];

    public function __construct(
        AttendanceSessionRepository $sessionRepository,
        CourseRepository $courseRepository,
        CourseEnrollmentRepository $enrollmentRepository
    ) {
        $this->sessionRepository = $sessionRepository;
        $this->courseRepository = $courseRepository;
        $this->enrollmentRepository = $enrollmentRepository;
    }

    /**
     * Lấy thời khóa biểu theo tuần
     * 
     * @param string $startDate Ngày bắt đầu tuần (Y-m-d)
     * @param string $endDate Ngày kết thúc tuần (Y-m-d)
     * @param int|null $userId ID người dùng
     * @param string|null $userType Loại người dùng (student, lecturer, admin)
     * @return array
     */
    public function getWeeklyTimetable(
        string $startDate,
        string $endDate,
        ?int $userId = null,
        ?string $userType = null
    ): array {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $items = collect();

        // 1. Lấy lịch học từ attendance_sessions (ưu tiên)
        $classSessions = $this->getClassSessions($start, $end, $userId, $userType);
        $items = $items->merge($classSessions);

        // 2. Lấy lịch học từ courses (fallback nếu chưa generate sessions)
        $fallbackSessions = $this->getFallbackSessionsFromCourses($start, $end, $userId, $userType);
        $items = $items->merge($fallbackSessions);

        // 3. Lấy sự kiện từ calendar
        $events = $this->getCalendarEvents($start, $end, $userId, $userType);
        $items = $items->merge($events);

        // 4. Lấy deadline từ tasks
        $taskDeadlines = $this->getTaskDeadlines($start, $end, $userId, $userType);
        $items = $items->merge($taskDeadlines);

        // Sắp xếp theo thời gian
        $items = $items->sortBy('start')->values();

        return [
            'week_start' => $startDate,
            'week_end' => $endDate,
            'items' => $items->toArray(),
        ];
    }

    /**
     * Lấy thời khóa biểu theo ngày
     * 
     * @param string $date Ngày (Y-m-d)
     * @param int|null $userId ID người dùng
     * @param string|null $userType Loại người dùng
     * @return array
     */
    public function getDailyTimetable(
        string $date,
        ?int $userId = null,
        ?string $userType = null
    ): array {
        $start = Carbon::parse($date)->startOfDay();
        $end = Carbon::parse($date)->endOfDay();

        $items = collect();

        // Lấy lịch học từ attendance_sessions
        $classSessions = $this->getClassSessions($start, $end, $userId, $userType);
        $items = $items->merge($classSessions);

        // Lấy lịch học từ courses (fallback)
        $fallbackSessions = $this->getFallbackSessionsFromCourses($start, $end, $userId, $userType);
        $items = $items->merge($fallbackSessions);

        // Lấy sự kiện từ calendar
        $events = $this->getCalendarEvents($start, $end, $userId, $userType);
        $items = $items->merge($events);

        // Lấy deadline từ tasks
        $taskDeadlines = $this->getTaskDeadlines($start, $end, $userId, $userType);
        $items = $items->merge($taskDeadlines);

        // Sắp xếp theo thời gian
        $items = $items->sortBy('start')->values();

        return [
            'date' => $date,
            'items' => $items->toArray(),
        ];
    }

    /**
     * Lấy config mapping tiết học
     * 
     * @return array
     */
    public function getPeriodsConfig(): array
    {
        return self::PERIODS;
    }

    /**
     * Map thời gian sang tiết học
     * 
     * @param string $startTime Thời gian bắt đầu (H:i:s hoặc H:i)
     * @param string $endTime Thời gian kết thúc (H:i:s hoặc H:i)
     * @return array Danh sách các tiết học
     */
    public function mapTimeToPeriod(string $startTime, string $endTime): array
    {
        // Parse thời gian (xử lý cả H:i:s và H:i)
        try {
            $start = Carbon::createFromFormat('H:i:s', $startTime)->format('H:i');
        } catch (\Exception $e) {
            try {
                $start = Carbon::createFromFormat('H:i', $startTime)->format('H:i');
            } catch (\Exception $e2) {
                // Nếu không parse được, trả về mảng rỗng
                return [];
            }
        }

        try {
            $end = Carbon::createFromFormat('H:i:s', $endTime)->format('H:i');
        } catch (\Exception $e) {
            try {
                $end = Carbon::createFromFormat('H:i', $endTime)->format('H:i');
            } catch (\Exception $e2) {
                return [];
            }
        }

        $periods = [];

        foreach (self::PERIODS as $periodNum => $period) {
            $periodStart = $period['start'];
            $periodEnd = $period['end'];

            // Kiểm tra xem thời gian có overlap với tiết học không
            // Nếu start_time <= period_end và end_time >= period_start thì overlap
            if ($start <= $periodEnd && $end >= $periodStart) {
                $periods[] = $periodNum;
            }
        }

        return $periods;
    }

    /**
     * Lấy lịch học từ attendance_sessions
     */
    private function getClassSessions(
        Carbon $start,
        Carbon $end,
        ?int $userId,
        ?string $userType
    ): Collection {
        $query = AttendanceSession::with(['course.semester', 'course.lecturer'])
            ->whereBetween('session_date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('status', [
                AttendanceSession::STATUS_SCHEDULED,
                AttendanceSession::STATUS_IN_PROGRESS,
                AttendanceSession::STATUS_COMPLETED
            ]);

        // Filter theo user
        $courseIds = $this->getAccessibleCourseIds($userId, $userType);
        if ($courseIds !== null) {
            $query->whereIn('course_id', $courseIds);
        }

        $sessions = $query->get();

        return $sessions->map(function ($session) {
            return $this->formatClassSession($session);
        });
    }

    /**
     * Lấy lịch học từ courses (fallback khi chưa generate sessions)
     */
    private function getFallbackSessionsFromCourses(
        Carbon $start,
        Carbon $end,
        ?int $userId,
        ?string $userType
    ): Collection {
        $query = Course::with(['semester', 'lecturer'])
            ->where('status', 'active')
            ->where('sessions_generated', false)
            ->whereNotNull('schedule_days')
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                  ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
                  ->orWhere(function ($q2) use ($start, $end) {
                      $q2->where('start_date', '<=', $start->toDateString())
                         ->where('end_date', '>=', $end->toDateString());
                  });
            });

        // Filter theo user
        $courseIds = $this->getAccessibleCourseIds($userId, $userType);
        if ($courseIds !== null) {
            $query->whereIn('id', $courseIds);
        }

        $courses = $query->get();

        $items = collect();

        foreach ($courses as $course) {
            $scheduleDays = $course->schedule_days ?? [];
            $currentDate = $start->copy();

            while ($currentDate->lte($end)) {
                // Laravel/Carbon: 1 = Monday, 7 = Sunday
                // Yêu cầu: 2 = Thứ 2, ..., 8 = CN
                $dayOfWeek = $currentDate->dayOfWeekIso + 1;

                if (in_array($dayOfWeek, $scheduleDays)) {
                    // Kiểm tra ngày có trong khoảng thời gian học không
                    $courseStart = Carbon::parse($course->start_date);
                    $courseEnd = Carbon::parse($course->end_date);

                    if ($currentDate->between($courseStart, $courseEnd)) {
                        $items->push($this->formatCourseAsSession($course, $currentDate));
                    }
                }

                $currentDate->addDay();
            }
        }

        return $items;
    }

    /**
     * Lấy sự kiện từ calendar
     */
    private function getCalendarEvents(
        Carbon $start,
        Carbon $end,
        ?int $userId,
        ?string $userType
    ): Collection {
        $query = Calendar::where(function ($q) use ($start, $end) {
            $q->whereBetween('start_time', [$start, $end])
              ->orWhereBetween('end_time', [$start, $end])
              ->orWhere(function ($q2) use ($start, $end) {
                  $q2->where('start_time', '<=', $start)
                     ->where('end_time', '>=', $end);
              });
        });

        // Filter theo user nếu là student hoặc lecturer
        if ($userType === 'student' && $userId) {
            // Student chỉ xem events mà họ là participant hoặc qua task receivers
            // Calendar có thể liên kết với task, kiểm tra qua task receivers
            $query->where(function ($q) use ($userId) {
                $q->whereHas('task', function ($taskQuery) use ($userId) {
                    $taskQuery->whereHas('receivers', function ($receiverQuery) use ($userId) {
                        $receiverQuery->where('receiver_id', $userId)
                                     ->where('receiver_type', 'student');
                    })->orWhereHas('receivers', function ($receiverQuery) {
                        $receiverQuery->where('receiver_type', 'all_students');
                    });
                })->orWhereNull('task_id'); // Events không có task (cho tất cả)
            });
        } elseif ($userType === 'lecturer' && $userId) {
            // Lecturer chỉ xem events mà họ tạo hoặc tham gia qua task
            $query->where(function ($q) use ($userId) {
                $q->where(function ($q2) use ($userId) {
                    $q2->where('creator_id', $userId)
                       ->where('creator_type', 'lecturer');
                })->orWhereHas('task', function ($taskQuery) use ($userId) {
                    $taskQuery->where('creator_id', $userId)
                             ->where('creator_type', 'lecturer')
                             ->orWhereHas('receivers', function ($receiverQuery) use ($userId) {
                                 $receiverQuery->where('receiver_id', $userId)
                                              ->where('receiver_type', 'lecturer');
                             })->orWhereHas('receivers', function ($receiverQuery) {
                                 $receiverQuery->where('receiver_type', 'all_lecturers');
                             });
                });
            });
        }

        $events = $query->get();

        return $events->map(function ($event) {
            return $this->formatCalendarEvent($event);
        });
    }

    /**
     * Lấy deadline từ tasks
     */
    private function getTaskDeadlines(
        Carbon $start,
        Carbon $end,
        ?int $userId,
        ?string $userType
    ): Collection {
        $query = Task::whereNotNull('deadline')
            ->whereBetween('deadline', [$start, $end])
            ->where('status', '!=', 'completed'); // Chỉ lấy task chưa hoàn thành

        // Filter theo user
        if ($userType === 'student' && $userId) {
            $query->where(function ($q) use ($userId) {
                $q->whereHas('receivers', function ($receiverQuery) use ($userId) {
                    $receiverQuery->where('receiver_id', $userId)
                                 ->where('receiver_type', 'student');
                })->orWhereHas('receivers', function ($receiverQuery) {
                    // Tasks gửi cho tất cả students
                    $receiverQuery->where('receiver_type', 'all_students');
                });
            });
        } elseif ($userType === 'lecturer' && $userId) {
            $query->where(function ($q) use ($userId) {
                $q->where(function ($q2) use ($userId) {
                    // Tasks do lecturer tạo
                    $q2->where('creator_id', $userId)
                       ->where('creator_type', 'lecturer');
                })->orWhereHas('receivers', function ($receiverQuery) use ($userId) {
                    // Tasks gửi cho lecturer cụ thể
                    $receiverQuery->where('receiver_id', $userId)
                                 ->where('receiver_type', 'lecturer');
                })->orWhereHas('receivers', function ($receiverQuery) {
                    // Tasks gửi cho tất cả lecturers
                    $receiverQuery->where('receiver_type', 'all_lecturers');
                });
            });
        }

        $tasks = $query->get();

        return $tasks->map(function ($task) {
            return $this->formatTaskDeadline($task);
        });
    }

    /**
     * Lấy danh sách course IDs mà user có quyền truy cập
     * 
     * @param int|null $userId
     * @param string|null $userType
     * @return array|null null = tất cả (admin), array = danh sách IDs
     */
    private function getAccessibleCourseIds(?int $userId, ?string $userType): ?array
    {
        if ($userType === 'admin' || !$userId) {
            return null; // Admin xem tất cả
        }

        if ($userType === 'student') {
            // Student chỉ xem môn đã đăng ký
            $enrollments = $this->enrollmentRepository->getByStudent($userId);
            return $enrollments->pluck('course_id')->unique()->toArray();
        }

        if ($userType === 'lecturer') {
            // Lecturer chỉ xem môn đang dạy
            $courses = $this->courseRepository->getByLecturer($userId);
            return $courses->pluck('id')->toArray();
        }

        return []; // Không có quyền
    }

    /**
     * Format attendance session thành item timetable
     */
    private function formatClassSession(AttendanceSession $session): array
    {
        $course = $session->course;
        
        // Đảm bảo start_time và end_time là string
        $startTime = is_string($session->start_time) ? $session->start_time : (string) $session->start_time;
        $endTime = is_string($session->end_time) ? $session->end_time : (string) $session->end_time;
        
        $startDateTime = Carbon::parse($session->session_date->format('Y-m-d') . ' ' . $startTime);
        $endDateTime = Carbon::parse($session->session_date->format('Y-m-d') . ' ' . $endTime);

        $periods = $this->mapTimeToPeriod($startTime, $endTime);

        return [
            'type' => 'class_session',
            'id' => $session->id,
            'title' => $course->name . ($course->code ? ' (' . $course->code . ')' : ''),
            'room' => $session->room ?? $course->room,
            'start' => $startDateTime->format('Y-m-d H:i:s'),
            'end' => $endDateTime->format('Y-m-d H:i:s'),
            'status' => $session->status,
            'course_id' => $course->id,
            'course_code' => $course->code,
            'course_name' => $course->name,
            'periods' => $periods,
            'session_number' => $session->session_number,
            'topic' => $session->topic,
        ];
    }

    /**
     * Format course thành session (fallback)
     */
    private function formatCourseAsSession(Course $course, Carbon $date): array
    {
        // Đảm bảo start_time và end_time là string
        $startTime = is_string($course->start_time) ? $course->start_time : (string) $course->start_time;
        $endTime = is_string($course->end_time) ? $course->end_time : (string) $course->end_time;
        
        $startDateTime = Carbon::parse($date->format('Y-m-d') . ' ' . $startTime);
        $endDateTime = Carbon::parse($date->format('Y-m-d') . ' ' . $endTime);

        $periods = $this->mapTimeToPeriod($startTime, $endTime);

        return [
            'type' => 'class_session',
            'id' => 'course_' . $course->id . '_' . $date->format('Ymd'),
            'title' => $course->name . ($course->code ? ' (' . $course->code . ')' : ''),
            'room' => $course->room,
            'start' => $startDateTime->format('Y-m-d H:i:s'),
            'end' => $endDateTime->format('Y-m-d H:i:s'),
            'status' => 'scheduled',
            'course_id' => $course->id,
            'course_code' => $course->code,
            'course_name' => $course->name,
            'periods' => $periods,
            'is_fallback' => true, // Đánh dấu là dữ liệu fallback
        ];
    }

    /**
     * Format calendar event thành item timetable
     */
    private function formatCalendarEvent(Calendar $event): array
    {
        return [
            'type' => 'event',
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'start' => $event->start_time->format('Y-m-d H:i:s'),
            'end' => $event->end_time->format('Y-m-d H:i:s'),
            'event_type' => $event->event_type,
            'task_id' => $event->task_id,
        ];
    }

    /**
     * Format task deadline thành item timetable
     */
    private function formatTaskDeadline(Task $task): array
    {
        return [
            'type' => 'task_deadline',
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'start' => $task->deadline->format('Y-m-d H:i:s'),
            'status' => $task->status,
            'priority' => $task->priority,
            'deadline' => $task->deadline->format('Y-m-d H:i:s'),
        ];
    }
}
