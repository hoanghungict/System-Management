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
}
