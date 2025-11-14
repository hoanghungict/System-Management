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
            'name' => 'required|unique:department,name|string|max:255',
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
            'name.unique' => 'Tên department đã tồn tại',
            'name.max' => 'Tên department không được vượt quá 255 ký tự',
            'type.required' => 'Loại department là bắt buộc',
            'type.in' => 'Loại department không hợp lệ',
            'parent_id.exists' => 'Department cha không tồn tại'
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
