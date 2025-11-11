<?php

namespace Modules\Auth\app\Http\Requests\AuthUserRequest;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
        return [
            'username' => 'required|string|max:255',
            'password' => 'required|string|min:6',
            'user_type' => 'required|in:student,lecturer'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'username.required' => 'Tên đăng nhập là bắt buộc',
            'username.string' => 'Tên đăng nhập phải là chuỗi',
            'username.max' => 'Tên đăng nhập không được vượt quá 255 ký tự',
            'password.required' => 'Mật khẩu là bắt buộc',
            'password.string' => 'Mật khẩu phải là chuỗi',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự',
            'user_type.required' => 'Loại người dùng là bắt buộc',
            'user_type.in' => 'Loại người dùng không hợp lệ'
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

