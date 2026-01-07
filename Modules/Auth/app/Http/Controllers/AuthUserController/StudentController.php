<?php

namespace Modules\Auth\app\Http\Controllers\AuthUserController;

use App\Http\Controllers\Controller;
use Modules\Auth\app\Models\Student;
use Illuminate\Http\Request;
use Modules\Auth\app\Services\AuthUserService\StudentService;
use Modules\Auth\app\Http\Requests\AuthUserRequest\CreateStudentRequest;
use Modules\Auth\app\Http\Requests\AuthUserRequest\UpdateStudentRequest;
use Modules\Auth\app\Http\Resources\AuthUserResources\UserResource;
use Illuminate\Http\JsonResponse;
use Modules\Notifications\app\Services\KafkaService\KafkaProducerService;
use Modules\Auth\app\Http\Requests\AuthUserRequest\UpdateAccountRequest;
use Illuminate\Support\Facades\Hash;


class StudentController extends Controller
{
    protected $studentService;
    protected $kafkaProducer;

    public function __construct(StudentService $studentService, KafkaProducerService $kafkaProducer)
    {
        $this->studentService = $studentService;
        $this->kafkaProducer = $kafkaProducer;
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
            
            // Cho phép cập nhật các trường thông tin cá nhân
            $allowedFields = [
                'full_name', 
                'phone', 
                'address', 
                'email', 
                'birth_date',
                'gender'
            ];
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
    public function getStudentByClassId(int $classId): JsonResponse
    {
        $students = $this->studentService->getStudentByClassId($classId);
        return response()->json(UserResource::collection($students));
    }

    /**
     * Lấy thông tin tài khoản của sinh viên
     */
    public function getAccount(int $id): JsonResponse
    {
        try {
            $student = $this->studentService->getStudentById($id);
            
            if (!$student) {
                return response()->json([
                    'message' => 'Không tìm thấy sinh viên'
                ], 404);
            }
            
            // Load relation account
            $account = $student->account;
            
            if (!$account) {
                return response()->json([
                    'message' => 'Sinh viên chưa có tài khoản'
                ], 404);
            }
            
            return response()->json([
                'username' => $account->username,
                'account_status' => $student->account_status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi lấy thông tin tài khoản',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật thông tin tài khoản sinh viên (đổi mật khẩu)
     */
    public function updateAccount(UpdateAccountRequest $request, int $id): JsonResponse
    {
        try {
            $student = $this->studentService->getStudentById($id);
            
            if (!$student) {
                return response()->json([
                    'message' => 'Không tìm thấy sinh viên'
                ], 404);
            }
            
            $account = $student->account;
            
            if (!$account) {
                return response()->json([
                    'message' => 'Sinh viên chưa có tài khoản'
                ], 404);
            }
            
            if ($request->filled('password')) {
                $account->password = Hash::make($request->password);
                $account->save();
            }
            
            // Nếu muốn update account_status thì cần update bảng student, hiện tại request chưa support field này nhưng có thể mở rộng sau
            
            return response()->json([
                'message' => 'Cập nhật tài khoản thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi cập nhật tài khoản',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
