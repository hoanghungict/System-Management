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
     * Lấy thông tin user từ JWT token
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
            
            return response()->json([
                'message' => 'Thông tin user',
                'data' => $payload
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
