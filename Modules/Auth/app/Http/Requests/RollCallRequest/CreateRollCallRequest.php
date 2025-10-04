<?php

namespace Modules\Auth\app\Http\Requests\RollCallRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRollCallRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Có thể thêm logic kiểm tra quyền ở đây
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'type' => [
                'required',
                'string',
                'in:class_based,manual'
            ],
            'title' => [
                'required',
                'string',
                'max:255'
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'date' => [
                'required',
                'date',
                'after_or_equal:today'
            ],
            'created_by' => [
                'required',
                'integer',
                'exists:lecturer,id'
            ]
        ];

        // Conditional validation based on type
        if ($this->input('type') === 'class_based') {
            $rules['class_id'] = [
                'required',
                'integer',
                'exists:class,id'
            ];
        } else if ($this->input('type') === 'manual') {
            $rules['class_id'] = ['nullable'];
            $rules['participants'] = [
                'required',
                'array',
                'min:1'
            ];
            $rules['participants.*'] = [
                'integer',
                'exists:student,id'
            ];
            $rules['expected_participants'] = [
                'nullable',
                'integer',
                'min:1'
            ];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Vui lòng chọn loại điểm danh.',
            'type.in' => 'Loại điểm danh không hợp lệ.',
            'class_id.required' => 'Vui lòng chọn lớp học.',
            'class_id.exists' => 'Lớp học không tồn tại.',
            'title.required' => 'Vui lòng nhập tiêu đề buổi điểm danh.',
            'title.max' => 'Tiêu đề không được vượt quá 255 ký tự.',
            'description.max' => 'Mô tả không được vượt quá 1000 ký tự.',
            'date.required' => 'Vui lòng chọn ngày điểm danh.',
            'date.after_or_equal' => 'Ngày điểm danh phải từ hôm nay trở đi.',
            'created_by.required' => 'Thiếu thông tin người tạo.',
            'created_by.exists' => 'Người tạo không tồn tại.',
            'participants.required' => 'Vui lòng chọn sinh viên tham gia.',
            'participants.array' => 'Danh sách sinh viên phải là mảng.',
            'participants.min' => 'Phải có ít nhất 1 sinh viên.',
            'participants.*.exists' => 'Có sinh viên không tồn tại.',
            'expected_participants.integer' => 'Số lượng sinh viên dự kiến phải là số nguyên.',
            'expected_participants.min' => 'Số lượng sinh viên dự kiến phải lớn hơn 0.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'type' => 'loại điểm danh',
            'class_id' => 'lớp học',
            'title' => 'tiêu đề',
            'description' => 'mô tả',
            'date' => 'ngày điểm danh',
            'created_by' => 'người tạo',
            'participants' => 'danh sách sinh viên',
            'expected_participants' => 'số lượng sinh viên dự kiến'
        ];
    }
}
