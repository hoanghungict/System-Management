<?php

namespace Modules\Auth\app\Http\Controllers\AuthUserController;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Auth\app\Http\Requests\AuthUserRequest\ChangePasswordRequest;
use Modules\Auth\app\Services\AuthUserService\AuthService;

class PasswordController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Đổi mật khẩu cho user đang đăng nhập
     *
     * @param ChangePasswordRequest $request
     * @return JsonResponse
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            // Lấy token từ header
            $token = $request->bearerToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token không được cung cấp'
                ], 401);
            }

            // Validate và lấy payload từ token
            $payload = $this->authService->validateToken($token);

            if (!$payload) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token không hợp lệ'
                ], 401);
            }

            // Lấy thông tin account dựa vào user_type
            $accountTable = null;
            $accountId = null;

            if ($payload->user_type === 'student') {
                $accountTable = 'student_account';
                // payload->sub là student_account.id
                $accountId = $payload->sub;
            } elseif ($payload->user_type === 'lecturer') {
                $accountTable = 'lecturer_account';
                // payload->sub là lecturer.id, cần tìm lecturer_account
                $lecturerAccount = DB::table('lecturer_account')
                    ->where('lecturer_id', $payload->sub)
                    ->first();
                
                if (!$lecturerAccount) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không tìm thấy tài khoản'
                    ], 404);
                }
                
                $accountId = $lecturerAccount->id;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Loại người dùng không hợp lệ'
                ], 400);
            }

            // Lấy account record
            $account = DB::table($accountTable)->where('id', $accountId)->first();

            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy tài khoản'
                ], 404);
            }

            // Verify current password
            if (!Hash::check($request->current_password, $account->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mật khẩu hiện tại không đúng',
                    'errors' => [
                        'current_password' => ['Mật khẩu hiện tại không đúng']
                    ]
                ], 422);
            }

            // Check if new password is same as current (double check)
            if (Hash::check($request->new_password, $account->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mật khẩu mới phải khác mật khẩu hiện tại'
                ], 400);
            }

            // Update password
            DB::table($accountTable)
                ->where('id', $accountId)
                ->update([
                    'password' => Hash::make($request->new_password),
                    'updated_at' => now()
                ]);

            // Log activity
            Log::info('Password changed successfully', [
                'user_id' => $payload->sub,
                'user_type' => $payload->user_type,
                'account_table' => $accountTable,
                'account_id' => $accountId,
                'timestamp' => now(),
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đổi mật khẩu thành công'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Change password failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['current_password', 'new_password', 'new_password_confirmation'])
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi đổi mật khẩu. Vui lòng thử lại sau.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}

