<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LecturerOnlyMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userType = $request->attributes->get('jwt_user_type');

        if ($userType !== 'lecturer') {
            return response()->json([
                'message' => 'Chỉ giảng viên mới có thể truy cập chức năng này',
                'error' => 'Lecturer access required'
            ], 403);
        }

        return $next($request);
    }
}
