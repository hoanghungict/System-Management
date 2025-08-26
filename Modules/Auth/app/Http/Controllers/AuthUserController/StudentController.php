<?php

namespace Modules\Auth\app\Http\Controllers\AuthUserController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Auth\app\Services\AuthUserService\StudentService;
use Modules\Auth\app\Http\Requests\AuthUserRequest\CreateStudentRequest;
use Modules\Auth\app\Http\Requests\AuthUserRequest\UpdateStudentRequest;
use Modules\Auth\app\Http\Resources\AuthUserResources\UserResource;
use Illuminate\Http\JsonResponse;

class StudentController extends Controller
{
    protected $studentService;

    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
    }

    /**
     * Hiển thị danh sách sinh viên
     */
    public function index(): JsonResponse
    {
        $students = $this->studentService->getAllStudents();
        return response()->json(UserResource::collection($students));
    }

    /**
     * Tạo sinh viên mới và tự động tạo tài khoản
     */
    public function store(CreateStudentRequest $request): JsonResponse
    {
        try {
            $student = $this->studentService->createStudentWithAccount($request->validated());
            
            return response()->json([
                'message' => 'Tạo sinh viên thành công',
                'data' => new UserResource($student),
                'account_info' => [
                    'username' => 'sv_' . $student->student_code,
                    'password' => '123456'
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi tạo sinh viên',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hiển thị thông tin sinh viên
     */
    public function show(int $id): JsonResponse
    {
        try {
            $student = $this->studentService->getStudentById($id);
            
            if (!$student) {
                return response()->json([
                    'message' => 'Không tìm thấy sinh viên'
                ], 404);
            }
            
            return response()->json(new UserResource($student));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi lấy thông tin sinh viên',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật thông tin sinh viên
     */
    public function update(UpdateStudentRequest $request, int $id): JsonResponse
    {
        try {
            $student = $this->studentService->getStudentById($id);
            
            if (!$student) {
                return response()->json([
                    'message' => 'Không tìm thấy sinh viên'
                ], 404);
            }
            
            $updatedStudent = $this->studentService->updateStudent($student, $request->validated());
            
            return response()->json([
                'message' => 'Cập nhật sinh viên thành công',
                'data' => new UserResource($updatedStudent)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi cập nhật sinh viên',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa sinh viên và tài khoản liên quan
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $student = $this->studentService->getStudentById($id);
            
            if (!$student) {
                return response()->json([
                    'message' => 'Không tìm thấy sinh viên'
                ], 404);
            }
            
            $this->studentService->deleteStudent($student);
            
            return response()->json([
                'message' => 'Xóa sinh viên thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi xóa sinh viên',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sinh viên xem thông tin của mình
     */
    public function showOwnProfile(Request $request): JsonResponse
    {
        try {
            $userId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type');
            
            if ($userType !== 'student') {
                return response()->json([
                    'message' => 'Chỉ sinh viên mới có thể truy cập chức năng này'
                ], 403);
            }
            
            $student = $this->studentService->getStudentById($userId);
            
            if (!$student) {
                return response()->json([
                    'message' => 'Không tìm thấy thông tin sinh viên'
                ], 404);
            }
            
            return response()->json(new UserResource($student));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi lấy thông tin sinh viên',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sinh viên cập nhật thông tin của mình
     */
    public function updateOwnProfile(Request $request): JsonResponse
    {
        try {
            $userId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type');
            
            if ($userType !== 'student') {
                return response()->json([
                    'message' => 'Chỉ sinh viên mới có thể truy cập chức năng này'
                ], 403);
            }
            
            $student = $this->studentService->getStudentById($userId);
            
            if (!$student) {
                return response()->json([
                    'message' => 'Không tìm thấy thông tin sinh viên'
                ], 404);
            }
            
            // Chỉ cho phép cập nhật một số trường nhất định
            $allowedFields = ['full_name', 'phone', 'address'];
            $updateData = array_intersect_key($request->all(), array_flip($allowedFields));
            
            $updatedStudent = $this->studentService->updateStudent($student, $updateData);
            
            return response()->json([
                'message' => 'Cập nhật thông tin thành công',
                'data' => new UserResource($updatedStudent)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi cập nhật thông tin sinh viên',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
