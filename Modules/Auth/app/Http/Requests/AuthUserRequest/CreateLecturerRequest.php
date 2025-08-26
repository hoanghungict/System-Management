<?php

namespace Modules\Auth\app\Http\Requests\AuthUserRequest;

use Illuminate\Foundation\Http\FormRequest;

class CreateLecturerRequest extends FormRequest
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
            'full_name' => 'required|string|max:255',
            'gender' => 'required|in:male,female,other',
            'address' => 'required|string',
            'email' => 'required|email|unique:lecturer,email',
            'phone' => 'required|string|max:20',
            'lecturer_code' => 'required|string|unique:lecturer,lecturer_code',
            'unit_id' => 'required|exists:unit,id'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Họ tên là bắt buộc',
            'full_name.string' => 'Họ tên phải là chuỗi',
            'full_name.max' => 'Họ tên không được vượt quá 255 ký tự',
            'gender.required' => 'Giới tính là bắt buộc',
            'gender.in' => 'Giới tính phải là male, female hoặc other',
            'address.required' => 'Địa chỉ là bắt buộc',
            'address.string' => 'Địa chỉ phải là chuỗi',
            'email.required' => 'Email là bắt buộc',
            'email.email' => 'Email không đúng định dạng',
            'email.unique' => 'Email đã tồn tại',
            'phone.required' => 'Số điện thoại là bắt buộc',
            'phone.string' => 'Số điện thoại phải là chuỗi',
            'phone.max' => 'Số điện thoại không được vượt quá 20 ký tự',
            'lecturer_code.required' => 'Mã giảng viên là bắt buộc',
            'lecturer_code.string' => 'Mã giảng viên phải là chuỗi',
            'lecturer_code.unique' => 'Mã giảng viên đã tồn tại',
            'unit_id.required' => 'Đơn vị là bắt buộc',
            'unit_id.exists' => 'Đơn vị không tồn tại'
        ];
    }
}
