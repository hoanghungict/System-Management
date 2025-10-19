<?php

namespace Modules\Auth\app\Repositories;

use Modules\Auth\app\Models\Department;
use Illuminate\Support\Collection;

class DepartmentRepository
{
    /**
     * Get all departments with staff count
     */
    public function getAllWithStaffCount(): Collection
    {
        $departments = Department::with(['parent', 'children'])
            ->withCount(['lecturers', 'classes'])
            ->get();
            
        // Calculate custom counts based on department type
        foreach ($departments as $department) {
            if ($department->type === 'school') {
                // School: count all child departments (faculty + department)
                $department->staff_count = $this->countChildDepartments($department->id);
            } else {
                // Faculty and Department: count lecturers
                $department->staff_count = $department->lecturers_count;
            }
        }
        
        return $departments;
    }
    
    /**
     * Count child departments recursively
     */
    private function countChildDepartments(int $parentId): int
    {
        $children = Department::where('parent_id', $parentId)->get();
        $count = $children->count();
        
        foreach ($children as $child) {
            $count += $this->countChildDepartments($child->id);
        }
        
        return $count;
    }

    /**
     * Get department by ID with staff count
     */
    public function getByIdWithStaffCount(int $id): ?Department
    {
        $department = Department::with(['parent', 'children', 'lecturers', 'classes'])
            ->withCount(['lecturers', 'classes'])
            ->find($id);
            
        if ($department) {
            if ($department->type === 'school') {
                $department->staff_count = $this->countChildDepartments($department->id);
            } else {
                $department->staff_count = $department->lecturers_count;
            }
        }
        
        return $department;
    }

    /**
     * Get all departments with level structure and staff count
     */
    public function getAllWithLevelAndStaffCount(): Collection
    {
        $departments = Department::with('parent')
            ->withCount(['lecturers', 'classes'])
            ->orderBy('type')
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get();
            
        // Calculate custom counts based on department type
        foreach ($departments as $department) {
            if ($department->type === 'school') {
                $department->staff_count = $this->countChildDepartments($department->id);
            } else {
                $department->staff_count = $department->lecturers_count;
            }
        }
        
        return $departments;
    }
}
