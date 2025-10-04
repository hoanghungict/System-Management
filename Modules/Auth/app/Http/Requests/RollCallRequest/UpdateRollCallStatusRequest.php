<?php

namespace Modules\Auth\app\Http\Requests\RollCallRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRollCallStatusRequest extends FormRequest
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
            'student_id' => [
                'required',
                'integer',
                'exists:student,id'
            ],
            'status' => [
                'required',
                'string',
                Rule::in(['Có Mặt', 'Vắng Mặt', 'Có Phép', 'Muộn'])
            ],
            'note' => [
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
            'student_id.required' => 'Vui lòng chọn sinh viên.',
            'student_id.exists' => 'Sinh viên không tồn tại.',
            'status.required' => 'Vui lòng chọn trạng thái điểm danh.',
            'status.in' => 'Trạng thái điểm danh không hợp lệ.',
            'note.max' => 'Ghi chú không được vượt quá 500 ký tự.',
            'note.string' => 'Ghi chú phải là chuỗi ký tự.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'student_id' => 'sinh viên',
            'status' => 'trạng thái điểm danh',
            'note' => 'ghi chú',
        ];
    }
}
