<?php

namespace Modules\Auth\app\Http\Requests\AuthUserRequest;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
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
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|different:current_password',
            'new_password_confirmation' => 'required|string|same:new_password'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Mật khẩu hiện tại là bắt buộc',
            'current_password.string' => 'Mật khẩu hiện tại phải là chuỗi',
            'new_password.required' => 'Mật khẩu mới là bắt buộc',
            'new_password.string' => 'Mật khẩu mới phải là chuỗi',
            'new_password.min' => 'Mật khẩu mới phải có ít nhất 6 ký tự',
            'new_password.different' => 'Mật khẩu mới phải khác mật khẩu hiện tại',
            'new_password_confirmation.required' => 'Xác nhận mật khẩu mới là bắt buộc',
            'new_password_confirmation.string' => 'Xác nhận mật khẩu mới phải là chuỗi',
            'new_password_confirmation.same' => 'Xác nhận mật khẩu mới không khớp'
        ];
    }
}

