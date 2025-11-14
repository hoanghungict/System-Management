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
            'department_id' => 'required|exists:department,id',
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
            'department_id.required' => 'Bắt buộc chọn khoa/phòng ban',
            'department_id.exists' => 'Khoa/phòng ban không tồn tại',
            'lecturer_id.exists' => 'Giảng viên không tồn tại',
            'school_year.required' => 'Năm học là bắt buộc',
            'school_year.max' => 'Năm học không được vượt quá 20 ký tự'
        ];
    }

    /**
     * Xử lý validation errors để trả về JSON response
     * Đảm bảo API requests luôn trả về JSON, không redirect
     * 
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        // Format errors theo format Laravel validation
        $errors = [];
        foreach ($validator->errors()->messages() as $field => $messages) {
            $errors[$field] = $messages;
        }

        // Tạo message tổng hợp
        $message = 'Dữ liệu không hợp lệ';
        $errorCount = count($errors);
        if ($errorCount > 0) {
            $firstError = reset($errors);
            $message = $firstError[0];
            if ($errorCount > 1) {
                $message .= " (and " . ($errorCount - 1) . " more error)";
            }
        }

        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            response()->json([
                'message' => $message,
                'errors' => $errors
            ], 422)
        );
    }
}
