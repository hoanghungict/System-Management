<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceUtf8Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Force UTF-8 encoding for JSON responses
        if ($response->headers->get('Content-Type') === 'application/json') {
            $response->header('Content-Type', 'application/json; charset=utf-8');
        }

        return $response;
    }
}