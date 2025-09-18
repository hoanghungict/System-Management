<?php

namespace Modules\Task\app\Student\Exceptions;

use Exception;

/**
 * Student Task Exception
 * 
 * Custom exception cho Student Task operations
 */
class StudentTaskException extends Exception
{
    protected $statusCode;
    protected $context;

    public function __construct($message = '', $statusCode = 500, $context = [], $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->statusCode = $statusCode;
        $this->context = $context;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function toArray()
    {
        return [
            'message' => $this->getMessage(),
            'status_code' => $this->statusCode,
            'context' => $this->context,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }

    /**
     * Task not found
     */
    public static function taskNotFound($taskId, $context = [])
    {
        return new self("Task with ID {$taskId} not found", 404, $context);
    }

    /**
     * Task not assigned to student
     */
    public static function taskNotAssigned($taskId, $studentId, $context = [])
    {
        return new self("Task {$taskId} is not assigned to student {$studentId}", 403, $context);
    }

    /**
     * Task already submitted
     */
    public static function taskAlreadySubmitted($taskId, $context = [])
    {
        return new self("Task {$taskId} has already been submitted", 400, $context);
    }

    /**
     * Task deadline passed
     */
    public static function deadlinePassed($taskId, $context = [])
    {
        return new self("Task {$taskId} deadline has passed", 400, $context);
    }

    /**
     * Submission not found
     */
    public static function submissionNotFound($taskId, $studentId, $context = [])
    {
        return new self("Submission for task {$taskId} by student {$studentId} not found", 404, $context);
    }

    /**
     * File upload failed
     */
    public static function fileUploadFailed($filename, $context = [])
    {
        return new self("Failed to upload file: {$filename}", 500, $context);
    }

    /**
     * File not found
     */
    public static function fileNotFound($fileId, $context = [])
    {
        return new self("File with ID {$fileId} not found", 404, $context);
    }

    /**
     * Access denied
     */
    public static function accessDenied($action, $context = [])
    {
        return new self("Access denied for action: {$action}", 403, $context);
    }

    /**
     * Validation failed
     */
    public static function validationFailed($errors, $context = [])
    {
        $message = is_array($errors) ? implode(', ', $errors) : $errors;
        return new self("Validation failed: {$message}", 400, $context);
    }
}
