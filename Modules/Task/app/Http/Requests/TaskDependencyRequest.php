<?php

namespace Modules\Task\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskDependencyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'predecessor_task_id' => [
                'required',
                'integer',
                'exists:task,id'
            ],
            'successor_task_id' => [
                'required',
                'integer',
                'exists:task,id'
            ],
            'dependency_type' => [
                'required',
                'string',
                Rule::in(['finish_to_start', 'start_to_start', 'finish_to_finish', 'start_to_finish'])
            ],
            'lag_days' => [
                'integer',
                'min:0',
                'max:365'
            ],
            'metadata' => [
                'nullable',
                'array'
            ],
            'created_by' => [
                'nullable',
                'integer',
                'exists:users,id'
            ],
            'created_by_type' => [
                'nullable',
                'string',
                Rule::in(['admin', 'lecturer', 'student'])
            ]
        ];

        // Custom validation cho update
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['predecessor_task_id'][0] = 'sometimes';
            $rules['successor_task_id'][0] = 'sometimes';
            $rules['dependency_type'][0] = 'sometimes';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'predecessor_task_id.required' => 'Predecessor task ID is required',
            'predecessor_task_id.exists' => 'Predecessor task not found',
            'successor_task_id.required' => 'Successor task ID is required',
            'successor_task_id.exists' => 'Successor task not found',
            'dependency_type.required' => 'Dependency type is required',
            'dependency_type.in' => 'Invalid dependency type',
            'lag_days.integer' => 'Lag days must be an integer',
            'lag_days.min' => 'Lag days cannot be negative',
            'lag_days.max' => 'Lag days cannot exceed 365 days',
            'metadata.array' => 'Metadata must be an array',
            'created_by.exists' => 'Created by user not found',
            'created_by_type.in' => 'Invalid created by type'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'predecessor_task_id' => 'predecessor task',
            'successor_task_id' => 'successor task',
            'dependency_type' => 'dependency type',
            'lag_days' => 'lag days',
            'metadata' => 'metadata',
            'created_by' => 'created by',
            'created_by_type' => 'created by type'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Kiểm tra không thể phụ thuộc vào chính mình
            if ($this->predecessor_task_id && $this->successor_task_id) {
                if ($this->predecessor_task_id === $this->successor_task_id) {
                    $validator->errors()->add('predecessor_task_id', 'Task cannot depend on itself');
                }
            }
        });
    }
}