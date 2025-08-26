<?php

namespace Modules\Auth\app\Http\Requests\ClassRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Sẽ được kiểm tra trong middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $classId = $this->route('id');
        
        return [
            'class_name' => 'sometimes|string|max:255',
            'class_code' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('class', 'class_code')->ignore($classId)
            ],
            'faculty_id' => 'sometimes|exists:department,id',
            'lecturer_id' => 'sometimes|nullable|exists:lecturer,id',
            'school_year' => 'sometimes|string|max:20'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'class_name.max' => 'Tên lớp học không được vượt quá 255 ký tự',
            'class_code.max' => 'Mã lớp học không được vượt quá 50 ký tự',
            'class_code.unique' => 'Mã lớp học đã tồn tại',
            'faculty_id.exists' => 'Khoa không tồn tại',
            'lecturer_id.exists' => 'Giảng viên không tồn tại',
            'school_year.max' => 'Năm học không được vượt quá 20 ký tự'
        ];
    }
}
