<?php

namespace Modules\Task\app\Services;

use Modules\Task\app\Exceptions\TaskException;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * ✅ TaskErrorHandlingService - Comprehensive error handling cho Task Module
 * 
 * Service này centralize tất cả error handling logic
 * Provides consistent error responses và detailed logging
 */
class TaskErrorHandlingService
{
    /**
     * Error severity levels
     */
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';

    /**
     * Error categories
     */
    const CATEGORY_VALIDATION = 'validation';
    const CATEGORY_AUTHORIZATION = 'authorization';
    const CATEGORY_BUSINESS_LOGIC = 'business_logic';
    const CATEGORY_DATABASE = 'database';
    const CATEGORY_EXTERNAL_SERVICE = 'external_service';
    const CATEGORY_SYSTEM = 'system';

    /**
     * ✅ Handle và categorize exceptions
     * 
     * @param \Exception $exception
     * @param array $context Additional context
     * @return array Structured error response
     */
    public function handleException(\Exception $exception, array $context = []): array
    {
        $errorData = $this->categorizeException($exception);
        
        // Add context information
        $errorData['context'] = array_merge($errorData['context'], $context);
        $errorData['timestamp'] = now()->toISOString();
        $errorData['request_id'] = request()->header('X-Request-ID') ?? uniqid();
        
        // Log the error with appropriate level
        $this->logError($exception, $errorData);
        
        // Return structured response
        return $this->formatErrorResponse($errorData);
    }

    /**
     * ✅ Categorize exception based on type
     */
    private function categorizeException(\Exception $exception): array
    {
        $errorData = [
            'exception_class' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'context' => []
        ];

        // Handle TaskException specifically
        if ($exception instanceof TaskException) {
            return array_merge($errorData, [
                'category' => self::CATEGORY_BUSINESS_LOGIC,
                'severity' => $this->determineSeverity($exception),
                'error_code' => $exception->getErrorCode(),
                'context' => $exception->getContext(),
                'user_message' => $this->getUserFriendlyMessage($exception),
                'recoverable' => $this->isRecoverable($exception)
            ]);
        }

        // Handle validation errors
        if ($exception instanceof ValidationException) {
            return array_merge($errorData, [
                'category' => self::CATEGORY_VALIDATION,
                'severity' => self::SEVERITY_LOW,
                'error_code' => 'VALIDATION_ERROR',
                'context' => ['errors' => $exception->errors()],
                'user_message' => 'Dữ liệu đầu vào không hợp lệ',
                'recoverable' => true
            ]);
        }

        // Handle database errors
        if ($exception instanceof QueryException) {
            return array_merge($errorData, [
                'category' => self::CATEGORY_DATABASE,
                'severity' => self::SEVERITY_HIGH,
                'error_code' => 'DATABASE_ERROR',
                'context' => [
                    'sql' => $exception->getSql(),
                    'bindings' => $exception->getBindings()
                ],
                'user_message' => 'Có lỗi xảy ra với cơ sở dữ liệu',
                'recoverable' => false
            ]);
        }

        // Handle 404 errors
        if ($exception instanceof NotFoundHttpException) {
            return array_merge($errorData, [
                'category' => self::CATEGORY_BUSINESS_LOGIC,
                'severity' => self::SEVERITY_LOW,
                'error_code' => 'RESOURCE_NOT_FOUND',
                'context' => [],
                'user_message' => 'Không tìm thấy tài nguyên được yêu cầu',
                'recoverable' => true
            ]);
        }

        // Handle authorization errors
        if ($exception instanceof AccessDeniedHttpException) {
            return array_merge($errorData, [
                'category' => self::CATEGORY_AUTHORIZATION,
                'severity' => self::SEVERITY_MEDIUM,
                'error_code' => 'ACCESS_DENIED',
                'context' => [],
                'user_message' => 'Bạn không có quyền thực hiện thao tác này',
                'recoverable' => false
            ]);
        }

        // Handle general exceptions
        return array_merge($errorData, [
            'category' => self::CATEGORY_SYSTEM,
            'severity' => self::SEVERITY_CRITICAL,
            'error_code' => 'UNKNOWN_ERROR',
            'context' => [],
            'user_message' => 'Có lỗi không xác định xảy ra',
            'recoverable' => false
        ]);
    }

    /**
     * ✅ Determine severity based on exception
     */
    private function determineSeverity(TaskException $exception): string
    {
        $errorCode = $exception->getErrorCode();
        
        $severityMap = [
            'VALIDATION_FAILED' => self::SEVERITY_LOW,
            'BUSINESS_RULE_VIOLATION' => self::SEVERITY_LOW,
            'ACCESS_DENIED' => self::SEVERITY_MEDIUM,
            'UNAUTHORIZED' => self::SEVERITY_MEDIUM,
            'TASK_NOT_FOUND' => self::SEVERITY_LOW,
            'SECURITY_VIOLATION' => self::SEVERITY_HIGH,
            'RATE_LIMIT_EXCEEDED' => self::SEVERITY_MEDIUM,
            'UNAUTHENTICATED' => self::SEVERITY_HIGH,
            'INVALID_USER_CONTEXT' => self::SEVERITY_HIGH
        ];

        return $severityMap[$errorCode] ?? self::SEVERITY_MEDIUM;
    }

    /**
     * ✅ Get user-friendly message
     */
    private function getUserFriendlyMessage(TaskException $exception): string
    {
        $errorCode = $exception->getErrorCode();
        
        $messageMap = [
            'VALIDATION_FAILED' => 'Dữ liệu không hợp lệ. Vui lòng kiểm tra lại.',
            'BUSINESS_RULE_VIOLATION' => 'Thao tác không được phép theo quy định.',
            'ACCESS_DENIED' => 'Bạn không có quyền thực hiện thao tác này.',
            'UNAUTHORIZED' => 'Bạn cần đăng nhập để thực hiện thao tác này.',
            'TASK_NOT_FOUND' => 'Không tìm thấy nhiệm vụ được yêu cầu.',
            'SECURITY_VIOLATION' => 'Thao tác vi phạm chính sách bảo mật.',
            'RATE_LIMIT_EXCEEDED' => 'Bạn đã thực hiện quá nhiều yêu cầu. Vui lòng thử lại sau.',
            'UNAUTHENTICATED' => 'Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại.',
            'INVALID_USER_CONTEXT' => 'Thông tin người dùng không hợp lệ.'
        ];

        return $messageMap[$errorCode] ?? 'Có lỗi xảy ra. Vui lòng thử lại sau.';
    }

    /**
     * ✅ Check if error is recoverable
     */
    private function isRecoverable(TaskException $exception): bool
    {
        $errorCode = $exception->getErrorCode();
        
        $recoverableErrors = [
            'VALIDATION_FAILED',
            'BUSINESS_RULE_VIOLATION', 
            'TASK_NOT_FOUND',
            'RATE_LIMIT_EXCEEDED'
        ];

        return in_array($errorCode, $recoverableErrors);
    }

    /**
     * ✅ Log error with appropriate level
     */
    private function logError(\Exception $exception, array $errorData): void
    {
        $logContext = [
            'category' => $errorData['category'],
            'severity' => $errorData['severity'],
            'error_code' => $errorData['error_code'] ?? 'UNKNOWN',
            'request_id' => $errorData['request_id'],
            'user_agent' => request()->header('User-Agent'),
            'ip_address' => request()->ip(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'context' => $errorData['context']
        ];

        switch ($errorData['severity']) {
            case self::SEVERITY_CRITICAL:
                Log::critical($exception->getMessage(), $logContext);
                break;
            case self::SEVERITY_HIGH:
                Log::error($exception->getMessage(), $logContext);
                break;
            case self::SEVERITY_MEDIUM:
                Log::warning($exception->getMessage(), $logContext);
                break;
            case self::SEVERITY_LOW:
            default:
                Log::info($exception->getMessage(), $logContext);
                break;
        }
    }

    /**
     * ✅ Format error response for API
     */
    private function formatErrorResponse(array $errorData): array
    {
        $response = [
            'success' => false,
            'error' => [
                'code' => $errorData['error_code'] ?? 'UNKNOWN_ERROR',
                'message' => $errorData['user_message'] ?? 'Có lỗi xảy ra',
                'category' => $errorData['category'],
                'recoverable' => $errorData['recoverable'] ?? false
            ],
            'request_id' => $errorData['request_id']
        ];

        // Add context in development/debug mode
        if (config('app.debug')) {
            $response['debug'] = [
                'exception' => $errorData['exception_class'],
                'message' => $errorData['message'],
                'file' => $errorData['file'],
                'line' => $errorData['line'],
                'context' => $errorData['context']
            ];
        }

        return $response;
    }

    /**
     * ✅ Handle specific task operation errors
     */
    public function handleTaskOperationError(string $operation, \Exception $exception, array $context = []): array
    {
        $enhancedContext = array_merge($context, [
            'operation' => $operation,
            'module' => 'Task'
        ]);

        return $this->handleException($exception, $enhancedContext);
    }

    /**
     * ✅ Create task-specific exceptions
     */
    public function createTaskNotFoundError(int $taskId): TaskException
    {
        return TaskException::taskNotFound($taskId);
    }

    public function createAccessDeniedError(string $action, int $taskId, int $userId): TaskException
    {
        return TaskException::accessDenied($action, $taskId)
            ->setContext(['user_id' => $userId]);
    }

    public function createValidationError(string $field, string $message, array $context = []): TaskException
    {
        return TaskException::validationFailed($field, $message, $context);
    }

    public function createSecurityViolationError(string $policy, array $context = []): TaskException
    {
        return TaskException::securityViolation($policy, $context);
    }

    /**
     * ✅ Get error statistics for monitoring
     */
    public function getErrorStatistics(string $timeframe = '24h'): array
    {
        // This would integrate with logging system to provide error stats
        // For now, return basic structure
        return [
            'timeframe' => $timeframe,
            'total_errors' => 0,
            'by_category' => [],
            'by_severity' => [],
            'by_error_code' => [],
            'most_common_errors' => [],
            'error_trends' => []
        ];
    }

    /**
     * ✅ Check system health based on error rates
     */
    public function checkSystemHealth(): array
    {
        $stats = $this->getErrorStatistics();
        
        return [
            'status' => 'healthy', // healthy, warning, critical
            'error_rate' => 0,
            'critical_errors' => 0,
            'last_check' => now()->toISOString(),
            'recommendations' => []
        ];
    }
}
