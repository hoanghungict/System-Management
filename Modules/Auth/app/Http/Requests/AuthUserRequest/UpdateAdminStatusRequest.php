<?php

namespace Modules\Auth\app\Http\Requests\AuthUserRequest;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminStatusRequest extends FormRequest
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
            'is_admin' => 'required|boolean'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'is_admin.required' => 'Trạng thái admin là bắt buộc',
            'is_admin.boolean' => 'Trạng thái admin phải là true hoặc false'
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
