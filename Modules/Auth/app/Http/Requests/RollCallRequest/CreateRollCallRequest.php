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
        return [
            'class_id' => [
                'required',
                'integer',
                'exists:class,id'
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
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'class_id.required' => 'Vui lòng chọn lớp học.',
            'class_id.exists' => 'Lớp học không tồn tại.',
            'title.required' => 'Vui lòng nhập tiêu đề buổi điểm danh.',
            'title.max' => 'Tiêu đề không được vượt quá 255 ký tự.',
            'description.max' => 'Mô tả không được vượt quá 1000 ký tự.',
            'date.required' => 'Vui lòng chọn ngày điểm danh.',
            'date.after_or_equal' => 'Ngày điểm danh phải từ hôm nay trở đi.',
            'created_by.required' => 'Thiếu thông tin người tạo.',
            'created_by.exists' => 'Người tạo không tồn tại.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'class_id' => 'lớp học',
            'title' => 'tiêu đề',
            'description' => 'mô tả',
            'date' => 'ngày điểm danh',
            'created_by' => 'người tạo'
        ];
    }
}
