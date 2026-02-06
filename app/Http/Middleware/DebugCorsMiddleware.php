<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DebugCorsMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log request info để debug
        if ($request->is('api/*')) {
            /* \Log::info('DebugCorsMiddleware: API Request', [
                'path' => $request->path(),
                'fullUrl' => $request->fullUrl(),
                'method' => $request->method(),
                'origin' => $request->header('Origin'),
                'hasAuth' => $request->hasHeader('Authorization'),
            ]); */
        }

        $response = $next($request);

        // Log response info
        if ($request->is('api/*')) {
            /* \Log::info('DebugCorsMiddleware: API Response', [
                'status' => $response->getStatusCode(),
                'cors_headers' => [
                    'Access-Control-Allow-Origin' => $response->headers->get('Access-Control-Allow-Origin'),
                    'Access-Control-Allow-Methods' => $response->headers->get('Access-Control-Allow-Methods'),
                    'Access-Control-Allow-Credentials' => $response->headers->get('Access-Control-Allow-Credentials'),
                ],
            ]); */
        }

        return $response;
    }
}


