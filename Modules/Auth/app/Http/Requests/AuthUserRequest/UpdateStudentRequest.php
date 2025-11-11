<?php

namespace Modules\Auth\app\Http\Requests\AuthUserRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $studentId = $this->route('id');
        
        return [
            'full_name' => 'sometimes|string|max:255',
            'birth_date' => 'sometimes|date',
            'gender' => 'sometimes|in:male,female,other',
            'address' => 'sometimes|string',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('student', 'email')->ignore($studentId)
            ],
            'phone' => 'sometimes|string|max:20',
            'class_id' => 'sometimes|exists:class,id'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'full_name.string' => 'Họ tên phải là chuỗi',
            'full_name.max' => 'Họ tên không được vượt quá 255 ký tự',
            'birth_date.date' => 'Ngày sinh không đúng định dạng',
            'gender.in' => 'Giới tính phải là male, female hoặc other',
            'address.string' => 'Địa chỉ phải là chuỗi',
            'email.email' => 'Email không đúng định dạng',
            'email.unique' => 'Email đã tồn tại',
            'phone.string' => 'Số điện thoại phải là chuỗi',
            'phone.max' => 'Số điện thoại không được vượt quá 20 ký tự',
            'class_id.exists' => 'Lớp không tồn tại'
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
