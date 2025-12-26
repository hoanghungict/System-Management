<?php

namespace Modules\Auth\app\Http\Requests\AttendanceRequest;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation cho điểm danh hàng loạt
 */
class BulkUpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attendances' => 'required|array|min:1',
            'attendances.*.student_id' => 'required|exists:student,id',
            'attendances.*.status' => 'required|in:present,absent,late,excused',
            'attendances.*.note' => 'nullable|string|max:500',
            'attendances.*.minutes_late' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'attendances.required' => 'Danh sách điểm danh là bắt buộc',
            'attendances.array' => 'Danh sách điểm danh phải là mảng',
            'attendances.min' => 'Phải có ít nhất 1 sinh viên',
            'attendances.*.student_id.required' => 'ID sinh viên là bắt buộc',
            'attendances.*.student_id.exists' => 'Sinh viên không tồn tại',
            'attendances.*.status.required' => 'Trạng thái điểm danh là bắt buộc',
            'attendances.*.status.in' => 'Trạng thái điểm danh không hợp lệ',
        ];
    }
}
