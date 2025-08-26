<?php

namespace Modules\Notifications\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkAsReadRequest extends FormRequest
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
            'user_notification_id' => 'required|integer|min:1'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_notification_id.required' => 'User notification ID là bắt buộc',
            'user_notification_id.integer' => 'User notification ID phải là số nguyên',
            'user_notification_id.min' => 'User notification ID phải lớn hơn 0'
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'user_notification_id' => 'ID thông báo người dùng'
        ];
    }
}
