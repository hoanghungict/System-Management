<?php

namespace Modules\Auth\app\Http\Requests\AttendanceRequest;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation cho tạo Course
 */
class CreateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Thông tin cơ bản
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'credits' => 'integer|min:1|max:10',
            'description' => 'nullable|string',
            
            // Liên kết
            'semester_id' => 'required|exists:semesters,id',
            'lecturer_id' => 'nullable|exists:lecturer,id',
            'department_id' => 'nullable|exists:department,id',
            
            // Thời khóa biểu
            'schedule_days' => 'required|array|min:1',
            'schedule_days.*' => 'integer|between:2,8', // 2=Thứ 2, 8=CN
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:50',
            
            // Cấu hình điểm danh
            'max_absences' => 'integer|min:0|max:20',
            'absence_warning' => 'integer|min:0',
            'late_threshold_minutes' => 'integer|min:0|max:60',
            
            // Thời gian học
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            
            // Tùy chọn
            'generate_sessions' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Mã môn học là bắt buộc',
            'name.required' => 'Tên môn học là bắt buộc',
            'semester_id.required' => 'Học kỳ là bắt buộc',
            'semester_id.exists' => 'Học kỳ không tồn tại',
            'lecturer_id.exists' => 'Giảng viên không tồn tại',
            'schedule_days.required' => 'Lịch học là bắt buộc',
            'schedule_days.array' => 'Lịch học phải là mảng',
            'schedule_days.*.between' => 'Ngày học phải từ 2 (Thứ 2) đến 8 (CN)',
            'start_time.required' => 'Giờ bắt đầu là bắt buộc',
            'start_time.date_format' => 'Giờ bắt đầu không đúng định dạng (HH:mm)',
            'end_time.required' => 'Giờ kết thúc là bắt buộc',
            'end_time.after' => 'Giờ kết thúc phải sau giờ bắt đầu',
            'start_date.required' => 'Ngày bắt đầu là bắt buộc',
            'end_date.required' => 'Ngày kết thúc là bắt buộc',
            'end_date.after' => 'Ngày kết thúc phải sau ngày bắt đầu',
        ];
    }

    /**
     * Chuẩn bị dữ liệu trước khi validate
     */
    protected function prepareForValidation(): void
    {
        // Đảm bảo schedule_days là array of integers
        if ($this->has('schedule_days') && is_string($this->schedule_days)) {
            $this->merge([
                'schedule_days' => json_decode($this->schedule_days, true)
            ]);
        }
    }
}
