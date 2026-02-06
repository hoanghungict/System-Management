<?php

namespace Modules\Auth\app\Services\AttendanceService;

use Modules\Auth\app\Repositories\AttendanceRepository\CourseEnrollmentRepository;
use Modules\Auth\app\Repositories\AttendanceRepository\AttendanceSessionRepository;
use Modules\Auth\app\Repositories\AttendanceRepository\AttendanceRepository;
use Modules\Auth\app\Repositories\AttendanceRepository\CourseRepository;
use Modules\Auth\app\Models\Attendance\CourseEnrollment;
use Modules\Auth\app\Models\Attendance\Attendance;
use Modules\Auth\app\Models\Student;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Service quản lý đăng ký môn học (Enrollment)
 */
class EnrollmentService
{
    protected CourseEnrollmentRepository $enrollmentRepository;
    protected CourseRepository $courseRepository;
    protected AttendanceSessionRepository $sessionRepository;
    protected AttendanceRepository $attendanceRepository;

    public function __construct(
        CourseEnrollmentRepository $enrollmentRepository,
        CourseRepository $courseRepository,
        AttendanceSessionRepository $sessionRepository,
        AttendanceRepository $attendanceRepository
    ) {
        $this->enrollmentRepository = $enrollmentRepository;
        $this->courseRepository = $courseRepository;
        $this->sessionRepository = $sessionRepository;
        $this->attendanceRepository = $attendanceRepository;
    }

    /**
     * Lấy danh sách sinh viên trong môn
     */
    public function getCourseEnrollments(int $courseId): Collection
    {
        return $this->enrollmentRepository->getByCourse($courseId);
    }

    /**
     * Đăng ký 1 sinh viên vào môn
     */
    public function enrollStudent(int $courseId, int $studentId, int $adminId, ?string $note = null): CourseEnrollment
    {
        $course = $this->courseRepository->findById($courseId);
        
        if (!$course) {
            throw new \Exception('Không tìm thấy môn học');
        }

        // Kiểm tra đã đăng ký chưa
        if ($this->enrollmentRepository->isEnrolled($courseId, $studentId)) {
            throw new \Exception('Sinh viên đã đăng ký môn học này');
        }

        $enrolledAt = now()->toDateString();
        $isLateEnrollment = $enrolledAt > $course->start_date->toDateString();

        DB::beginTransaction();

        try {
            // Tạo enrollment
            $enrollment = $this->enrollmentRepository->create([
                'course_id' => $courseId,
                'student_id' => $studentId,
                'enrolled_at' => $enrolledAt,
                'status' => CourseEnrollment::STATUS_ACTIVE,
                'note' => $isLateEnrollment ? ($note ?? 'Đăng ký muộn') : $note,
            ]);

            // Tạo attendance records cho các buổi học
            $this->createAttendanceRecordsForEnrollment($enrollment, $adminId, $isLateEnrollment);

            DB::commit();

            /* Log::info('Student enrolled', [
                'course_id' => $courseId,
                'student_id' => $studentId,
                'is_late' => $isLateEnrollment,
            ]); */

            return $enrollment;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Đăng ký nhiều sinh viên vào môn (bulk)
     */
    public function enrollStudentsBulk(int $courseId, array $studentIds, int $adminId): array
    {
        $results = [
            'success' => [],
            'failed' => [],
            'already_enrolled' => [],
        ];

        foreach ($studentIds as $studentId) {
            try {
                // Kiểm tra đã đăng ký chưa
                if ($this->enrollmentRepository->isEnrolled($courseId, $studentId)) {
                    $results['already_enrolled'][] = $studentId;
                    continue;
                }

                $this->enrollStudent($courseId, $studentId, $adminId);
                $results['success'][] = $studentId;

            } catch (\Exception $e) {
                $results['failed'][] = [
                    'student_id' => $studentId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        /* Log::info('Bulk enrollment completed', [
            'course_id' => $courseId,
            'success_count' => count($results['success']),
            'failed_count' => count($results['failed']),
            'already_enrolled_count' => count($results['already_enrolled']),
        ]); */

        return $results;
    }

    /**
     * Tạo attendance records cho enrollment
     * Nếu đăng ký muộn: các buổi trước = excused (pass)
     */
    private function createAttendanceRecordsForEnrollment(CourseEnrollment $enrollment, int $adminId, bool $isLateEnrollment): void
    {
        $sessions = $this->sessionRepository->getByCourse($enrollment->course_id);
        
        if ($sessions->isEmpty()) {
            return;
        }

        $now = now();
        $enrolledDate = $enrollment->enrolled_at;
        $attendances = [];

        foreach ($sessions as $session) {
            $status = Attendance::STATUS_NOT_MARKED;
            $note = null;

            // Nếu đăng ký muộn và buổi học đã qua ngày đăng ký
            if ($isLateEnrollment && $session->session_date < $enrolledDate) {
                $status = Attendance::STATUS_EXCUSED;
                $note = 'Đăng ký muộn - tự động pass';
            }

            $attendances[] = [
                'session_id' => $session->id,
                'student_id' => $enrollment->student_id,
                'status' => $status,
                'note' => $note,
                'marked_by' => $status === Attendance::STATUS_EXCUSED ? $adminId : null,
                'marked_at' => $status === Attendance::STATUS_EXCUSED ? $now : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->attendanceRepository->createMany($attendances);
    }

    /**
     * Hủy đăng ký sinh viên khỏi môn
     */
    public function unenrollStudent(int $courseId, int $studentId, ?string $reason = null): bool
    {
        $result = $this->enrollmentRepository->drop($courseId, $studentId, $reason);

        if ($result) {
            /* Log::info('Student unenrolled', [
                'course_id' => $courseId,
                'student_id' => $studentId,
                'reason' => $reason,
            ]); */
        }

        return $result;
    }

    /**
     * Thêm sinh viên đăng ký muộn (với logic pass các buổi trước)
     * Đây là method riêng cho chức năng "Thêm SV đăng ký muộn"
     */
    public function addLateEnrollment(int $courseId, int $studentId, int $adminId, ?string $note = null): CourseEnrollment
    {
        return $this->enrollStudent($courseId, $studentId, $adminId, $note ?? 'Đăng ký muộn');
    }

    /**
     * Lấy danh sách môn của sinh viên
     */
    public function getStudentCourses(int $studentId): Collection
    {
        return $this->enrollmentRepository->getByStudent($studentId);
    }

    /**
     * Kiểm tra sinh viên có trong môn không
     */
    public function isStudentEnrolled(int $courseId, int $studentId): bool
    {
        return $this->enrollmentRepository->isEnrolled($courseId, $studentId);
    }
}
