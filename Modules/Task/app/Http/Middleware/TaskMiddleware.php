<?php

namespace Modules\Task\app\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Task\app\Services\PermissionService;
use Modules\Task\app\Exceptions\TaskException;

/**
 * TaskMiddleware - Xử lý authorization cho Task module
 * 
 * Middleware này đảm bảo user có quyền truy cập vào Task operations
 * Tuân thủ nguyên tắc bảo mật: Verify trước khi cho phép access
 */
class TaskMiddleware
{
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * ✅ Xử lý request với proper authorization checks
     * 
     * @param Request $request
     * @param Closure $next
     * @param string|null $action Specific action cần check (optional)
     * @param string|null $resource Resource type cần check (optional)
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ?string $action = null, ?string $resource = null)
    {
        try {
            // Lấy user info từ JWT middleware (đã được xử lý trước đó)
            $userId = $request->attributes->get('jwt_user_id');
            $userType = $request->attributes->get('jwt_user_type');
            
            if (!$userId || !$userType) {
                Log::warning('TaskMiddleware: Missing user authentication info', [
                    'route' => $request->route()?->getName(),
                    'method' => $request->method(),
                    'url' => $request->url()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Thông tin xác thực không hợp lệ',
                    'error_code' => 'AUTH_INVALID'
                ], 401);
            }

            // Tạo user context object
            $userContext = $this->createUserContext($userId, $userType, $request);
            
            // Thực hiện permission checks
            $this->validatePermissions($request, $userContext, $action, $resource);
            
            // Thêm user context vào request để controller sử dụng
            $request->attributes->set('user_context', $userContext);
            $request->attributes->set('task_permissions', $this->getUserPermissions($userContext));
            
            // Log successful authorization
            // Log::debug('TaskMiddleware: Authorization successful', [
            //     'user_id' => $userId,
            //     'user_type' => $userType,
            //     'action' => $action,
            //     'resource' => $resource,
            //     'route' => $request->route()?->getName()
            // ]);

            return $next($request);
            
        } catch (TaskException $e) {
            Log::warning('TaskMiddleware: Permission denied', [
                'user_id' => $userId ?? 'unknown',
                'user_type' => $userType ?? 'unknown',
                'action' => $action,
                'resource' => $resource,
                'error' => $e->getMessage(),
                'route' => $request->route()?->getName()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
                'context' => $e->getContext()
            ], $e->getCode() ?: 403);
            
        } catch (\Exception $e) {
            Log::error('TaskMiddleware: Unexpected error', [
                'user_id' => $userId ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi kiểm tra quyền truy cập',
                'error_code' => 'AUTH_ERROR'
            ], 500);
        }
    }

    /**
     * ✅ Tạo user context object từ JWT data
     */
    private function createUserContext(int $userId, string $userType, Request $request): object
    {
        $payload = $request->attributes->get('jwt_payload');
        
        return (object) [
            'id' => $userId,
            'user_type' => $userType,
            'username' => $payload->username ?? null,
            'email' => $payload->email ?? null,
            'full_name' => $payload->full_name ?? null,
            'is_admin' => $request->attributes->get('is_admin', false),
            'admin_lecturer_id' => $request->attributes->get('admin_lecturer_id'),
            'jwt_payload' => $payload
        ];
    }

    /**
     * ✅ Validate permissions dựa trên route và action
     */
    private function validatePermissions(Request $request, object $userContext, ?string $action, ?string $resource): void
    {
        $route = $request->route();
        $routeName = $route?->getName();
        $method = $request->method();
        
        // Nếu có action và resource được specify explicitly
        if ($action && $resource) {
            if (!$this->permissionService->canPerformAction($userContext, $action, $resource)) {
                throw TaskException::accessDenied($action, 0);
            }
            return;
        }
        
        // Auto-detect permissions dựa trên route patterns
        $this->validateRoutePermissions($request, $userContext, $routeName, $method);
    }

    /**
     * ✅ Validate permissions dựa trên route patterns
     */
    private function validateRoutePermissions(Request $request, object $userContext, ?string $routeName, string $method): void
    {
        // Admin routes - chỉ admin mới access được
        if (str_contains($routeName ?? '', 'admin') || 
            str_contains($routeName ?? '', 'overview') ||
            str_contains($routeName ?? '', 'all')) {
            
            if (!$this->permissionService->isAdmin($userContext)) {
                throw TaskException::accessDenied('admin_access', 0);
            }
            return;
        }
        
        // Lecturer-only routes
        if (str_contains($routeName ?? '', 'lecturer') || 
            str_contains($routeName ?? '', 'created') ||
            str_contains($routeName ?? '', 'assign') ||
            $method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
            
            if (!$this->permissionService->canCreateTasks($userContext)) {
                throw TaskException::accessDenied('lecturer_access', 0);
            }
            return;
        }
        
        // Task-specific permissions
        if ($request->route('task')) {
            $taskId = (int) $request->route('task');
            $this->validateTaskSpecificPermissions($request, $userContext, $taskId, $method);
            return;
        }
        
        // Default: authenticated user permissions
        if (!$this->permissionService->isAuthenticated($userContext)) {
            throw TaskException::accessDenied('authenticated_access', 0);
        }
    }

    /**
     * ✅ Validate permissions cho specific task
     */
    private function validateTaskSpecificPermissions(Request $request, object $userContext, int $taskId, string $method): void
    {
        switch ($method) {
            case 'GET':
                if (!$this->permissionService->canViewTask($userContext, $taskId)) {
                    throw TaskException::accessDenied('view_task', $taskId);
                }
                break;
                
            case 'PUT':
            case 'PATCH':
                if (!$this->permissionService->canEditTask($userContext, $taskId)) {
                    throw TaskException::accessDenied('edit_task', $taskId);
                }
                break;
                
            case 'DELETE':
                if (!$this->permissionService->canDeleteTask($userContext, $taskId)) {
                    throw TaskException::accessDenied('delete_task', $taskId);
                }
                break;
        }
    }

    /**
     * ✅ Lấy permissions của user để controller sử dụng
     */
    private function getUserPermissions(object $userContext): array
    {
        return [
            'can_create_tasks' => $this->permissionService->canCreateTasks($userContext),
            'can_view_all_tasks' => $this->permissionService->canViewAllTasks($userContext),
            'can_manage_users' => $this->permissionService->canManageUsers($userContext),
            'can_generate_reports' => $this->permissionService->canGenerateReports($userContext),
            'is_admin' => $this->permissionService->isAdmin($userContext),
            'is_lecturer' => $this->permissionService->isLecturer($userContext),
            'is_student' => $this->permissionService->isStudent($userContext)
        ];
    }
}
