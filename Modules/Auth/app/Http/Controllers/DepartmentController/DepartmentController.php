<?php

namespace Modules\Auth\app\Http\Controllers\DepartmentController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Auth\app\Services\DepartmentService\DepartmentService;
use Modules\Auth\app\Http\Requests\DepartmentRequest\CreateDepartmentRequest;
use Modules\Auth\app\Http\Requests\DepartmentRequest\UpdateDepartmentRequest;
use Modules\Auth\app\Http\Resources\DepartmentResources\DepartmentResource;
use Illuminate\Http\JsonResponse;

class DepartmentController extends Controller
{
    protected $departmentService;

    public function __construct(DepartmentService $departmentService)
    {
        $this->departmentService = $departmentService;
    }

    /**
     * Hiển thị danh sách departments
     */
    public function index(): JsonResponse
    {
        $departments = $this->departmentService->getAllDepartments();
        return response()->json(DepartmentResource::collection($departments));
    }

    /**
     * Tạo department mới
     */
    public function store(CreateDepartmentRequest $request): JsonResponse
    {
        try {
            $department = $this->departmentService->createDepartment($request->validated());
            
            return response()->json([
                'message' => 'Tạo department thành công',
                'data' => new DepartmentResource($department)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi tạo department',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hiển thị thông tin department
     */
    public function show(int $id): JsonResponse
    {
        try {
            $department = $this->departmentService->getDepartmentById($id);
            
            if (!$department) {
                return response()->json([
                    'message' => 'Không tìm thấy department'
                ], 404);
            }
            
            return response()->json(new DepartmentResource($department));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi lấy thông tin department',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật thông tin department
     */
    public function update(UpdateDepartmentRequest $request, int $id): JsonResponse
    {
        try {
            $department = $this->departmentService->getDepartmentById($id);
            
            if (!$department) {
                return response()->json([
                    'message' => 'Không tìm thấy department'
                ], 404);
            }
            
            $updatedDepartment = $this->departmentService->updateDepartment($department, $request->validated());
            
            return response()->json([
                'message' => 'Cập nhật department thành công',
                'data' => new DepartmentResource($updatedDepartment)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi cập nhật department',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa department
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $department = $this->departmentService->getDepartmentById($id);
            
            if (!$department) {
                return response()->json([
                    'message' => 'Không tìm thấy department'
                ], 404);
            }
            
            $this->departmentService->deleteDepartment($department);
            
            return response()->json([
                'message' => 'Xóa department thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi xóa department',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách departments theo cấu trúc đơn giản
     */
    public function tree(): JsonResponse
    {
        try {
            $departments = $this->departmentService->getAllDepartmentsWithLevel();
            return response()->json($departments);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi lấy cấu trúc department',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
