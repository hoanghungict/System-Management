<?php

namespace Modules\Auth\app\Http\Requests\AttendanceRequest;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation cho đăng ký nhiều sinh viên
 */
class BulkEnrollStudentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:student,id',
        ];
    }

    public function messages(): array
    {
        return [
            'student_ids.required' => 'Danh sách sinh viên là bắt buộc',
            'student_ids.array' => 'Danh sách sinh viên phải là mảng',
            'student_ids.min' => 'Phải có ít nhất 1 sinh viên',
            'student_ids.*.exists' => 'Sinh viên không tồn tại',
        ];
    }
}
