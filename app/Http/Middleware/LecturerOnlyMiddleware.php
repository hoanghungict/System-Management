<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class LecturerOnlyMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * Cho phép cả Giảng viên VÀ Admin truy cập.
     * Admin có thể thực hiện điểm danh cho bất kỳ môn nào.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userType = $request->attributes->get('jwt_user_type');
        $jwtPayload = $request->attributes->get('jwt_payload');

        // Cho phép nếu là lecturer
        if ($userType === 'lecturer') {
            return $next($request);
        }

        // Cho phép nếu là Admin (kiểm tra is_admin từ JWT payload)
        if ($jwtPayload && isset($jwtPayload->is_admin) && $jwtPayload->is_admin === true) {
            return $next($request);
        }

        // Fallback: Kiểm tra trong database nếu là lecturer có quyền admin
        if ($userType === 'lecturer') {
            $userId = $request->attributes->get('jwt_user_id');
            $lecturerAccount = DB::table('lecturer_account')
                ->where('lecturer_id', $userId)
                ->where('is_admin', 1)
                ->first();
            
            if ($lecturerAccount) {
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'Chỉ giảng viên hoặc admin mới có thể truy cập chức năng này',
            'error' => 'Lecturer or Admin access required'
        ], 403);
    }
}
