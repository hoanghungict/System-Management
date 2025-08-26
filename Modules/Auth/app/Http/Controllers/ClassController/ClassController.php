<?php

namespace Modules\Auth\app\Http\Controllers\ClassController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Auth\app\Services\ClassService\ClassService;
use Modules\Auth\app\Http\Requests\ClassRequest\CreateClassRequest;
use Modules\Auth\app\Http\Requests\ClassRequest\UpdateClassRequest;
use Modules\Auth\app\Http\Resources\ClassResources\ClassResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ClassController extends Controller
{
    protected $classService;

    public function __construct(ClassService $classService)
    {
        $this->classService = $classService;
    }

    /**
     * Hiển thị danh sách classes
     */
    public function index(): JsonResponse
    {
        $classes = $this->classService->getAllClasses();
        return response()->json(ClassResource::collection($classes));
    }

    /**
     * Tạo class mới
     */
    public function store(CreateClassRequest $request): JsonResponse
    {
        try {
            $class = $this->classService->createClass($request->validated());
            
            // Clear related cache
            if ($class->faculty_id) {
                Cache::forget("auth:classes:faculty:{$class->faculty_id}");
            }
            if ($class->lecturer_id) {
                Cache::forget("auth:classes:lecturer:{$class->lecturer_id}");
            }
            
            return response()->json([
                'message' => 'Tạo lớp học thành công',
                'data' => new ClassResource($class)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi tạo lớp học',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hiển thị thông tin class
     */
    public function show(int $id): JsonResponse
    {
        try {
            $class = $this->classService->getClassById($id);
            
            if (!$class) {
                return response()->json([
                    'message' => 'Không tìm thấy lớp học'
                ], 404);
            }
            
            return response()->json(new ClassResource($class));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi lấy thông tin lớp học',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật thông tin class
     */
    public function update(UpdateClassRequest $request, int $id): JsonResponse
    {
        try {
            $class = $this->classService->getClassById($id);
            
            if (!$class) {
                return response()->json([
                    'message' => 'Không tìm thấy lớp học'
                ], 404);
            }
            
            $updatedClass = $this->classService->updateClass($class, $request->validated());
            
            // Clear related cache
            $cacheKey = "auth:class:{$id}";
            Cache::forget($cacheKey);
            
            // Clear faculty and lecturer cache if they exist
            if ($updatedClass->faculty_id) {
                Cache::forget("auth:classes:faculty:{$updatedClass->faculty_id}");
            }
            if ($updatedClass->lecturer_id) {
                Cache::forget("auth:classes:lecturer:{$updatedClass->lecturer_id}");
            }
            
            return response()->json([
                'message' => 'Cập nhật lớp học thành công',
                'data' => new ClassResource($updatedClass)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi cập nhật lớp học',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa class
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $class = $this->classService->getClassById($id);
            
            if (!$class) {
                return response()->json([
                    'message' => 'Không tìm thấy lớp học'
                ], 404);
            }
            
            // Store faculty and lecturer IDs before deletion for cache clearing
            $facultyId = $class->faculty_id;
            $lecturerId = $class->lecturer_id;
            
            $this->classService->deleteClass($class);
            
            // Clear related cache
            $cacheKey = "auth:class:{$id}";
            Cache::forget($cacheKey);
            
            if ($facultyId) {
                Cache::forget("auth:classes:faculty:{$facultyId}");
            }
            if ($lecturerId) {
                Cache::forget("auth:classes:lecturer:{$lecturerId}");
            }
            
            return response()->json([
                'message' => 'Xóa lớp học thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi xóa lớp học',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách classes theo faculty
     */
    public function getByFaculty(int $facultyId): JsonResponse
    {
        try {
            // Try to get from cache first
            $cacheKey = "auth:classes:faculty:{$facultyId}";
            $cachedClasses = Cache::get($cacheKey);
            
            if ($cachedClasses) {
                return response()->json([
                    'message' => 'Danh sách lớp theo khoa (from cache)',
                    'data' => ClassResource::collection($cachedClasses),
                    'source' => 'cache'
                ]);
            }
            
            $classes = $this->classService->getClassesByFaculty($facultyId);
            
            // Cache for 1 hour (3600 seconds)
            Cache::put($cacheKey, $classes, 3600);
            
            return response()->json([
                'message' => 'Danh sách lớp theo khoa',
                'data' => ClassResource::collection($classes),
                'source' => 'database'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi lấy danh sách lớp học',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách classes theo lecturer
     */
    public function getByLecturer(int $lecturerId): JsonResponse
    {
        try {
            // Try to get from cache first
            $cacheKey = "auth:classes:lecturer:{$lecturerId}";
            $cachedClasses = Cache::get($cacheKey);
            
            if ($cachedClasses) {
                return response()->json([
                    'message' => 'Danh sách lớp theo giảng viên (from cache)',
                    'data' => ClassResource::collection($cachedClasses),
                    'source' => 'cache'
                ]);
            }
            
            $classes = $this->classService->getClassesByLecturer($lecturerId);
            
            // Cache for 1 hour (3600 seconds)
            Cache::put($cacheKey, $classes, 3600);
            
            return response()->json([
                'message' => 'Danh sách lớp theo giảng viên',
                'data' => ClassResource::collection($classes),
                'source' => 'database'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi lấy danh sách lớp học',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
