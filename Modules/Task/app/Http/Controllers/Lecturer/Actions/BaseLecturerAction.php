<?php

declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers\Lecturer\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Base Action class for Lecturer Task Actions
 * 
 * Provides common utility methods for all actions
 */
abstract class BaseLecturerAction
{
    /**
     * Get authenticated user ID from JWT payload
     */
    protected function getUserId(Request $request): ?int
    {
        $userId = $request->attributes->get('jwt_user_id');
        return $userId ? (int)$userId : null;
    }

    /**
     * Get authenticated user data from JWT payload
     */
    protected function getUserData(Request $request): ?\stdClass
    {
        return $request->attributes->get('jwt_payload');
    }

    /**
     * Get user type from JWT payload
     */
    protected function getUserType(Request $request): ?string
    {
        return $request->attributes->get('jwt_user_type');
    }

    /**
     * Create user context object
     */
    protected function createUserContext(Request $request): object
    {
        return (object) [
            'id' => $this->getUserId($request),
            'user_type' => $this->getUserType($request) ?? 'lecturer',
        ];
    }

    /**
     * Return unauthorized response
     */
    protected function unauthorizedResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'User not authenticated'
        ], 401);
    }

    /**
     * Return not found response
     */
    protected function notFoundResponse(string $resource = 'Resource'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => "{$resource} not found"
        ], 404);
    }

    /**
     * Return access denied response
     */
    protected function accessDeniedResponse(string $message = 'Access denied'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], 403);
    }

    /**
     * Return success response
     */
    protected function successResponse(mixed $data, string $message, int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message
        ], $code);
    }

    /**
     * Return error response
     */
    protected function errorResponse(string $message, \Exception $e, int $code = 500): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => $e->getMessage()
        ], $code);
    }
}
