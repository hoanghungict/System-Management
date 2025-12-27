<?php

declare(strict_types=1);

namespace Modules\Task\app\Http\Controllers\Task\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Base Action class for Task Actions
 */
abstract class BaseTaskAction
{
    protected function getUserId(Request $request): ?int
    {
        $userId = $request->attributes->get('jwt_user_id');
        return $userId ? (int)$userId : null;
    }

    protected function getUserData(Request $request): ?\stdClass
    {
        return $request->attributes->get('jwt_payload');
    }

    protected function getUserType(Request $request): ?string
    {
        return $request->attributes->get('jwt_user_type');
    }

    protected function createUserContext(Request $request): object
    {
        return (object) [
            'id' => $this->getUserId($request),
            'user_type' => $this->getUserType($request),
        ];
    }

    protected function unauthorizedResponse(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
    }

    protected function notFoundResponse(string $resource = 'Resource'): JsonResponse
    {
        return response()->json(['success' => false, 'message' => "{$resource} not found"], 404);
    }

    protected function accessDeniedResponse(string $message = 'Access denied'): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], 403);
    }

    protected function successResponse(mixed $data, string $message, int $code = 200): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data, 'message' => $message], $code);
    }

    protected function errorResponse(string $message, \Exception $e, int $code = 500): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message, 'error' => $e->getMessage()], $code);
    }
}
