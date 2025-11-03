<?php

namespace Modules\Auth\app\Http\Controllers\AuthUserController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Auth\app\Services\AuthUserService\AuthService;
use Modules\Auth\app\Http\Requests\AuthUserRequest\LoginRequest;
use Modules\Auth\app\Http\Resources\AuthUserResources\LoginResource;
use Illuminate\Http\JsonResponse;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Đăng nhập sinh viên
     */
    public function loginStudent(LoginRequest $request): JsonResponse
    {
        $user = $this->authService->loginStudent(
            $request->username,
            $request->password
        );

        if (!$user) {
            return response()->json([
                'message' => 'Thông tin đăng nhập không chính xác'
            ], 401);
        }

        return response()->json(new LoginResource($user));
    }

    /**
     * Đăng nhập giảng viên
     */
    public function loginLecturer(LoginRequest $request): JsonResponse
    {
        $user = $this->authService->loginLecturer(
            $request->username,
            $request->password
        );

        if (!$user) {
            return response()->json([
                'message' => 'Thông tin đăng nhập không chính xác'
            ], 401);
        }

        return response()->json(new LoginResource($user));
    }

    /**
     * Đăng nhập chung (tự động xác định loại user)
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $userType = $request->user_type;

        if ($userType === 'student') {
            return $this->loginStudent($request);
        } elseif ($userType === 'lecturer') {
            return $this->loginLecturer($request);
        }

        return response()->json([
            'message' => 'Loại người dùng không hợp lệ'
        ], 400);
    }

    /**
     * Làm mới JWT token
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $token = $request->bearerToken();

            if (!$token) {
                return response()->json([
                    'message' => 'Token không được cung cấp'
                ], 401);
            }

            $newToken = $this->authService->refreshToken($token);

            return response()->json([
                'message' => 'Token được làm mới thành công',
                'token' => $newToken
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Không thể làm mới token',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Lấy thông tin user đầy đủ từ database
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $token = $request->bearerToken();

            if (!$token) {
                return response()->json([
                    'message' => 'Token không được cung cấp'
                ], 401);
            }

            $payload = $this->authService->validateToken($token);

            if (!$payload) {
                return response()->json([
                    'message' => 'Token không hợp lệ'
                ], 401);
            }

            // Lấy thông tin đầy đủ từ database dựa trên user_type
            $userData = [];

            if ($payload->user_type === 'student') {
                // 1. Lấy tài khoản sinh viên bằng 'sub' của token (chính là student_account.id)
                $studentAccount = DB::table('student_account')->where('id', $payload->sub)->first();

                if ($studentAccount) {
                    $student = DB::table('student')->where('id', $studentAccount->student_id)->first();
                    $class = $student ? DB::table('class')->where('id', $student->class_id)->first() : null;
                    $userData = [
                        'id' => $studentAccount->id, // Đây là ID từ student_account
                        'full_name' => $student->full_name ?? 'Unknown', // Lấy từ $student
                        'email' => $student->email ?? 'unknown@email.com', // Lấy từ $student
                        'user_type' => 'student',
                        'student_info' => [
                            'student_code' => $student->student_code ?? null,
                            'class' => $class ? [
                                'id' => $class->id,
                                'class_name' => $class->class_name ?? 'N/A',
                                'class_code' => $class->class_code ?? 'N/A'
                            ] : null
                        ],
                        'account' => [
                            'username' => $studentAccount->username,
                            'is_admin' => false
                        ]
                    ];
                }
            } elseif ($payload->user_type === 'lecturer') {
                $lecturerAccount = DB::table('lecturer_account')->where('lecturer_id', $payload->sub)->first();
                if ($lecturerAccount) {
                    $lecturer = DB::table('lecturer')->where('id', $lecturerAccount->lecturer_id)->first();
                    $department = DB::table('department')->where('id', $lecturer->department_id)->first();
                    $userData = [
                        'id' => $lecturerAccount->lecturer_id,
                        'full_name' => $lecturer->full_name ?? 'Unknown',
                        'email' => $lecturer->email ?? 'unknown@email.com',
                        'user_type' => 'lecturer',
                        'lecturer_info' => [
                            'lecturer_code' => $lecturer->lecturer_code ?? null,
                            'department' => $lecturer ? [
                                'id' => $lecturer->department_id ?? null,
                                'name' => $department->name ?? 'N/A',
                                'type' => $department->type ?? 'N/A'
                            ] : null
                        ],
                        'account' => [
                            'username' => $lecturerAccount->username,
                            'is_admin' => $lecturerAccount->is_admin ?? false
                        ]
                    ];
                }
            }

            if (empty($userData)) {
                return response()->json([
                    'message' => 'User không tồn tại'
                ], 404);
            }

            return response()->json([
                'message' => 'Thông tin user',
                'data' => $userData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Đăng xuất
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $token = $request->bearerToken();

            if ($token) {
                // Có thể thêm token vào blacklist nếu cần
                $this->authService->invalidateToken($token);
            }

            return response()->json([
                'message' => 'Đăng xuất thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi đăng xuất',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}