<?php

namespace Modules\Auth\app\Http\Controllers\AuthUserController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Auth\app\Services\AuthUserService\LecturerService;
use Modules\Auth\app\Http\Requests\AuthUserRequest\CreateLecturerRequest;
use Modules\Auth\app\Http\Requests\AuthUserRequest\UpdateLecturerRequest;
use Modules\Auth\app\Http\Requests\AuthUserRequest\UpdateAdminStatusRequest;
use Modules\Auth\app\Http\Resources\AuthUserResources\UserResource;
use Illuminate\Http\JsonResponse;

class LecturerController extends Controller
{
    protected $lecturerService;

    public function __construct(LecturerService $lecturerService)
    {
        $this->lecturerService = $lecturerService;
    }

    /**
     * Hiển thị danh sách giảng viên
     */
    public function index(): JsonResponse
    {
        $lecturers = $this->lecturerService->getAllLecturers();
        return response()->json(UserResource::collection($lecturers));
    }

    /**
     * Tạo giảng viên mới và tự động tạo tài khoản
     */
    public function store(CreateLecturerRequest $request): JsonResponse
    {
        try {
            $lecturer = $this->lecturerService->createLecturerWithAccount($request->validated());
            
            return response()->json([
                'message' => 'Tạo giảng viên thành công',
                'data' => new UserResource($lecturer),
                'account_info' => [
                    'username' => 'gv_' . $lecturer->lecturer_code,
                    'password' => '123456'
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi tạo giảng viên',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hiển thị thông tin giảng viên
     */
    public function show(int $id): JsonResponse
    {
        try {
            $lecturer = $this->lecturerService->getLecturerById($id);
            
            if (!$lecturer) {
                return response()->json([
                    'message' => 'Không tìm thấy giảng viên'
                ], 404);
            }
            
            return response()->json(new UserResource($lecturer));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi lấy thông tin giảng viên',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật thông tin giảng viên
     */
    public function update(UpdateLecturerRequest $request, int $id): JsonResponse
    {
        try {
            $lecturer = $this->lecturerService->getLecturerById($id);
            
            if (!$lecturer) {
                return response()->json([
                    'message' => 'Không tìm thấy giảng viên'
                ], 404);
            }
            
            $updatedLecturer = $this->lecturerService->updateLecturer($lecturer, $request->validated());
            
            return response()->json([
                'message' => 'Cập nhật giảng viên thành công',
                'data' => new UserResource($updatedLecturer)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi cập nhật giảng viên',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa giảng viên và tài khoản liên quan
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $lecturer = $this->lecturerService->getLecturerById($id);
            
            if (!$lecturer) {
                return response()->json([
                    'message' => 'Không tìm thấy giảng viên'
                ], 404);
            }
            
            $this->lecturerService->deleteLecturer($lecturer);
            
            return response()->json([
                'message' => 'Xóa giảng viên thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi xóa giảng viên',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật quyền admin cho giảng viên
     */
    public function updateAdminStatus(UpdateAdminStatusRequest $request, int $id): JsonResponse
    {
        try {
            $lecturer = $this->lecturerService->getLecturerById($id);
            
            if (!$lecturer) {
                return response()->json([
                    'message' => 'Không tìm thấy giảng viên'
                ], 404);
            }
            
            $this->lecturerService->updateAdminStatus($lecturer, $request->is_admin);
            
            return response()->json([
                'message' => 'Cập nhật quyền admin thành công',
                'data' => new UserResource($lecturer->fresh())
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi cập nhật quyền admin',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Giảng viên xem thông tin của mình
     */
    public function showOwnProfile(Request $request): JsonResponse
    {
        try {
            $userId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type');
            
            if ($userType !== 'lecturer') {
                return response()->json([
                    'message' => 'Chỉ giảng viên mới có thể truy cập chức năng này'
                ], 403);
            }
            
            $lecturer = $this->lecturerService->getLecturerById($userId);
            
            if (!$lecturer) {
                return response()->json([
                    'message' => 'Không tìm thấy thông tin giảng viên'
                ], 404);
            }
            
            return response()->json(new UserResource($lecturer));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi lấy thông tin giảng viên',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Giảng viên cập nhật thông tin của mình
     */
    public function updateOwnProfile(Request $request): JsonResponse
    {
        try {
            $userId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type');
            
            if ($userType !== 'lecturer') {
                return response()->json([
                    'message' => 'Chỉ giảng viên mới có thể truy cập chức năng này'
                ], 403);
            }
            
            $lecturer = $this->lecturerService->getLecturerById($userId);
            
            if (!$lecturer) {
                return response()->json([
                    'message' => 'Không tìm thấy thông tin giảng viên'
                ], 404);
            }
            
            // Chỉ cho phép cập nhật một số trường nhất định
            $allowedFields = ['full_name', 'phone', 'address'];
            $updateData = array_intersect_key($request->all(), array_flip($allowedFields));
            
            $updatedLecturer = $this->lecturerService->updateLecturer($lecturer, $updateData);
            
            return response()->json([
                'message' => 'Cập nhật thông tin thành công',
                'data' => new UserResource($updatedLecturer)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi cập nhật thông tin giảng viên',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
