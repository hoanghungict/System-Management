<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StudentOnlyMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userType = $request->attributes->get('jwt_user_type');

        if ($userType !== 'student') {
            return response()->json([
                'message' => 'Chỉ sinh viên mới có thể truy cập chức năng này',
                'error' => 'Student access required'
            ], 403);
        }

        return $next($request);
    }
}
