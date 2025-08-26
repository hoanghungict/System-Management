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
        $isAdmin = false;

        // Kiểm tra nếu là lecturer, xem có phải admin không
        if ($userType === 'lecturer') {
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
                'error' => 'Admin access required'
            ], 403);
        }

        return $next($request);
    }
}
