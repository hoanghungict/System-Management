<?php

namespace Modules\Notifications\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetUserNotificationsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Internal API, authentication được handle bởi middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|min:1',
            'user_type' => 'required|string|in:student,lecturer,admin',
            'limit' => 'integer|min:1|max:100',
            'offset' => 'integer|min:0'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'User ID là bắt buộc',
            'user_id.integer' => 'User ID phải là số nguyên',
            'user_id.min' => 'User ID phải lớn hơn 0',
            'user_type.required' => 'User type là bắt buộc',
            'user_type.in' => 'User type phải là student, lecturer hoặc admin',
            'limit.integer' => 'Limit phải là số nguyên',
            'limit.min' => 'Limit phải lớn hơn 0',
            'limit.max' => 'Limit không được vượt quá 100',
            'offset.integer' => 'Offset phải là số nguyên',
            'offset.min' => 'Offset phải lớn hơn hoặc bằng 0'
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'user_id' => 'ID người dùng',
            'user_type' => 'loại người dùng',
            'limit' => 'giới hạn',
            'offset' => 'vị trí bắt đầu'
        ];
    }
}
