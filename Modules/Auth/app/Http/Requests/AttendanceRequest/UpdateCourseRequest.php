<?php

namespace Modules\Auth\app\Http\Requests\AttendanceRequest;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation cho cập nhật Course
 */
class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Thông tin cơ bản
            'code' => 'sometimes|string|max:50',
            'name' => 'sometimes|string|max:255',
            'credits' => 'sometimes|integer|min:1|max:10',
            'description' => 'nullable|string',
            
            // Liên kết
            'lecturer_id' => 'nullable|exists:lecturer,id',
            'department_id' => 'nullable|exists:department,id',
            
            // Thời khóa biểu
            'schedule_days' => 'sometimes|array|min:1',
            'schedule_days.*' => 'integer|between:2,8',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i',
            'room' => 'nullable|string|max:50',
            
            // Cấu hình điểm danh
            'max_absences' => 'sometimes|integer|min:0|max:20',
            'absence_warning' => 'sometimes|integer|min:0',
            'late_threshold_minutes' => 'sometimes|integer|min:0|max:60',
            
            // Thời gian học
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
            
            // Trạng thái
            'status' => 'sometimes|in:draft,active,completed,cancelled',
        ];
    }

    public function messages(): array
    {
        return [
            'lecturer_id.exists' => 'Giảng viên không tồn tại',
            'schedule_days.array' => 'Lịch học phải là mảng',
            'schedule_days.*.between' => 'Ngày học phải từ 2 (Thứ 2) đến 8 (CN)',
            'start_time.date_format' => 'Giờ bắt đầu không đúng định dạng (HH:mm)',
            'end_time.date_format' => 'Giờ kết thúc không đúng định dạng (HH:mm)',
            'status.in' => 'Trạng thái không hợp lệ',
        ];
    }
}
