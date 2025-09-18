<?php

namespace Modules\Task\app\Lecturer\Exceptions;

use Exception;

/**
 * Lecturer Task Exception
 * 
 * Exception dành riêng cho Lecturer package
 */
class LecturerTaskException extends Exception
{
    protected $statusCode;

    public function __construct($message = "", $statusCode = 500, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Task not found
     */
    public static function taskNotFound($taskId = null)
    {
        $message = $taskId ? "Task with ID {$taskId} not found" : "Task not found";
        return new self($message, 404);
    }

    /**
     * Access denied
     */
    public static function accessDenied($taskId = null)
    {
        $message = $taskId ? "Access denied to task {$taskId}" : "Access denied";
        return new self($message, 403);
    }

    /**
     * Validation error
     */
    public static function validationError($message = "Validation failed")
    {
        return new self($message, 422);
    }

    /**
     * Unauthorized
     */
    public static function unauthorized($message = "Unauthorized access")
    {
        return new self($message, 401);
    }

    /**
     * Forbidden
     */
    public static function forbidden($message = "Forbidden access")
    {
        return new self($message, 403);
    }

    /**
     * Internal server error
     */
    public static function internalError($message = "Internal server error")
    {
        return new self($message, 500);
    }
}
