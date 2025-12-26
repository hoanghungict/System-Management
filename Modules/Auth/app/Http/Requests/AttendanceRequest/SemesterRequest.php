<?php

namespace Modules\Auth\app\Http\Requests\AttendanceRequest;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation cho tạo/cập nhật Semester
 */
class SemesterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $semesterId = $this->route('id');
        
        $rules = [
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:semesters,code',
            'academic_year' => 'required|string|max:20',
            'semester_type' => 'required|in:1,2,3',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ];

        // Nếu là update, bỏ qua unique check cho code hiện tại
        if ($semesterId) {
            $rules['code'] = 'required|string|max:20|unique:semesters,code,' . $semesterId;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên học kỳ là bắt buộc',
            'code.required' => 'Mã học kỳ là bắt buộc',
            'code.unique' => 'Mã học kỳ đã tồn tại',
            'academic_year.required' => 'Năm học là bắt buộc',
            'semester_type.required' => 'Loại học kỳ là bắt buộc',
            'semester_type.in' => 'Loại học kỳ không hợp lệ',
            'start_date.required' => 'Ngày bắt đầu là bắt buộc',
            'start_date.date' => 'Ngày bắt đầu không hợp lệ',
            'end_date.required' => 'Ngày kết thúc là bắt buộc',
            'end_date.date' => 'Ngày kết thúc không hợp lệ',
            'end_date.after' => 'Ngày kết thúc phải sau ngày bắt đầu',
        ];
    }
}
