<?php

namespace Modules\Auth\app\Http\Requests\AttendanceRequest;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation cho đổi lịch buổi học
 */
class RescheduleSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_date' => 'required|date|after_or_equal:today',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'room' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'session_date.required' => 'Ngày học mới là bắt buộc',
            'session_date.date' => 'Ngày học không hợp lệ',
            'session_date.after_or_equal' => 'Ngày học phải từ hôm nay trở đi',
            'start_time.date_format' => 'Giờ bắt đầu không đúng định dạng (HH:mm)',
            'end_time.date_format' => 'Giờ kết thúc không đúng định dạng (HH:mm)',
        ];
    }
}
