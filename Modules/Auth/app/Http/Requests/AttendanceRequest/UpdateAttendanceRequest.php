<?php

namespace Modules\Auth\app\Http\Requests\AttendanceRequest;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation cho điểm danh
 */
class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|exists:student,id',
            'status' => 'required|in:present,absent,late,excused',
            'note' => 'nullable|string|max:500',
            'minutes_late' => 'nullable|integer|min:0',
            'check_in_time' => 'nullable|date_format:H:i',
            'excuse_reason' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'ID sinh viên là bắt buộc',
            'student_id.exists' => 'Sinh viên không tồn tại',
            'status.required' => 'Trạng thái điểm danh là bắt buộc',
            'status.in' => 'Trạng thái điểm danh không hợp lệ',
        ];
    }
}
