<?php

namespace Modules\Task\app\Repositories;

use Modules\Task\app\Repositories\Interfaces\CachedUserRepositoryInterface;
use Modules\Task\app\Services\Interfaces\CacheServiceInterface;
use Illuminate\Support\Facades\Log;
use Modules\Auth\app\Models\Department;
use Modules\Auth\app\Models\Lecturer;
use Modules\Auth\app\Models\Classroom;
use Modules\Auth\app\Models\Student;

/**
 * Cached User Repository Implementation
 * 
 * Tuân thủ Clean Architecture: Implementation của CachedUserRepositoryInterface
 * Kết hợp cache với user data operations
 */
class CachedUserRepository implements CachedUserRepositoryInterface
{
    public function __construct(
        private CacheServiceInterface $cacheService
    ) {}

    /**
     * Lấy danh sách faculties với cache
     */
    public function getFacultiesForUser($user): array
    {
        $cacheKey = $this->cacheService->generateKey('faculties_user', [
            'user_id' => $user->id ?? 0,
            'user_type' => $this->getUserType($user)
        ]);
        
        return $this->cacheService->remember($cacheKey, function () use ($user) {
            $userType = $this->getUserType($user);
            
            // Admin có thể xem tất cả departments
            if ($userType === 'admin') {
                return Department::all()->toArray();
            }
            
            // Lecturer chỉ có thể xem department của mình
            if ($userType === 'lecturer') {
                $lecturer = \Modules\Auth\app\Models\Lecturer::find($user->id);
                if ($lecturer && $lecturer->department_id) {
                    $department = Department::find($lecturer->department_id);
                    if ($department) {
                        return [$department->toArray()];
                    }
                }
            }
            
            return [];
        });
    }

    /**
     * Lấy danh sách classes theo department với cache
     */
    public function getClassesByDepartmentForUser($user, int $departmentId): array
    {
        $cacheKey = $this->cacheService->generateKey('classes_department_user', [
            'user_id' => $user->id ?? 0,
            'user_type' => $this->getUserType($user),
            'department_id' => $departmentId
        ]);
        
        return $this->cacheService->remember($cacheKey, function () use ($user, $departmentId) {
            $userType = $this->getUserType($user);
            
            // Admin có thể xem tất cả classes
            if ($userType === 'admin') {
                return \Modules\Auth\app\Models\Classroom::where('department_id', $departmentId)->get()->toArray();
            }
            
            // Lecturer chỉ có thể xem classes thuộc department của mình
            if ($userType === 'lecturer') {
                $lecturer = \Modules\Auth\app\Models\Lecturer::find($user->id);
                if ($lecturer && $lecturer->department_id == $departmentId) {
                    return \Modules\Auth\app\Models\Classroom::where('department_id', $departmentId)->get()->toArray();
                }
            }
            
            return [];
        });
    }

    /**
     * Lấy danh sách students theo class với cache
     */
    public function getStudentsByClassForUser($user, int $classId): array
    {
        $cacheKey = $this->cacheService->generateKey('students_class_user', [
            'user_id' => $user->id ?? 0,
            'user_type' => $this->getUserType($user),
            'class_id' => $classId
        ]);
        
        return $this->cacheService->remember($cacheKey, function () use ($user, $classId) {
            $userType = $this->getUserType($user);
            
            // Admin có thể xem tất cả students
            if ($userType === 'admin') {
                return \Modules\Auth\app\Models\Student::where('class_id', $classId)->get()->toArray();
            }
            
            // Lecturer chỉ có thể xem students thuộc faculty của mình
            if ($userType === 'lecturer') {
                $lecturer = \Modules\Auth\app\Models\Lecturer::find($user->id);
                if ($lecturer && $lecturer->faculty_id) {
                    $class = \Modules\Auth\app\Models\Classroom::where('id', $classId)
                        ->where('faculty_id', $lecturer->faculty_id)
                        ->first();
                    
                    if ($class) {
                        return \Modules\Auth\app\Models\Student::where('class_id', $classId)->get()->toArray();
                    }
                }
            }
            
            return [];
        });
    }

    /**
     * Lấy danh sách lecturers với cache
     */
    public function getLecturersForUser($user): array
    {
        $cacheKey = $this->cacheService->generateKey('lecturers_user', [
            'user_id' => $user->id ?? 0,
            'user_type' => $this->getUserType($user)
        ]);
        
        return $this->cacheService->remember($cacheKey, function () use ($user) {
            $userType = $this->getUserType($user);
            
            // Admin có thể xem tất cả lecturers
            if ($userType === 'admin') {
                return \Modules\Auth\app\Models\Lecturer::with('department')->get()->toArray();
            }
            
            // Lecturer có thể xem lecturers trong cùng department
            if ($userType === 'lecturer') {
                $lecturer = \Modules\Auth\app\Models\Lecturer::with('department')->find($user->id);
                if ($lecturer && $lecturer->department_id) {
                    return \Modules\Auth\app\Models\Lecturer::with('department')
                        ->where('department_id', $lecturer->department_id)
                        ->get()
                        ->toArray();
                }
            }
            
            return [];
        });
    }

    /**
     * Lấy danh sách tất cả students với cache
     */
    public function getAllStudentsForUser($user): array
    {
        $cacheKey = $this->cacheService->generateKey('all_students_user', [
            'user_id' => $user->id ?? 0,
            'user_type' => $this->getUserType($user)
        ]);
        
        return $this->cacheService->remember($cacheKey, function () use ($user) {
            $userType = $this->getUserType($user);
            
            // Admin có thể xem tất cả students
            if ($userType === 'admin') {
                return \Modules\Auth\app\Models\Student::with('classroom')->get()->toArray();
            }
            
            // Lecturer chỉ có thể xem students thuộc faculty của mình
            if ($userType === 'lecturer') {
                $lecturer = \Modules\Auth\app\Models\Lecturer::find($user->id);
                if ($lecturer && $lecturer->faculty_id) {
                    return \Modules\Auth\app\Models\Student::whereHas('classroom', function($query) use ($lecturer) {
                        $query->where('faculty_id', $lecturer->faculty_id);
                    })->with('classroom')->get()->toArray();
                }
            }
            
            return [];
        });
    }

    /**
     * Lấy tất cả faculties (admin only) với cache
     */
    public function getAllFaculties(): array
    {
        $cacheKey = $this->cacheService->generateKey('all_faculties');
        
        return $this->cacheService->remember($cacheKey, function () {
            return Department::all()->toArray();
        });
    }

    /**
     * Lấy tất cả classes của department với cache
     */
    public function getAllClassesByDepartment(int $departmentId): array
    {
        $cacheKey = $this->cacheService->generateKey('all_classes_department', ['department_id' => $departmentId]);
        
        return $this->cacheService->remember($cacheKey, function () use ($departmentId) {
            return \Modules\Auth\app\Models\Classroom::where('department_id', $departmentId)->get()->toArray();
        });
    }

    /**
     * Lấy tất cả students của class với cache
     */
    public function getAllStudentsByClass(int $classId): array
    {
        $cacheKey = $this->cacheService->generateKey('all_students_class', ['class_id' => $classId]);
        
        return $this->cacheService->remember($cacheKey, function () use ($classId) {
            return \Modules\Auth\app\Models\Student::where('class_id', $classId)->get()->toArray();
        });
    }

    /**
     * Lấy tất cả lecturers với cache
     */
    public function getAllLecturers(): array
    {
        $cacheKey = $this->cacheService->generateKey('all_lecturers');
        
        return $this->cacheService->remember($cacheKey, function () {
            return \Modules\Auth\app\Models\Lecturer::with('faculty')->get()->toArray();
        });
    }

    /**
     * Xóa cache cho user
     */
    public function clearUserCache(int $userId, string $userType): bool
    {
        $patterns = [
            'faculties_user:*user_id=' . $userId . '*',
            'classes_faculty_user:*user_id=' . $userId . '*',
            'students_class_user:*user_id=' . $userId . '*',
            'lecturers_user:*user_id=' . $userId . '*',
            'all_students_user:*user_id=' . $userId . '*'
        ];
        
        foreach ($patterns as $pattern) {
            $this->cacheService->forgetPattern($pattern);
        }
        
        Log::info('User cache cleared', [
            'user_id' => $userId,
            'user_type' => $userType
        ]);
        
        return true;
    }

    /**
     * Xóa cache cho department
     */
    public function clearDepartmentCache(int $departmentId): bool
    {
        $patterns = [
            'departments_user:*',
            'classes_department_user:*department_id=' . $departmentId . '*',
            'all_classes_department:*department_id=' . $departmentId . '*',
            'all_departments:*'
        ];
        
        foreach ($patterns as $pattern) {
            $this->cacheService->forgetPattern($pattern);
        }
        
        Log::info('Department cache cleared', ['department_id' => $departmentId]);
        
        return true;
    }

    /**
     * Xóa cache cho class
     */
    public function clearClassCache(int $classId): bool
    {
        $patterns = [
            'classes_faculty_user:*',
            'students_class_user:*class_id=' . $classId . '*',
            'all_students_class:*class_id=' . $classId . '*',
            'all_classes_faculty:*'
        ];
        
        foreach ($patterns as $pattern) {
            $this->cacheService->forgetPattern($pattern);
        }
        
        Log::info('Class cache cleared', ['class_id' => $classId]);
        
        return true;
    }

    /**
     * Xóa tất cả user management cache
     */
    public function clearAllUserCache(): bool
    {
        $patterns = [
            'faculties_*',
            'classes_*',
            'students_*',
            'lecturers_*',
            'all_faculties:*',
            'all_classes_*',
            'all_students_*',
            'all_lecturers:*'
        ];
        
        foreach ($patterns as $pattern) {
            $this->cacheService->forgetPattern($pattern);
        }
        
        Log::info('All user management cache cleared');
        
        return true;
    }

    /**
     * Lấy loại user từ model
     */
    private function getUserType($user): string
    {
        // Kiểm tra nếu user là admin (có is_admin = true)
        if (isset($user->account) && isset($user->account['is_admin']) && $user->account['is_admin']) {
            return 'admin';
        }
        
        // Nếu user có user_type property (từ JWT)
        if (isset($user->user_type)) {
            return $user->user_type;
        }
        
        // Kiểm tra instance của model
        if ($user instanceof \Modules\Auth\app\Models\Lecturer) {
            return 'lecturer';
        } elseif ($user instanceof \Modules\Auth\app\Models\Student) {
            return 'student';
        } elseif ($user instanceof \Modules\Auth\app\Models\LecturerAccount) {
            return 'lecturer';
        } elseif ($user instanceof \Modules\Auth\app\Models\StudentAccount) {
            return 'student';
        }
        
        return 'unknown';
    }
}
