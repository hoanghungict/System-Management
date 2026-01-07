<?php

namespace Modules\Auth\app\Http\Requests\AuthUserRequest;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => 'nullable|string|min:6',
            'is_admin' => 'nullable|boolean', // Chỉ dành cho giảng viên
        ];
    }

    public function messages(): array
    {
        return [
            'password.string' => 'Mật khẩu phải là chuỗi ký tự',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự',
            'is_admin.boolean' => 'Trạng thái admin phải là true hoặc false',
        ];
    }
}
