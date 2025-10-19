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
            'roll_call_details' => [
                'required',
                'array',
                'min:1'
            ],
            'roll_call_details.*.student_id' => [
                'required',
                'integer',
                'exists:student,id'
            ],
            'roll_call_details.*.status' => [
                'required',
                'string',
                Rule::in(['Có Mặt', 'Vắng Mặt', 'Có Phép', 'Muộn'])
            ],
            'roll_call_details.*.note' => [
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
            'roll_call_details.required' => 'Vui lòng cung cấp danh sách điểm danh.',
            'roll_call_details.array' => 'Danh sách điểm danh phải là mảng.',
            'roll_call_details.min' => 'Danh sách điểm danh không được rỗng.',
            'roll_call_details.*.student_id.required' => 'Vui lòng chọn sinh viên.',
            'roll_call_details.*.student_id.exists' => 'Sinh viên không tồn tại.',
            'roll_call_details.*.status.required' => 'Vui lòng chọn trạng thái điểm danh.',
            'roll_call_details.*.status.in' => 'Trạng thái điểm danh không hợp lệ.',
            'roll_call_details.*.note.max' => 'Ghi chú không được vượt quá 500 ký tự.',
            'roll_call_details.*.note.string' => 'Ghi chú phải là chuỗi ký tự.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'roll_call_details' => 'danh sách điểm danh',
            'roll_call_details.*.student_id' => 'sinh viên',
            'roll_call_details.*.status' => 'trạng thái điểm danh',
            'roll_call_details.*.note' => 'ghi chú',
        ];
    }
}
