<?php

namespace Modules\Auth\app\Services\RollCallService;

use Modules\Auth\app\Models\RollCall;
use Modules\Auth\app\Models\RollCallDetail;
use Modules\Auth\app\Models\Student;
use Modules\Auth\app\Models\Classroom;
use Modules\Auth\app\Repositories\Interfaces\RollCallRepositoryInterface;
use Modules\Auth\app\Repositories\Interfaces\RollCallDetailRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RollCallService
{
    protected $rollCallRepository;
    protected $rollCallDetailRepository;

    public function __construct(
        RollCallRepositoryInterface $rollCallRepository,
        RollCallDetailRepositoryInterface $rollCallDetailRepository
    ) {
        $this->rollCallRepository = $rollCallRepository;
        $this->rollCallDetailRepository = $rollCallDetailRepository;
    }

    /**
     * Lấy danh sách lớp học
     */
    public function getClassrooms()
    {
        $cacheKey = 'classrooms:with_students';
        
        // Cache 30 phút - classrooms ít thay đổi
        return Cache::remember($cacheKey, 1800, function() {
            return Classroom::with('students')->get();
        });
    }

    /**
     * Tạo buổi điểm danh mới
     */
    public function createRollCall(array $data): RollCall
    {
        DB::beginTransaction();

        try {
            $type = $data['type'] ?? 'class_based';
            
            // Tạo buổi điểm danh
            $rollCall = $this->rollCallRepository->create($data);
            
            // Xử lý theo loại điểm danh
            switch ($type) {
                case 'class_based':
                    $this->createClassBasedRollCall($rollCall, $data);
                    break;
                case 'manual':
                    $this->createManualRollCall($rollCall, $data);
                    break;
            }

            // Xóa cache liên quan
            $this->clearRollCallCache($data['class_id'] ?? null);
            
            DB::commit();

            /* Log::info('Roll call created successfully', [
                'roll_call_id' => $rollCall->id,
                'type' => $type,
                'class_id' => $data['class_id'] ?? null
            ]); */

            return $this->rollCallRepository->findById($rollCall->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create roll call', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Tạo buổi điểm danh cho lớp cơ bản
     */
    private function createClassBasedRollCall(RollCall $rollCall, array $data): void
    {
        // Lấy danh sách sinh viên trong lớp
        $students = Student::where('class_id', $data['class_id'])->get();
        
        // Tạo chi tiết điểm danh cho từng sinh viên
        foreach ($students as $student) {
            $this->rollCallDetailRepository->create([
                'roll_call_id' => $rollCall->id,
                'student_id' => $student->id,
                'status' => 'Vắng Mặt', // Mặc định là vắng mặt
                'checked_at' => null
            ]);
        }

        /* Log::info('Class-based roll call created', [
            'roll_call_id' => $rollCall->id,
            'class_id' => $data['class_id'],
            'students_count' => $students->count()
        ]); */
    }

    /**
     * Tạo buổi điểm danh manual (tự chọn sinh viên)
     */
    private function createManualRollCall(RollCall $rollCall, array $data): void
    {
        $participants = $data['participants'] ?? [];
        
        // Tạo chi tiết điểm danh cho từng sinh viên được chọn
        foreach ($participants as $studentId) {
            $this->rollCallDetailRepository->create([
                'roll_call_id' => $rollCall->id,
                'student_id' => $studentId,
                'status' => 'Vắng Mặt', // Mặc định là vắng mặt
                'checked_at' => null
            ]);
        }

        // Update expected_participants nếu không có
        if (!isset($data['expected_participants'])) {
            $this->rollCallRepository->update($rollCall->id, [
                'expected_participants' => count($participants)
            ]);
        }

        /* Log::info('Manual roll call created', [
            'roll_call_id' => $rollCall->id,
            'participants_count' => count($participants),
            'expected_participants' => $data['expected_participants'] ?? count($participants)
        ]); */
    }

    /**
     * Thêm sinh viên vào buổi điểm danh manual
     */
    public function addParticipants(int $rollCallId, array $studentIds): bool
    {
        try {
            $rollCall = $this->rollCallRepository->findById($rollCallId);
            
            if (!$rollCall || !$rollCall->isManual()) {
                throw new \Exception('Roll call không tồn tại hoặc không phải loại manual');
            }

            // Lấy danh sách sinh viên đã có
            $existingStudentIds = $rollCall->rollCallDetails->pluck('student_id')->toArray();
            
            // Chỉ thêm những sinh viên chưa có
            $newStudentIds = array_diff($studentIds, $existingStudentIds);
            
            foreach ($newStudentIds as $studentId) {
                $this->rollCallDetailRepository->create([
                    'roll_call_id' => $rollCall->id,
                    'student_id' => $studentId,
                    'status' => 'Vắng Mặt',
                    'checked_at' => null
                ]);
            }

            // Clear cache
            $this->clearRollCallCache($rollCall->class_id);
            Cache::forget("roll_call_details:{$rollCallId}");

            /* Log::info('Participants added to manual roll call', [
                'roll_call_id' => $rollCallId,
                'new_participants' => count($newStudentIds),
                'total_participants' => count($existingStudentIds) + count($newStudentIds)
            ]); */

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to add participants', [
                'roll_call_id' => $rollCallId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Xóa sinh viên khỏi buổi điểm danh manual
     */
    public function removeParticipant(int $rollCallId, int $studentId): bool
    {
        try {
            $rollCall = $this->rollCallRepository->findById($rollCallId);
            
            if (!$rollCall || !$rollCall->isManual()) {
                throw new \Exception('Roll call không tồn tại hoặc không phải loại manual');
            }

            // Xóa chi tiết điểm danh
            $deleted = $this->rollCallDetailRepository->deleteByStudentAndRollCall($studentId, $rollCallId);
            
            if ($deleted) {
                // Clear cache
                $this->clearRollCallCache($rollCall->class_id);
                Cache::forget("roll_call_details:{$rollCallId}");

                /* Log::info('Participant removed from manual roll call', [
                    'roll_call_id' => $rollCallId,
                    'student_id' => $studentId
                ]); */
            }

            return $deleted;

        } catch (\Exception $e) {
            Log::error('Failed to remove participant', [
                'roll_call_id' => $rollCallId,
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Lấy danh sách tất cả sinh viên để chọn cho manual roll call
     */
    public function getAllStudentsForSelection()
    {
        $cacheKey = 'all_students_for_roll_call';
        
        return Cache::remember($cacheKey, 1800, function() {
            return Student::with('account')
                ->orderBy('full_name')
                ->get();
        });
    }
    
    /**
     * Lấy danh sách buổi điểm danh theo lớp
     */
    public function getRollCallsByClass(int $classId, int $perPage = 15)
    {
        $cacheKey = "roll_calls:class:{$classId}:page:{$perPage}";
        
        // Cache 5 phút (300s) - data có thể thay đổi
        return Cache::remember($cacheKey, 300, function() use ($classId, $perPage) {
            return $this->rollCallRepository->getByClass($classId, $perPage);
        });
    }
    
    /**
     * Lấy tất cả buổi điểm danh với filter và phân trang
     */
    public function getAllRollCalls(array $filters = [])
    {
        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;
        $status = $filters['status'] ?? null;
        $type = $filters['type'] ?? null;
        $search = $filters['search'] ?? null;
        $classId = $filters['class_id'] ?? null;
        
        $cacheKey = "roll_calls:all:page:{$page}:per_page:{$perPage}:status:{$status}:type:{$type}:search:{$search}:class:{$classId}";
        
        // GIẢM CACHE TIME từ 1800s (30 phút) xuống 60s (1 phút)
        // Vì roll calls data thay đổi thường xuyên (create, update, complete, cancel)
        return Cache::remember($cacheKey, 60, function() use ($perPage, $page, $status, $type, $search, $classId) {
            $query = $this->rollCallRepository->getModel()->with(['class', 'creator']);
            
            // Filter by status
            if ($status) {
                $query->where('status', $status);
            }
            
            // Filter by type
            if ($type) {
                $query->where('type', $type);
            }
            
            // Filter by class
            if ($classId) {
                $query->where('class_id', $classId);
            }
            
            // Search by title or description
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }
            
            // Order by created_at desc
            $query->orderBy('created_at', 'desc');
            
            // Paginate
            return $query->paginate($perPage, ['*'], 'page', $page);
        });
    }
    
    /**
     * Lấy chi tiết buổi điểm danh
     */
    public function getRollCallDetails(int $rollCallId)
    {
        $cacheKey = "roll_call_details:{$rollCallId}";

        return Cache::remember($cacheKey, 1800, function () use ($rollCallId) {
            return $this->rollCallRepository->findById($rollCallId);
        });
    }

    /**
     * Cập nhật trạng thái điểm danh của sinh viên
     */
    public function updateStudentStatus(int $rollCallId, int $studentId, string $status, string $note = null): bool
    {
        try {
            $success = $this->rollCallDetailRepository->updateByStudentAndRollCall(
                $studentId,
                $rollCallId,
                [
                    'status' => $status,
                    'note' => $note,
                    'checked_at' => now()
                ]
            );

            if ($success) {
                // Lấy thông tin buổi điểm danh để xóa cache
                $rollCall = $this->rollCallRepository->findById($rollCallId);
                if ($rollCall) {
                    $this->clearRollCallCache($rollCall->class_id);
                }
                Cache::forget("roll_call_details:{$rollCallId}");

                /* Log::info('Student roll call status updated', [
                    'roll_call_id' => $rollCallId,
                    'student_id' => $studentId,
                    'status' => $status
                ]); */
            }

            return $success;
        } catch (\Exception $e) {
            Log::error('Failed to update student roll call status', [
                'roll_call_id' => $rollCallId,
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Cập nhật trạng thái hàng loạt
     */
    public function updateBulkStatus(int $rollCallId, array $studentStatuses): bool
    {
        DB::beginTransaction();

        try {
            foreach ($studentStatuses as $studentId => $data) {
                $this->updateStudentStatus(
                    $rollCallId,
                    $studentId,
                    $data['status'],
                    $data['note'] ?? null
                );
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update bulk roll call status', [
                'roll_call_id' => $rollCallId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Hoàn thành buổi điểm danh
     */
    public function completeRollCall(int $rollCallId): bool
    {
        try {
            $success = $this->rollCallRepository->update($rollCallId, ['status' => 'completed']);

            if ($success) {
                $rollCall = $this->rollCallRepository->findById($rollCallId);
                if ($rollCall) {
                    $this->clearRollCallCache($rollCall->class_id);
                }

                // Log::info('Roll call completed', ['roll_call_id' => $rollCallId]);
            }

            return $success;
        } catch (\Exception $e) {
            Log::error('Failed to complete roll call', [
                'roll_call_id' => $rollCallId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Hủy buổi điểm danh
     */
    public function cancelRollCall(int $rollCallId): bool
    {
        try {
            $success = $this->rollCallRepository->update($rollCallId, ['status' => 'cancelled']);

            if ($success) {
                $rollCall = $this->rollCallRepository->findById($rollCallId);
                if ($rollCall) {
                    $this->clearRollCallCache($rollCall->class_id);
                }

                // Log::info('Roll call cancelled', ['roll_call_id' => $rollCallId]);
            }

            return $success;
        } catch (\Exception $e) {
            Log::error('Failed to cancel roll call', [
                'roll_call_id' => $rollCallId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Lấy thống kê điểm danh theo lớp
     */
    public function getRollCallStatistics(int $classId, $startDate = null, $endDate = null)
    {
        $cacheKey = "roll_call_stats:class:{$classId}:" . ($startDate ?? 'all') . ':' . ($endDate ?? 'all');
        
        return Cache::remember($cacheKey, 3600, function() use ($classId, $startDate, $endDate) {
            // Lấy danh sách buổi điểm danh theo lớp và thời gian
            $rollCallsQuery = RollCall::where('class_id', $classId);
            
            if ($startDate) {
                $rollCallsQuery->whereDate('date', '>=', $startDate);
            }
            
            if ($endDate) {
                $rollCallsQuery->whereDate('date', '<=', $endDate);
            }
            
            $rollCalls = $rollCallsQuery->with(['rollCallDetails'])->get();
            
            // Khởi tạo thống kê
            $stats = [
                'total_roll_calls' => $rollCalls->count(),
                'roll_call_sessions' => [],
                'summary' => [
                    'total_students_checked' => 0,
                    'total_present' => 0,
                    'total_absent' => 0,
                    'total_late' => 0,
                    'total_excused' => 0,
                    'average_attendance_rate' => 0
                ]
            ];
            
            $totalChecked = 0;
            $totalPresent = 0;
            $totalAbsent = 0;
            $totalLate = 0;
            $totalExcused = 0;
            
            // Thống kê từng buổi điểm danh
            foreach ($rollCalls as $rollCall) {
                $details = $rollCall->rollCallDetails;
                
                $sessionStats = [
                    'roll_call_id' => $rollCall->id,
                    'title' => $rollCall->title,
                    'date' => $rollCall->date->format('Y-m-d H:i:s'),
                    'status' => $rollCall->status,
                    'type' => $rollCall->type ?? 'class_based',
                    'students' => [
                        'total' => $details->count(),
                        'present' => $details->where('status', 'Có Mặt')->count(),
                        'absent' => $details->where('status', 'Vắng Mặt')->count(),
                        'late' => $details->where('status', 'Muộn')->count(),
                        'excused' => $details->where('status', 'Có Phép')->count()
                    ],
                    'attendance_rate' => 0
                ];
                
                // Tính tỷ lệ điểm danh cho buổi này
                if ($sessionStats['students']['total'] > 0) {
                    $attendedCount = $sessionStats['students']['present'] + $sessionStats['students']['late'];
                    $sessionStats['attendance_rate'] = round(($attendedCount / $sessionStats['students']['total']) * 100, 2);
                }
                
                $stats['roll_call_sessions'][] = $sessionStats;
                
                // Cộng dồn cho tổng kết
                $totalChecked += $sessionStats['students']['total'];
                $totalPresent += $sessionStats['students']['present'];
                $totalAbsent += $sessionStats['students']['absent'];
                $totalLate += $sessionStats['students']['late'];
                $totalExcused += $sessionStats['students']['excused'];
            }
            
            // Tính toán tổng kết
            $stats['summary']['total_students_checked'] = $totalChecked;
            $stats['summary']['total_present'] = $totalPresent;
            $stats['summary']['total_absent'] = $totalAbsent;
            $stats['summary']['total_late'] = $totalLate;
            $stats['summary']['total_excused'] = $totalExcused;
            
            // Tính tỷ lệ điểm danh trung bình
            if ($totalChecked > 0) {
                $totalAttended = $totalPresent + $totalLate;
                $stats['summary']['average_attendance_rate'] = round(($totalAttended / $totalChecked) * 100, 2);
            }
            
            return $stats;
        });
    }

    /**
     * Xóa cache điểm danh
     */
    private function clearRollCallCache(?int $classId = null): void
    {
        // Xóa cache cho getRollCallsByClass nếu có classId
        if ($classId) {
            // Xóa tất cả pages của class này
            for ($i = 1; $i <= 20; $i++) {
                Cache::forget("roll_calls:class:{$classId}:page:{$i}");
            }
            Cache::forget("roll_call_stats:class:{$classId}");
        }
        
        // XÓA TẤT CẢ CACHE CỦA getAllRollCalls
        // Cache key pattern: roll_calls:all:page:X:per_page:Y:status:Z:type:W:search:S:class:C
        // Vì có nhiều combinations, cần xóa toàn bộ
        
        // Option 1: Sử dụng Redis keys pattern (nếu dùng Redis)
        try {
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                // Sử dụng Laravel Redis facade
                $redis = \Illuminate\Support\Facades\Redis::connection();
                
                // Xóa tất cả keys bắt đầu với roll_calls:all:
                $prefix = config('cache.prefix') . ':';
                $pattern = $prefix . 'roll_calls:all:*';
                
                $keys = $redis->keys($pattern);
                if (!empty($keys)) {
                    // Xóa từng key
                    foreach ($keys as $key) {
                        // Laravel Redis keys đã có prefix, xóa trực tiếp
                        Cache::forget(str_replace($prefix, '', $key));
                    }
                    /* Log::info('Cleared getAllRollCalls cache via Redis', [
                        'keys_count' => count($keys),
                        'pattern' => $pattern
                    ]); */
                }
            }
        } catch (\Exception $e) {
            Log::warning('Could not clear cache via Redis, using fallback', [
                'error' => $e->getMessage()
            ]);
        }
        
        // Option 2: Fallback - xóa các cache keys phổ biến
        $statuses = ['', 'active', 'completed', 'cancelled'];
        $types = ['', 'class_based', 'manual'];
        $perPages = [10, 15, 20, 25, 50, 100];
        
        foreach ($statuses as $status) {
            foreach ($types as $type) {
                foreach ($perPages as $perPage) {
                    // Xóa 5 pages đầu tiên cho mỗi combination
                    for ($page = 1; $page <= 5; $page++) {
                        $cacheKey = "roll_calls:all:page:{$page}:per_page:{$perPage}:status:{$status}:type:{$type}:search::class:";
                        Cache::forget($cacheKey);
                        
                        // Cũng xóa với class_id nếu có
                        if ($classId) {
                            $cacheKey = "roll_calls:all:page:{$page}:per_page:{$perPage}:status:{$status}:type:{$type}:search::class:{$classId}";
                            Cache::forget($cacheKey);
                        }
                    }
                }
            }
        }
        
        /* Log::info('Roll call cache cleared comprehensively', [
            'class_id' => $classId,
            'method' => 'Redis + fallback patterns'
        ]); */
    }
}
