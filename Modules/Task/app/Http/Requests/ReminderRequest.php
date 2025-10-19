<?php

declare(strict_types=1);

namespace Modules\Task\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Reminder Request
 * 
 * Validates reminder creation and update requests
 */
class ReminderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'task_id' => ['required', 'integer', 'exists:task,id'],
            'reminder_type' => [
                'required',
                'string',
                Rule::in(['email', 'push', 'sms', 'in_app'])
            ],
            'reminder_time' => ['required', 'date', 'after:now'],
            'message' => ['nullable', 'string', 'max:1000'],
            'metadata' => ['nullable', 'array']
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'task_id.required' => 'Task ID is required',
            'task_id.exists' => 'Selected task does not exist',
            'reminder_type.required' => 'Reminder type is required',
            'reminder_type.in' => 'Invalid reminder type. Must be one of: email, push, sms, in_app',
            'reminder_time.required' => 'Reminder time is required',
            'reminder_time.date' => 'Reminder time must be a valid date',
            'reminder_time.after' => 'Reminder time must be in the future',
            'message.max' => 'Message must not exceed 1000 characters',
            'metadata.array' => 'Metadata must be an array'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'task_id' => 'task',
            'reminder_type' => 'reminder type',
            'reminder_time' => 'reminder time',
            'message' => 'message',
            'metadata' => 'metadata'
        ];
    }
}
