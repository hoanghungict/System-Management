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
        $session = $this->sessionRepository->findById($sessionId);
        
        if (!$session) {
            return null;
        }
        
        // Nếu session đang in_progress nhưng không có attendances, tự động tạo lại
        if ($session->status === AttendanceSession::STATUS_IN_PROGRESS) {
            $attendanceCount = $session->attendances->count();
            $enrollmentCount = count($this->enrollmentRepository->getStudentIdsByCourse($session->course_id));
            
            if ($attendanceCount === 0 && $enrollmentCount > 0) {
                Log::warning('Session in_progress but no attendances, creating attendance records', [
                    'session_id' => $sessionId,
                    'course_id' => $session->course_id,
                    'enrollment_count' => $enrollmentCount,
                ]);
                
                // Tạo attendance records
                $this->createAttendanceRecordsForSession($session);
                
                // Refresh để load attendances mới tạo
                $session->refresh();
                $session->load(['attendances.student']);
            }
        }
        
        return $session;
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

            // Refresh session để load attendances mới tạo
            $session->refresh();
            $session->load(['attendances.student']);

            // Verify attendances were created
            $attendanceCount = $session->attendances->count();
            $enrollmentCount = $this->enrollmentRepository->getStudentIdsByCourse($session->course_id);
            
            Log::info('Session started', [
                'session_id' => $sessionId,
                'lecturer_id' => $lecturerId,
                'attendance_count' => $attendanceCount,
                'enrollment_count' => count($enrollmentCount),
            ]);

            if ($attendanceCount === 0 && count($enrollmentCount) > 0) {
                Log::warning('No attendance records found after creation', [
                    'session_id' => $sessionId,
                    'course_id' => $session->course_id,
                    'enrollment_count' => count($enrollmentCount),
                ]);
            }

            return $session;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error starting session', [
                'session_id' => $sessionId,
                'lecturer_id' => $lecturerId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
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

        Log::info('Creating attendance records for session', [
            'session_id' => $session->id,
            'course_id' => $session->course_id,
            'student_ids_count' => count($studentIds),
            'student_ids' => $studentIds,
        ]);

        // Lấy danh sách sinh viên đã có attendance
        $existingStudentIds = $session->attendances->pluck('student_id')->toArray();

        Log::info('Existing attendance records', [
            'session_id' => $session->id,
            'existing_count' => count($existingStudentIds),
            'existing_ids' => $existingStudentIds,
        ]);

        // Chỉ tạo cho sinh viên chưa có
        $newStudentIds = array_diff($studentIds, $existingStudentIds);

        Log::info('New students to create attendance', [
            'session_id' => $session->id,
            'new_count' => count($newStudentIds),
            'new_ids' => $newStudentIds,
        ]);

        if (empty($newStudentIds)) {
            Log::warning('No new students to create attendance records', [
                'session_id' => $session->id,
                'total_enrolled' => count($studentIds),
                'existing_attendance' => count($existingStudentIds),
            ]);
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

        try {
            $result = $this->attendanceRepository->createMany($attendances);
            
            if ($result) {
                Log::info('Attendance records created successfully', [
                    'session_id' => $session->id,
                    'created_count' => count($attendances),
                    'student_ids' => $newStudentIds,
                ]);
            } else {
                Log::error('Failed to create attendance records - createMany returned false', [
                    'session_id' => $session->id,
                    'attempted_count' => count($attendances),
                    'student_ids' => $newStudentIds,
                ]);
                throw new \Exception('Không thể tạo attendance records');
            }
            
            // Verify records were actually created
            $createdCount = \Modules\Auth\app\Models\Attendance\Attendance::where('session_id', $session->id)
                ->whereIn('student_id', $newStudentIds)
                ->count();
            
            if ($createdCount !== count($newStudentIds)) {
                Log::error('Attendance records count mismatch', [
                    'session_id' => $session->id,
                    'expected' => count($newStudentIds),
                    'actual' => $createdCount,
                ]);
                throw new \Exception("Chỉ tạo được {$createdCount} / " . count($newStudentIds) . " attendance records");
            }
            
        } catch (\Exception $e) {
            Log::error('Error creating attendance records', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'student_ids' => $newStudentIds,
            ]);
            throw $e;
        }
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
     * Cập nhật thông tin buổi học (ví dụ: ca học/shift)
     */
    public function updateSession(int $sessionId, array $data): bool
    {
        $session = $this->sessionRepository->findById($sessionId);
        if (!$session) {
            throw new \Exception('Không tìm thấy buổi học');
        }

        return $this->sessionRepository->update($sessionId, $data);
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

    /**
     * Lấy tổng hợp điểm danh của môn học
     * 
     * Trả về dữ liệu dạng bảng tổng hợp:
     * - Cột: các buổi học trong học kỳ
     * - Hàng: danh sách sinh viên với trạng thái điểm danh mỗi buổi
     */
    public function getCourseSummary(int $courseId): array
    {
        $course = $this->courseRepository->findById($courseId);
        
        if (!$course) {
            throw new \Exception('Không tìm thấy môn học');
        }

        // Lấy tất cả buổi học của môn, sắp xếp theo số buổi
        $sessions = $this->sessionRepository->getByCourse($courseId)
            ->sortBy('session_number')
            ->values();

        // Lấy tất cả sinh viên đang đăng ký môn
        $enrollments = $this->enrollmentRepository->getActiveStudentsByCourse($courseId);

        // Lấy tất cả attendance records của môn này
        $allAttendances = $this->attendanceRepository->getByCourseId($courseId);

        // Build students data với attendance per session
        $studentsData = [];
        
        foreach ($enrollments as $enrollment) {
            $student = $enrollment->student;
            $studentAttendances = $allAttendances->where('student_id', $student->id);
            
            // Build attendance map: session_id => status
            $attendanceMap = [];
            $stats = [
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'excused' => 0,
                'not_marked' => 0,
            ];
            
            foreach ($sessions as $session) {
                $attendance = $studentAttendances->firstWhere('session_id', $session->id);
                $status = $attendance ? $attendance->status : 'not_enrolled';
                $attendanceMap[(string)$session->id] = $status;
                
                // Count stats (only for completed sessions)
                if ($attendance && in_array($session->status, ['completed', 'in_progress'])) {
                    if (isset($stats[$status])) {
                        $stats[$status]++;
                    }
                }
            }
            
            // Calculate attendance rate
            $totalMarked = $stats['present'] + $stats['absent'] + $stats['late'] + $stats['excused'];
            $attended = $stats['present'] + $stats['late'];
            $attendanceRate = $totalMarked > 0 ? round(($attended / $totalMarked) * 100, 2) : 0;
            
            // Check if at risk
            $isAtRisk = $stats['absent'] >= ($course->absence_warning ?? 3);
            $isExceeded = $stats['absent'] > ($course->max_absences ?? 5);
            
            $studentsData[] = [
                'id' => $student->id,
                'student_code' => $student->student_code,
                'name' => $student->full_name,
                'class' => $student->class->class_name ?? null,
                'attendance' => $attendanceMap,
                'summary' => [
                    'total_sessions' => $sessions->count(),
                    'present' => $stats['present'],
                    'late' => $stats['late'],
                    'absent' => $stats['absent'],
                    'excused' => $stats['excused'],
                    'not_marked' => $stats['not_marked'],
                    'attendance_rate' => $attendanceRate,
                    'is_at_risk' => $isAtRisk,
                    'is_exceeded' => $isExceeded,
                ],
            ];
        }

        // Sort students by name
        usort($studentsData, fn($a, $b) => strcmp($a['name'], $b['name']));

        // Build sessions response
        $sessionsData = $sessions->map(function ($session) {
            return [
                'id' => $session->id,
                'session_number' => $session->session_number,
                'date' => $session->session_date->format('Y-m-d'),
                'day_of_week' => $session->day_of_week,
                'day_of_week_text' => $session->day_of_week_text,
                'shift' => $session->shift,
                'status' => $session->status,
                'topic' => $session->topic,
            ];
        })->values()->toArray();

        // Calculate overall statistics
        $totalStudents = count($studentsData);
        $atRiskCount = collect($studentsData)->filter(fn($s) => $s['summary']['is_at_risk'])->count();
        $avgAttendanceRate = $totalStudents > 0 
            ? round(collect($studentsData)->avg('summary.attendance_rate'), 2) 
            : 0;

        return [
            'course' => [
                'id' => $course->id,
                'code' => $course->code,
                'name' => $course->name,
                'semester' => $course->semester ? $course->semester->name : null,
                'lecturer' => $course->lecturer ? $course->lecturer->full_name : null,
                'total_sessions' => $course->total_sessions,
                'max_absences' => $course->max_absences,
                'absence_warning' => $course->absence_warning,
            ],
            'sessions' => $sessionsData,
            'students' => $studentsData,
            'statistics' => [
                'total_students' => $totalStudents,
                'at_risk_count' => $atRiskCount,
                'average_attendance_rate' => $avgAttendanceRate,
                'completed_sessions' => $sessions->where('status', 'completed')->count(),
                'total_sessions' => $sessions->count(),
            ],
        ];
    }
}
