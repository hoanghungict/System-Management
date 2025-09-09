<?php

namespace Modules\Task\app\Exceptions;

use Exception;

/**
 * Custom Exception cho Task Module
 * 
 * Tuân thủ Clean Architecture: Tách biệt error handling
 */
class TaskException extends Exception
{
    protected $errorCode;
    protected $context;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null,
        string $errorCode = 'TASK_ERROR',
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->context = $context;
    }

    /**
     * Lấy error code
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Lấy context
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Exception khi không tìm thấy task
     */
    public static function taskNotFound(int $taskId): self
    {
        return new self(
            "Task with ID {$taskId} not found",
            404,
            null,
            'TASK_NOT_FOUND',
            ['task_id' => $taskId]
        );
    }

    /**
     * Exception khi không có quyền truy cập
     */
    public static function accessDenied(string $action, int $taskId): self
    {
        return new self(
            "Access denied for action '{$action}' on task {$taskId}",
            403,
            null,
            'ACCESS_DENIED',
            ['action' => $action, 'task_id' => $taskId]
        );
    }

    /**
     * Exception khi validation business rules thất bại
     */
    public static function businessRuleViolation(string $rule, array $context = []): self
    {
        return new self(
            "Business rule violation: {$rule}",
            422,
            null,
            'BUSINESS_RULE_VIOLATION',
            array_merge(['rule' => $rule], $context)
        );
    }

    /**
     * ✅ Exception khi user không được authenticate
     */
    public static function unauthenticated(string $reason = ''): self
    {
        $message = 'User not authenticated';
        if ($reason) {
            $message .= ": {$reason}";
        }
        
        return new self(
            $message,
            401,
            null,
            'UNAUTHENTICATED',
            ['reason' => $reason]
        );
    }

    /**
     * ✅ Exception khi user không có quyền truy cập resource
     */
    public static function unauthorized(string $resource, string $action, array $context = []): self
    {
        return new self(
            "Unauthorized to {$action} {$resource}",
            403,
            null,
            'UNAUTHORIZED',
            array_merge([
                'resource' => $resource,
                'action' => $action
            ], $context)
        );
    }

    /**
     * ✅ Exception khi validation input thất bại
     */
    public static function validationFailed(string $field, string $message, array $context = []): self
    {
        return new self(
            "Validation failed for {$field}: {$message}",
            422,
            null,
            'VALIDATION_FAILED',
            array_merge([
                'field' => $field,
                'validation_message' => $message
            ], $context)
        );
    }

    /**
     * ✅ Exception khi security policy bị vi phạm
     */
    public static function securityViolation(string $policy, array $context = []): self
    {
        return new self(
            "Security policy violation: {$policy}",
            403,
            null,
            'SECURITY_VIOLATION',
            array_merge(['policy' => $policy], $context)
        );
    }

    /**
     * ✅ Exception khi rate limiting bị trigger
     */
    public static function rateLimitExceeded(string $action, int $maxAttempts, array $context = []): self
    {
        return new self(
            "Rate limit exceeded for {$action}. Maximum {$maxAttempts} attempts allowed",
            429,
            null,
            'RATE_LIMIT_EXCEEDED',
            array_merge([
                'action' => $action,
                'max_attempts' => $maxAttempts
            ], $context)
        );
    }

    /**
     * ✅ Exception khi user context không hợp lệ
     */
    public static function invalidUserContext(string $reason, array $context = []): self
    {
        return new self(
            "Invalid user context: {$reason}",
            400,
            null,
            'INVALID_USER_CONTEXT',
            array_merge(['reason' => $reason], $context)
        );
    }
}
