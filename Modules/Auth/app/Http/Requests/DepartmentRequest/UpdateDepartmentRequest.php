<?php

namespace Modules\Auth\app\Http\Requests\DepartmentRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
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
        $departmentId = $this->route('id');
        
        return [
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:school,faculty,department',
            'parent_id' => [
                'sometimes',
                'nullable',
                'exists:department,id',
                Rule::notIn([$departmentId]) // Không thể set parent_id = chính mình
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.max' => 'Tên department không được vượt quá 255 ký tự',
            'type.in' => 'Loại department không hợp lệ',
            'parent_id.exists' => 'Department cha không tồn tại',
            'parent_id.not_in' => 'Không thể set department cha là chính mình'
        ];
    }
}
