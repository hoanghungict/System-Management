<?php

namespace Modules\Auth\app\Services\AttendanceService;

use Modules\Auth\app\Repositories\AttendanceRepository\CourseRepository;
use Modules\Auth\app\Repositories\AttendanceRepository\AttendanceSessionRepository;
use Modules\Auth\app\Repositories\AttendanceRepository\CourseEnrollmentRepository;
use Modules\Auth\app\Repositories\AttendanceRepository\AttendanceRepository;
use Modules\Auth\app\Models\Attendance\Course;
use Modules\Auth\app\Models\Attendance\AttendanceSession;
use Modules\Auth\app\Models\Attendance\Attendance;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service quản lý Môn học và tự động tạo lịch điểm danh
 */
class CourseService
{
    protected CourseRepository $courseRepository;
    protected AttendanceSessionRepository $sessionRepository;
    protected CourseEnrollmentRepository $enrollmentRepository;
    protected AttendanceRepository $attendanceRepository;

    public function __construct(
        CourseRepository $courseRepository,
        AttendanceSessionRepository $sessionRepository,
        CourseEnrollmentRepository $enrollmentRepository,
        AttendanceRepository $attendanceRepository
    ) {
        $this->courseRepository = $courseRepository;
        $this->sessionRepository = $sessionRepository;
        $this->enrollmentRepository = $enrollmentRepository;
        $this->attendanceRepository = $attendanceRepository;
    }

    /**
     * Lấy danh sách môn học với filter
     */
    public function getCourses(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->courseRepository->paginate($filters, $perPage);
    }

    /**
     * Lấy chi tiết môn học
     */
    public function getCourseById(int $id): ?Course
    {
        return $this->courseRepository->findById($id);
    }

    /**
     * Lấy môn học của giảng viên
     */
    public function getCoursesByLecturer(int $lecturerId, ?int $semesterId = null): Collection
    {
        return $this->courseRepository->getByLecturer($lecturerId, $semesterId);
    }

    /**
     * Lấy môn học đang hoạt động của GV
     */
    public function getActiveCoursesByLecturer(int $lecturerId): Collection
    {
        return $this->courseRepository->getActiveCoursesByLecturer($lecturerId);
    }

    /**
     * Tạo môn học mới và tự động sinh lịch điểm danh
     */
    public function createCourse(array $data, bool $generateSessions = true): Course
    {
        DB::beginTransaction();

        try {
            // Tạo môn học
            $course = $this->courseRepository->create($data);

            Log::info('Course created', [
                'course_id' => $course->id,
                'code' => $course->code,
                'name' => $course->name,
            ]);

            // Tự động tạo lịch điểm danh nếu được yêu cầu
            if ($generateSessions && !empty($data['schedule_days'])) {
                $this->generateSessions($course);
            }

            DB::commit();

            return $course->fresh(['semester', 'lecturer', 'sessions']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create course', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Cập nhật môn học
     */
    public function updateCourse(int $id, array $data): bool
    {
        $result = $this->courseRepository->update($id, $data);

        if ($result) {
            Log::info('Course updated', ['course_id' => $id]);
        }

        return $result;
    }

    /**
     * Xóa môn học
     */
    public function deleteCourse(int $id): bool
    {
        $course = $this->courseRepository->findById($id);
        
        if (!$course) {
            return false;
        }

        $result = $this->courseRepository->delete($id);

        if ($result) {
            Log::info('Course deleted', ['course_id' => $id]);
        }

        return $result;
    }

    /**
     * TỰ ĐỘNG TẠO LỊCH ĐIỂM DANH
     * Dựa trên thời khóa biểu (schedule_days) và thời gian học
     */
    public function generateSessions(Course $course): int
    {
        if (empty($course->schedule_days) || !$course->start_date || !$course->end_date) {
            throw new \Exception('Thiếu thông tin thời khóa biểu để tạo lịch');
        }

        // Xóa các session cũ (nếu có)
        $this->sessionRepository->deleteByCourse($course->id);

        $sessions = [];
        $sessionNumber = 1;
        $currentDate = Carbon::parse($course->start_date);
        $endDate = Carbon::parse($course->end_date);
        $scheduleDays = $course->schedule_days;
        $now = now();

        while ($currentDate->lte($endDate)) {
            // Lấy ngày trong tuần (1 = Monday, 7 = Sunday)
            // Laravel/Carbon: 1 = Monday, 7 = Sunday
            // Nhưng yêu cầu: 2 = Thứ 2 (Monday), ..., 8 = CN
            $dayOfWeek = $currentDate->dayOfWeekIso + 1; // +1 để chuyển từ 1-7 sang 2-8

            // Kiểm tra ngày có trong lịch học không
            if (in_array($dayOfWeek, $scheduleDays)) {
                $sessions[] = [
                    'course_id' => $course->id,
                    'session_number' => $sessionNumber,
                    'session_date' => $currentDate->toDateString(),
                    'day_of_week' => $dayOfWeek,
                    'start_time' => $course->start_time,
                    'end_time' => $course->end_time,
                    'room' => $course->room,
                    'status' => AttendanceSession::STATUS_SCHEDULED,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $sessionNumber++;
            }

            $currentDate->addDay();
        }

        // Insert tất cả sessions
        if (!empty($sessions)) {
            $this->sessionRepository->createMany($sessions);
        }

        // Cập nhật trạng thái đã tạo lịch
        $this->courseRepository->update($course->id, [
            'sessions_generated' => true,
            'total_sessions' => count($sessions),
        ]);

        Log::info('Sessions generated for course', [
            'course_id' => $course->id,
            'sessions_count' => count($sessions),
        ]);

        return count($sessions);
    }

    /**
     * Tái tạo lịch điểm danh (xóa cũ và tạo mới)
     */
    public function regenerateSessions(int $courseId): int
    {
        $course = $this->courseRepository->findById($courseId);
        
        if (!$course) {
            throw new \Exception('Không tìm thấy môn học');
        }

        return $this->generateSessions($course);
    }

    /**
     * Lấy danh sách buổi học của môn
     */
    public function getCourseSessions(int $courseId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->sessionRepository->paginateByCourse($courseId, $perPage);
    }

    /**
     * Lấy danh sách sinh viên trong môn
     */
    public function getCourseStudents(int $courseId): Collection
    {
        return $this->enrollmentRepository->getActiveStudentsByCourse($courseId);
    }

    /**
     * Đếm số sinh viên trong môn
     */
    public function countCourseStudents(int $courseId): int
    {
        return $this->enrollmentRepository->countByCourse($courseId, 'active');
    }

    /**
     * Lấy thống kê môn học
     */
    public function getCourseStatistics(int $courseId): array
    {
        $course = $this->courseRepository->findById($courseId);
        
        if (!$course) {
            throw new \Exception('Không tìm thấy môn học');
        }

        $sessions = $course->sessions;
        $enrollments = $this->enrollmentRepository->getActiveStudentsByCourse($courseId);

        return [
            'course' => [
                'id' => $course->id,
                'code' => $course->code,
                'name' => $course->name,
                'max_absences' => $course->max_absences,
            ],
            'sessions' => [
                'total' => $sessions->count(),
                'completed' => $sessions->where('status', 'completed')->count(),
                'scheduled' => $sessions->where('status', 'scheduled')->count(),
                'cancelled' => $sessions->where('status', 'cancelled')->count(),
            ],
            'students' => [
                'total' => $enrollments->count(),
            ],
        ];
    }
}
