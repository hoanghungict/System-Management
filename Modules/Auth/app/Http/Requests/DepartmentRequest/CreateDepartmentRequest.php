<?php

namespace Modules\Auth\app\Http\Requests\DepartmentRequest;

use Illuminate\Foundation\Http\FormRequest;

class CreateDepartmentRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:school,faculty,department',
            'parent_id' => 'nullable|exists:department,id'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên department là bắt buộc',
            'name.max' => 'Tên department không được vượt quá 255 ký tự',
            'type.required' => 'Loại department là bắt buộc',
            'type.in' => 'Loại department không hợp lệ',
            'parent_id.exists' => 'Department cha không tồn tại'
        ];
    }
}
