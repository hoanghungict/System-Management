<?php

namespace Modules\Task\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request class xử lý validation cho Task
 * 
 * Class này chứa tất cả logic validation cho các operations liên quan đến Task
 * Tuân thủ Clean Architecture: chỉ xử lý validation, không chứa business logic
 */
class TaskRequest extends FormRequest
{
    /**
     * Kiểm tra quyền truy cập của user
     * 
     * @return bool True nếu user có quyền thực hiện request này
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Định nghĩa các validation rules cho request
     * 
     * @return array Mảng chứa các validation rules
     */
    public function rules(): array
    {
        $rules = [];

        // Quy tắc cơ bản cho create/update tasks
        if ($this->isMethod('POST') || $this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'due_date' => 'nullable|date|after_or_equal:today', // ✅ Thêm validation cho due_date
                'deadline' => 'nullable|date|after:now',
                'status' => 'nullable|in:pending,in_progress,completed,overdue',
                'priority' => 'nullable|in:low,medium,high',
                'receivers' => 'required|array|min:1',
                'receivers.*.receiver_id' => 'required|integer|min:0', // ✅ Cho phép 0 cho all_students/all_lecturers toàn hệ thống
                'receivers.*.receiver_type' => 'required|in:student,lecturer,class,all_students,all_lecturers',
                'creator_id' => 'required|integer',
                'creator_type' => 'required|in:lecturer,student',
                'assigned_to' => 'nullable|string|max:255', // ✅ Thêm validation cho assigned_to
                'assigned_to_id' => 'nullable|integer|min:0', // ✅ Thêm validation cho assigned_to_id
                'include_new_students' => 'nullable|boolean',
                'include_new_lecturers' => 'nullable|boolean',
            ];
        }

        // Quy tắc cho lấy tasks theo receiver
        if ($this->routeIs('tasks.by-receiver')) {
            $rules = [
                'receiver_id' => 'required|integer',
                'receiver_type' => 'required|in:student,lecturer,class,all_students,all_lecturers'
            ];
        }

        // Quy tắc cho lấy tasks theo creator
        if ($this->routeIs('tasks.by-creator')) {
            $rules = [
                'creator_id' => 'required|integer',
                'creator_type' => 'required|in:lecturer,student'
            ];
        }

        return $rules;
    }

    /**
     * Định nghĩa các message validation tùy chỉnh
     * 
     * @return array Mảng chứa các custom messages
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Task title is required.',
            'title.max' => 'Task title cannot exceed 255 characters.',
            'due_date.date' => 'Due date must be a valid date.',
            'due_date.after_or_equal' => 'Due date must be today or in the future.',
            'deadline.date' => 'Deadline must be a valid date.',
            'deadline.after' => 'Deadline must be in the future.',
            'status.in' => 'Status must be pending, in_progress, completed, or overdue.',
            'priority.in' => 'Priority must be low, medium, or high.',
            'receivers.required' => 'At least one receiver is required.',
            'receivers.array' => 'Receivers must be an array.',
            'receivers.min' => 'At least one receiver is required.',
            'receivers.*.receiver_id.required' => 'Receiver ID is required.',
            'receivers.*.receiver_id.integer' => 'Receiver ID must be an integer.',
            'receivers.*.receiver_id.min' => 'Receiver ID must be 0 or greater.',
            'receivers.*.receiver_type.required' => 'Receiver type is required.',
            'receivers.*.receiver_type.in' => 'Receiver type must be student, lecturer, class, all_students, or all_lecturers.',
            'creator_id.required' => 'Creator ID is required.',
            'creator_id.integer' => 'Creator ID must be an integer.',
            'creator_type.required' => 'Creator type is required.',
            'creator_type.in' => 'Creator type must be either lecturer or student.',
            'assigned_to.string' => 'Assigned to must be a string.',
            'assigned_to.max' => 'Assigned to cannot exceed 255 characters.',
            'assigned_to_id.integer' => 'Assigned to ID must be an integer.',
            'assigned_to_id.min' => 'Assigned to ID must be 0 or greater.',
        ];
    }

    /**
     * Lấy dữ liệu đã được validate với xử lý bổ sung (nếu có)
     * 
     * @param string|null $key Key cụ thể cần lấy
     * @param mixed $default Giá trị mặc định nếu không tìm thấy
     * @return array|mixed Dữ liệu đã được validate và xử lý
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // Thiết lập giá trị mặc định nếu không có
        if (!isset($validated['creator_id'])) {
            $validated['creator_id'] = $this->getUserIdFromJwt(); // ✅ Fix: sử dụng JWT attributes
        }

        if (!isset($validated['creator_type'])) {
            $validated['creator_type'] = 'lecturer'; // Default value
        }

        return $validated;
    }

    /**
     * Lấy user ID từ JWT attributes
     * 
     * @return int User ID hoặc 1 nếu không có
     */
    private function getUserIdFromJwt(): int
    {
        return $this->attributes->get('jwt_user_id', 1);
    }

    /**
     * Xử lý validation errors để trả về JSON response
     * 
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'error_code' => 'VALIDATION_ERROR'
            ], 422)
        );
    }
}
