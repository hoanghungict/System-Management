<?php

namespace Modules\Auth\app\Http\Requests\AttendanceRequest;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation cho đăng ký môn học
 */
class EnrollStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|exists:student,id',
            'note' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'ID sinh viên là bắt buộc',
            'student_id.exists' => 'Sinh viên không tồn tại',
        ];
    }
}
