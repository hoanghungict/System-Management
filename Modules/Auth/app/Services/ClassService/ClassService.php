<?php

namespace Modules\Auth\app\Services\ClassService;

use Modules\Auth\app\Models\Classroom;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ClassService
{
    /**
     * Lấy tất cả classes
     */
    public function getAllClasses(): Collection
    {
        return Cache::remember('classrooms:all', 1800, function() {
            return Classroom::with(['faculty', 'lecturer', 'students'])->get();
        });
    }

    /**
     * Lấy class theo ID
     */
    public function getClassById(int $id): ?Classroom
    {
        return Cache::remember("classrooms:{$id}", 1800, function() use ($id) {
            return Classroom::with(['faculty', 'lecturer', 'students'])->find($id);
        });
    }

    /**
     * Tạo class mới
     */
    public function createClass(array $data): Classroom
    {
        $class = Classroom::create($data);
        
        // Xóa cache classrooms
        $this->clearClassroomsCache();
        
        return $class;
    }

    /**
     * Cập nhật class
     */
    public function updateClass(Classroom $class, array $data): Classroom
    {
        $class->update($data);
        
        // Xóa cache classrooms
        $this->clearClassroomsCache();
        
        return $class->fresh();
    }

    /**
     * Xóa class
     */
    public function deleteClass(Classroom $class): bool
    {
        // Kiểm tra xem có thể xóa không
        if ($class->students()->count() > 0) {
            throw new \Exception('Không thể xóa lớp học vì còn sinh viên');
        }
        
        $deleted = $class->delete();
        
        if ($deleted) {
            // Xóa cache classrooms
            $this->clearClassroomsCache();
        }
        
        return $deleted;
    }

    /**
     * Lấy classes theo faculty
     */
    public function getClassesByFaculty(int $facultyId): Collection
    {
        return Cache::remember("classrooms:faculty:{$facultyId}", 1800, function() use ($facultyId) {
            return Classroom::where('faculty_id', $facultyId)
                           ->with(['faculty', 'lecturer', 'students'])
                           ->get();
        });
    }

    /**
     * Lấy classes theo lecturer
     */
    public function getClassesByLecturer(int $lecturerId): Collection
    {
        return Cache::remember("classrooms:lecturer:{$lecturerId}", 1800, function() use ($lecturerId) {
            return Classroom::where('lecturer_id', $lecturerId)
                           ->with(['faculty', 'lecturer', 'students'])
                           ->get();
        });
    }

    /**
     * Lấy classes theo năm học
     */
    public function getClassesBySchoolYear(string $schoolYear): Collection
    {
        return Classroom::where('school_year', $schoolYear)
                       ->with(['faculty', 'lecturer', 'students'])
                       ->get();
    }

    /**
     * Tìm kiếm classes
     */
    public function searchClasses(string $keyword): Collection
    {
        return Cache::remember("classrooms:search:{$keyword}", 1800, function() use ($keyword) {
            return Classroom::where('class_name', 'like', "%{$keyword}%")
                           ->orWhere('class_code', 'like', "%{$keyword}%")
                           ->orWhere('school_year', 'like', "%{$keyword}%")
                           ->with(['faculty', 'lecturer', 'students'])
                           ->get();
        });
    }
    
    /**
     * Xóa tất cả cache classrooms
     */
    private function clearClassroomsCache(): void
    {
        Cache::forget('classrooms:all');
        
        // Xóa cache individual classrooms
        $classrooms = Classroom::pluck('id');
        foreach ($classrooms as $id) {
            Cache::forget("classrooms:{$id}");
        }
        
        // Xóa cache faculty/lecturer specific
        $faculties = Classroom::distinct()->pluck('faculty_id');
        foreach ($faculties as $facultyId) {
            Cache::forget("classrooms:faculty:{$facultyId}");
        }
        
        $lecturers = Classroom::distinct()->pluck('lecturer_id');
        foreach ($lecturers as $lecturerId) {
            Cache::forget("classrooms:lecturer:{$lecturerId}");
        }
        
        // Xóa cache search (khó track, có thể clear all)
        Cache::forget('classrooms:search:*');
    }
}
