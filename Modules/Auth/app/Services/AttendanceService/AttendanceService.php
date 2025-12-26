<?php

namespace Modules\Auth\app\Services\AttendanceService;

use Modules\Auth\app\Repositories\AttendanceRepository\AttendanceSessionRepository;
use Modules\Auth\app\Repositories\AttendanceRepository\AttendanceRepository;
use Modules\Auth\app\Repositories\AttendanceRepository\CourseRepository;
use Modules\Auth\app\Repositories\AttendanceRepository\CourseEnrollmentRepository;
use Modules\Auth\app\Models\Attendance\AttendanceSession;
use Modules\Auth\app\Models\Attendance\Attendance;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service xử lý điểm danh
 */
class AttendanceService
{
    protected AttendanceSessionRepository $sessionRepository;
    protected AttendanceRepository $attendanceRepository;
    protected CourseRepository $courseRepository;
    protected CourseEnrollmentRepository $enrollmentRepository;

    public function __construct(
        AttendanceSessionRepository $sessionRepository,
        AttendanceRepository $attendanceRepository,
        CourseRepository $courseRepository,
        CourseEnrollmentRepository $enrollmentRepository
    ) {
        $this->sessionRepository = $sessionRepository;
        $this->attendanceRepository = $attendanceRepository;
        $this->courseRepository = $courseRepository;
        $this->enrollmentRepository = $enrollmentRepository;
    }

    /**
     * Lấy chi tiết buổi học với danh sách điểm danh
     */
    public function getSessionDetails(int $sessionId): ?AttendanceSession
    {
        return $this->sessionRepository->findById($sessionId);
    }

    /**
     * Bắt đầu điểm danh buổi học
     */
    public function startSession(int $sessionId, int $lecturerId): AttendanceSession
    {
        $session = $this->sessionRepository->findById($sessionId);

        if (!$session) {
            throw new \Exception('Không tìm thấy buổi học');
        }

        if ($session->status !== AttendanceSession::STATUS_SCHEDULED) {
            throw new \Exception('Buổi học không ở trạng thái có thể bắt đầu điểm danh');
        }

        // Kiểm tra quyền: chỉ GV của môn mới được điểm danh
        if ($session->course->lecturer_id !== $lecturerId) {
            throw new \Exception('Bạn không có quyền điểm danh buổi học này');
        }

        DB::beginTransaction();

        try {
            // Cập nhật trạng thái session
            $session->start($lecturerId);

            // Tạo attendance records cho tất cả sinh viên (nếu chưa có)
            $this->createAttendanceRecordsForSession($session);

            DB::commit();

            Log::info('Session started', [
                'session_id' => $sessionId,
                'lecturer_id' => $lecturerId,
            ]);

            return $session->fresh(['attendances.student']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Tạo attendance records cho tất cả sinh viên trong buổi học
     */
    private function createAttendanceRecordsForSession(AttendanceSession $session): void
    {
        // Lấy danh sách sinh viên đã đăng ký môn
        $studentIds = $this->enrollmentRepository->getStudentIdsByCourse($session->course_id);

        // Lấy danh sách sinh viên đã có attendance
        $existingStudentIds = $session->attendances->pluck('student_id')->toArray();

        // Chỉ tạo cho sinh viên chưa có
        $newStudentIds = array_diff($studentIds, $existingStudentIds);

        if (empty($newStudentIds)) {
            return;
        }

        $now = now();
        $attendances = [];

        foreach ($newStudentIds as $studentId) {
            $attendances[] = [
                'session_id' => $session->id,
                'student_id' => $studentId,
                'status' => Attendance::STATUS_NOT_MARKED,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->attendanceRepository->createMany($attendances);
    }

    /**
     * Cập nhật điểm danh 1 sinh viên
     */
    public function updateAttendance(
        int $sessionId,
        int $studentId,
        string $status,
        int $lecturerId,
        array $additionalData = []
    ): bool {
        $session = $this->sessionRepository->findById($sessionId);

        if (!$session) {
            throw new \Exception('Không tìm thấy buổi học');
        }

        // Kiểm tra quyền sửa
        // GV chỉ được sửa khi chưa completed
        // Admin được sửa mọi lúc (check ở controller)
        if ($session->isCompleted()) {
            throw new \Exception('Buổi học đã hoàn thành. Chỉ Admin mới được sửa.');
        }

        $data = array_merge([
            'status' => $status,
            'marked_by' => $lecturerId,
            'marked_at' => now(),
        ], $additionalData);

        $result = $this->attendanceRepository->updateBySessionAndStudent($sessionId, $studentId, $data);

        if ($result) {
            Log::info('Attendance updated', [
                'session_id' => $sessionId,
                'student_id' => $studentId,
                'status' => $status,
                'marked_by' => $lecturerId,
            ]);
        }

        return $result;
    }

    /**
     * Cập nhật điểm danh hàng loạt
     */
    public function bulkUpdateAttendance(
        int $sessionId,
        array $studentStatuses,
        int $lecturerId
    ): int {
        $session = $this->sessionRepository->findById($sessionId);

        if (!$session) {
            throw new \Exception('Không tìm thấy buổi học');
        }

        if ($session->isCompleted()) {
            throw new \Exception('Buổi học đã hoàn thành. Chỉ Admin mới được sửa.');
        }

        $now = now();
        $updated = 0;

        DB::beginTransaction();

        try {
            foreach ($studentStatuses as $item) {
                $data = [
                    'status' => $item['status'],
                    'marked_by' => $lecturerId,
                    'marked_at' => $now,
                ];

                if (!empty($item['note'])) {
                    $data['note'] = $item['note'];
                }

                if (!empty($item['minutes_late'])) {
                    $data['minutes_late'] = $item['minutes_late'];
                }

                if ($this->attendanceRepository->updateBySessionAndStudent($sessionId, $item['student_id'], $data)) {
                    $updated++;
                }
            }

            DB::commit();

            Log::info('Bulk attendance updated', [
                'session_id' => $sessionId,
                'updated_count' => $updated,
                'marked_by' => $lecturerId,
            ]);

            return $updated;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Đánh dấu tất cả có mặt
     */
    public function markAllPresent(int $sessionId, int $lecturerId): int
    {
        $session = $this->sessionRepository->findById($sessionId);

        if (!$session) {
            throw new \Exception('Không tìm thấy buổi học');
        }

        if ($session->isCompleted()) {
            throw new \Exception('Buổi học đã hoàn thành');
        }

        $count = $this->attendanceRepository->markAllPresent($sessionId, $lecturerId);

        Log::info('All marked present', [
            'session_id' => $sessionId,
            'count' => $count,
            'marked_by' => $lecturerId,
        ]);

        return $count;
    }

    /**
     * Hoàn thành buổi điểm danh
     */
    public function completeSession(int $sessionId, int $lecturerId): bool
    {
        $session = $this->sessionRepository->findById($sessionId);

        if (!$session) {
            throw new \Exception('Không tìm thấy buổi học');
        }

        if ($session->status !== AttendanceSession::STATUS_IN_PROGRESS) {
            throw new \Exception('Buổi học không ở trạng thái đang điểm danh');
        }

        // Tự động đánh dấu những SV chưa điểm danh thành vắng
        $this->attendanceRepository->getBySession($sessionId)
            ->where('status', Attendance::STATUS_NOT_MARKED)
            ->each(function ($attendance) use ($lecturerId) {
                $attendance->markAbsent($lecturerId, 'Tự động đánh vắng khi hoàn thành');
            });

        $result = $session->complete();

        if ($result) {
            Log::info('Session completed', [
                'session_id' => $sessionId,
                'lecturer_id' => $lecturerId,
            ]);
        }

        return $result;
    }

    /**
     * Hủy buổi học
     */
    public function cancelSession(int $sessionId): bool
    {
        $session = $this->sessionRepository->findById($sessionId);

        if (!$session) {
            throw new \Exception('Không tìm thấy buổi học');
        }

        $result = $session->cancel();

        if ($result) {
            Log::info('Session cancelled', ['session_id' => $sessionId]);
        }

        return $result;
    }

    /**
     * Đổi ngày buổi học
     */
    public function rescheduleSession(int $sessionId, string $newDate, ?string $newStartTime = null, ?string $newEndTime = null): bool
    {
        $session = $this->sessionRepository->findById($sessionId);

        if (!$session) {
            throw new \Exception('Không tìm thấy buổi học');
        }

        $data = [
            'session_date' => $newDate,
            'day_of_week' => \Carbon\Carbon::parse($newDate)->dayOfWeekIso + 1,
        ];

        if ($newStartTime) {
            $data['start_time'] = $newStartTime;
        }

        if ($newEndTime) {
            $data['end_time'] = $newEndTime;
        }

        $result = $this->sessionRepository->update($sessionId, $data);

        if ($result) {
            Log::info('Session rescheduled', [
                'session_id' => $sessionId,
                'new_date' => $newDate,
            ]);
        }

        return $result;
    }

    /**
     * ADMIN: Sửa điểm danh sau khi completed
     */
    public function adminUpdateAttendance(
        int $attendanceId,
        string $status,
        int $adminId,
        array $additionalData = []
    ): bool {
        $data = array_merge([
            'status' => $status,
            'marked_by' => $adminId,
            'marked_at' => now(),
        ], $additionalData);

        $result = $this->attendanceRepository->update($attendanceId, $data);

        if ($result) {
            Log::info('Admin updated attendance', [
                'attendance_id' => $attendanceId,
                'status' => $status,
                'admin_id' => $adminId,
            ]);
        }

        return $result;
    }

    /**
     * Thống kê điểm danh của sinh viên trong môn
     */
    public function getStudentAttendanceStats(int $studentId, int $courseId): array
    {
        $course = $this->courseRepository->findById($courseId);
        
        if (!$course) {
            throw new \Exception('Không tìm thấy môn học');
        }

        $stats = $this->attendanceRepository->getStudentStatsInCourse($studentId, $courseId);
        $absentCount = $stats['absent'];
        $maxAbsences = $course->max_absences;
        $warningThreshold = $course->absence_warning;

        return [
            'student_id' => $studentId,
            'course_id' => $courseId,
            'course_name' => $course->name,
            'attendance' => $stats,
            'max_absences' => $maxAbsences,
            'remaining_absences' => max(0, $maxAbsences - $absentCount),
            'is_warning' => $absentCount >= $warningThreshold,
            'is_exceeded' => $absentCount > $maxAbsences,
            'status' => $absentCount > $maxAbsences ? 'exceeded' : ($absentCount >= $warningThreshold ? 'warning' : 'ok'),
        ];
    }

    /**
     * Lấy danh sách sinh viên có nguy cơ (gần/vượt số buổi nghỉ)
     */
    public function getAtRiskStudents(int $courseId): array
    {
        $course = $this->courseRepository->findById($courseId);
        
        if (!$course) {
            throw new \Exception('Không tìm thấy môn học');
        }

        $enrollments = $this->enrollmentRepository->getActiveStudentsByCourse($courseId);
        $atRiskStudents = [];

        foreach ($enrollments as $enrollment) {
            $stats = $this->getStudentAttendanceStats($enrollment->student_id, $courseId);
            
            if ($stats['is_warning'] || $stats['is_exceeded']) {
                $atRiskStudents[] = [
                    'student' => $enrollment->student,
                    'stats' => $stats,
                ];
            }
        }

        // Sắp xếp theo số buổi vắng giảm dần
        usort($atRiskStudents, function ($a, $b) {
            return $b['stats']['attendance']['absent'] - $a['stats']['attendance']['absent'];
        });

        return $atRiskStudents;
    }
}
