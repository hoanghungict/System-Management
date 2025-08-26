<?php

namespace Modules\Auth\app\Services\DepartmentService;

use Modules\Auth\app\Models\Department;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DepartmentService
{
    /**
     * Lấy tất cả departments
     */
    public function getAllDepartments(): Collection
    {
        return Cache::remember('departments:all', 3600, function() {
            return Department::with(['parent', 'children'])->get();
        });
    }

    /**
     * Lấy department theo ID
     */
    public function getDepartmentById(int $id): ?Department
    {
        return Cache::remember("departments:{$id}", 3600, function() use ($id) {
            return Department::with(['parent', 'children', 'lecturers', 'classes'])->find($id);
        });
    }

    /**
     * Tạo department mới
     */
    public function createDepartment(array $data): Department
    {
        $department = Department::create($data);
        
        // Xóa cache departments
        $this->clearDepartmentsCache();
        
        return $department;
    }

    /**
     * Cập nhật department
     */
    public function updateDepartment(Department $department, array $data): Department
    {
        $department->update($data);
        
        // Xóa cache departments
        $this->clearDepartmentsCache();
        
        return $department->fresh();
    }

    /**
     * Xóa department
     */
    public function deleteDepartment(Department $department): bool
    {
        // Kiểm tra xem có thể xóa không
        if ($department->lecturers()->count() > 0) {
            throw new \Exception('Không thể xóa department vì còn giảng viên');
        }
        
        if ($department->classes()->count() > 0) {
            throw new \Exception('Không thể xóa department vì còn lớp học');
        }
        
        if ($department->children()->count() > 0) {
            throw new \Exception('Không thể xóa department vì còn departments con');
        }
        
        $deleted = $department->delete();
        
        if ($deleted) {
            // Xóa cache departments
            $this->clearDepartmentsCache();
        }
        
        return $deleted;
    }

    /**
     * Lấy tất cả departments theo cấu trúc đơn giản
     */
    public function getAllDepartmentsWithLevel(): Collection
    {
        return Cache::remember('departments:with_level', 3600, function() {
            return Department::with('parent')
                       ->orderBy('type')
                       ->orderBy('parent_id')
                       ->orderBy('name')
                       ->get();
        });
    }
    
    /**
     * Xóa tất cả cache departments
     */
    private function clearDepartmentsCache(): void
    {
        Cache::forget('departments:all');
        Cache::forget('departments:with_level');
        
        // Xóa cache individual departments (có thể có nhiều)
        $departments = Department::pluck('id');
        foreach ($departments as $id) {
            Cache::forget("departments:{$id}");
        }
    }
}
