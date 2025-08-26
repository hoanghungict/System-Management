<?php

namespace Modules\Auth\app\Http\Requests\AuthUserRequest;

use Illuminate\Foundation\Http\FormRequest;

class CreateStudentRequest extends FormRequest
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
            'birth_date' => 'required|date',
            'gender' => 'required|in:male,female,other',
            'address' => 'required|string',
            'email' => 'required|email|unique:student,email',
            'phone' => 'required|string|max:20',
            'student_code' => 'required|string|unique:student,student_code',
            'class_id' => 'required|exists:class,id'
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
            'birth_date.required' => 'Ngày sinh là bắt buộc',
            'birth_date.date' => 'Ngày sinh không đúng định dạng',
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
            'student_code.required' => 'Mã sinh viên là bắt buộc',
            'student_code.string' => 'Mã sinh viên phải là chuỗi',
            'student_code.unique' => 'Mã sinh viên đã tồn tại',
            'class_id.required' => 'Lớp là bắt buộc',
            'class_id.exists' => 'Lớp không tồn tại'
        ];
    }
}
