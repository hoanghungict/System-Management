<?php

namespace Modules\Auth\app\Http\Requests\RollCallRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUpdateRollCallStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'student_statuses' => [
                'required',
                'array',
                'min:1'
            ],
            'student_statuses.*.student_id' => [
                'required',
                'integer',
                'exists:students,id'
            ],
            'student_statuses.*.status' => [
                'required',
                'string',
                Rule::in(['Có Mặt', 'Vắng Mặt', 'Có Phép', 'Muộn'])
            ],
            'student_statuses.*.note' => [
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
            'student_statuses.required' => 'Vui lòng cung cấp danh sách trạng thái sinh viên.',
            'student_statuses.array' => 'Dữ liệu trạng thái sinh viên phải là mảng.',
            'student_statuses.min' => 'Phải có ít nhất 1 sinh viên để cập nhật.',
            'student_statuses.*.student_id.required' => 'Mỗi sinh viên phải có ID.',
            'student_statuses.*.student_id.exists' => 'Có sinh viên không tồn tại.',
            'student_statuses.*.status.required' => 'Mỗi sinh viên phải có trạng thái điểm danh.',
            'student_statuses.*.status.in' => 'Có trạng thái điểm danh không hợp lệ.',
            'student_statuses.*.note.max' => 'Ghi chú không được vượt quá 500 ký tự.',
            'student_statuses.*.note.string' => 'Ghi chú phải là chuỗi ký tự.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'student_statuses' => 'danh sách trạng thái sinh viên',
            'student_statuses.*.student_id' => 'ID sinh viên',
            'student_statuses.*.status' => 'trạng thái điểm danh',
            'student_statuses.*.note' => 'ghi chú',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Chuyển đổi dữ liệu từ frontend nếu cần
        if ($this->has('student_statuses')) {
            $studentStatuses = [];
            foreach ($this->student_statuses as $key => $data) {
                $studentStatuses[] = [
                    'student_id' => $data['student_id'] ?? null,
                    'status' => $data['status'] ?? null,
                    'note' => $data['note'] ?? null
                ];
            }
            $this->merge(['student_statuses' => $studentStatuses]);
        }
    }
}
