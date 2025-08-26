<?php

namespace Modules\Auth\app\Http\Requests\ClassRequest;

use Illuminate\Foundation\Http\FormRequest;

class CreateClassRequest extends FormRequest
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
        return [
            'class_name' => 'required|string|max:255',
            'class_code' => 'required|string|max:50|unique:class,class_code',
            'faculty_id' => 'required|exists:department,id',
            'lecturer_id' => 'nullable|exists:lecturer,id',
            'school_year' => 'required|string|max:20'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'class_name.required' => 'Tên lớp học là bắt buộc',
            'class_name.max' => 'Tên lớp học không được vượt quá 255 ký tự',
            'class_code.required' => 'Mã lớp học là bắt buộc',
            'class_code.max' => 'Mã lớp học không được vượt quá 50 ký tự',
            'class_code.unique' => 'Mã lớp học đã tồn tại',
            'faculty_id.required' => 'Khoa là bắt buộc',
            'faculty_id.exists' => 'Khoa không tồn tại',
            'lecturer_id.exists' => 'Giảng viên không tồn tại',
            'school_year.required' => 'Năm học là bắt buộc',
            'school_year.max' => 'Năm học không được vượt quá 20 ký tự'
        ];
    }
}
