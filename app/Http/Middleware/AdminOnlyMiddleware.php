<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AdminOnlyMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userType = $request->attributes->get('jwt_user_type');
        $jwtPayload = $request->attributes->get('jwt_payload');
        $isAdmin = false;

        // Kiểm tra is_admin từ JWT payload trước
        if ($jwtPayload && isset($jwtPayload->is_admin) && $jwtPayload->is_admin === true) {
            $isAdmin = true;
        }
        
        // Fallback: Kiểm tra nếu là lecturer, xem có phải admin không
        if (!$isAdmin && $userType === 'lecturer') {
            $userId = $request->attributes->get('jwt_user_id');
            
            // Kiểm tra trong database xem lecturer có phải admin không
            $lecturerAccount = DB::table('lecturer_account')
                ->where('lecturer_id', $userId)
                ->where('is_admin', 1)
                ->first();
                
            if ($lecturerAccount) {
                $isAdmin = true;
            }
        }

        if (!$isAdmin) {
            return response()->json([
                'message' => 'Bạn không có quyền truy cập chức năng này',
                'error' => 'Admin access required',
                'debug' => [
                    'user_type' => $userType,
                    'has_jwt_payload' => $jwtPayload !== null,
                    'is_admin_in_jwt' => $jwtPayload->is_admin ?? 'not set'
                ]
            ], 403);
        }

        return $next($request);
    }
}
