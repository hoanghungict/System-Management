<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'message' => 'Token không được cung cấp'
            ], 401);
        }

        try {
            $secret = config('jwt.secret');
            $algo = config('jwt.algorithm', 'HS256');

            if (!$secret) {
                return response()->json([
                    'message' => 'JWT secret is not configured'
                ], 500);
            }

            $decoded = JWT::decode($token, new Key($secret, $algo));
            
            // Kiểm tra token hết hạn
            if (isset($decoded->exp) && $decoded->exp < time()) {
                return response()->json([
                    'message' => 'Token đã hết hạn'
                ], 401);
            }

            // Lưu thông tin user vào request để sử dụng trong controller
            $request->attributes->set('jwt_payload', $decoded);
            $request->attributes->set('jwt_user_id', $decoded->sub);
            $request->attributes->set('jwt_user_type', $decoded->user_type ?? null);

            return $next($request);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Token không hợp lệ',
                'error' => $e->getMessage()
            ], 401);
        }
    }
}
