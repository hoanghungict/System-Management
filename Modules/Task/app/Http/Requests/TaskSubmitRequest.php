<?php

namespace Modules\Task\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * TaskSubmitRequest - Request validation cho API submit task chung
 * 
 * Validation rules cho cả sinh viên và giảng viên submit task
 */
class TaskSubmitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization được xử lý ở middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'submission_type' => [
                'required',
                'string',
                Rule::in(['task_completion', 'assignment_submission', 'project_submission', 'report_submission'])
            ],
            'submission_content' => [
                'required',
                'string',
                'min:10',
                'max:5000'
            ],
            'submission_notes' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'submission_files' => [
                'nullable',
                'array',
                'max:10'
            ],
            'submission_files.*.name' => [
                'required_with:submission_files',
                'string',
                'max:255'
            ],
            'submission_files.*.path' => [
                'required_with:submission_files',
                'string',
                'max:500'
            ],
            'submission_files.*.size' => [
                'nullable',
                'integer',
                'min:1',
                'max:10485760' // 10MB max
            ],
            'submission_files.*.type' => [
                'nullable',
                'string',
                'max:100'
            ],
            'completion_status' => [
                'nullable',
                'string',
                Rule::in(['completed', 'partially_completed', 'needs_revision'])
            ],
            'grade' => [
                'nullable',
                'numeric',
                'min:0',
                'max:10'
            ],
            'feedback' => [
                'nullable',
                'string',
                'max:2000'
            ],
            'estimated_hours' => [
                'nullable',
                'numeric',
                'min:0.1',
                'max:1000'
            ],
            'difficulty_level' => [
                'nullable',
                'string',
                Rule::in(['easy', 'medium', 'hard', 'expert'])
            ],
            'tags' => [
                'nullable',
                'array',
                'max:10'
            ],
            'tags.*' => [
                'string',
                'max:50'
            ],
            'is_late' => [
                'nullable',
                'boolean'
            ],
            'late_reason' => [
                'nullable',
                'string',
                'max:500'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'submission_type.required' => 'Loại submission là bắt buộc',
            'submission_type.in' => 'Loại submission không hợp lệ',
            'submission_content.required' => 'Nội dung submission là bắt buộc',
            'submission_content.min' => 'Nội dung submission phải có ít nhất 10 ký tự',
            'submission_content.max' => 'Nội dung submission không được vượt quá 5000 ký tự',
            'submission_notes.max' => 'Ghi chú không được vượt quá 1000 ký tự',
            'submission_files.max' => 'Không được upload quá 10 files',
            'submission_files.*.name.required_with' => 'Tên file là bắt buộc',
            'submission_files.*.name.max' => 'Tên file không được vượt quá 255 ký tự',
            'submission_files.*.path.required_with' => 'Đường dẫn file là bắt buộc',
            'submission_files.*.size.max' => 'Kích thước file không được vượt quá 10MB',
            'completion_status.in' => 'Trạng thái hoàn thành không hợp lệ',
            'grade.min' => 'Điểm không được nhỏ hơn 0',
            'grade.max' => 'Điểm không được lớn hơn 10',
            'feedback.max' => 'Feedback không được vượt quá 2000 ký tự',
            'estimated_hours.min' => 'Số giờ ước tính phải lớn hơn 0',
            'estimated_hours.max' => 'Số giờ ước tính không được vượt quá 1000',
            'difficulty_level.in' => 'Mức độ khó không hợp lệ',
            'tags.max' => 'Không được có quá 10 tags',
            'tags.*.max' => 'Mỗi tag không được vượt quá 50 ký tự',
            'late_reason.max' => 'Lý do trễ hạn không được vượt quá 500 ký tự'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'submission_type' => 'loại submission',
            'submission_content' => 'nội dung submission',
            'submission_notes' => 'ghi chú',
            'submission_files' => 'files đính kèm',
            'completion_status' => 'trạng thái hoàn thành',
            'grade' => 'điểm số',
            'feedback' => 'phản hồi',
            'estimated_hours' => 'số giờ ước tính',
            'difficulty_level' => 'mức độ khó',
            'tags' => 'tags',
            'is_late' => 'trễ hạn',
            'late_reason' => 'lý do trễ hạn'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Auto-detect submission type based on user type
        $userType = $this->attributes->get('jwt_user_type', 'student');

        if (!$this->has('submission_type')) {
            $this->merge([
                'submission_type' => $userType === 'lecturer' ? 'task_completion' : 'assignment_submission'
            ]);
        }

        // Auto-detect if submission is late
        if (!$this->has('is_late') && $this->has('task_id')) {
            // This would need to be implemented based on task deadline
            $this->merge(['is_late' => false]);
        }
    }
}
