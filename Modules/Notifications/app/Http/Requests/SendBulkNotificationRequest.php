<?php

namespace Modules\Notifications\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendBulkNotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public API, không cần authentication
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'template' => 'required|string|max:255',
            'recipients' => 'required|array|min:1|max:1000', // Giới hạn 1000 recipients
            'recipients.*.user_id' => 'required|integer|min:1',
            'recipients.*.user_type' => 'required|string|in:student,lecturer,admin',
            'recipients.*.channels' => 'array|in:email,push,sms,in_app',
            'data' => 'array',
            'options' => 'array',
            'options.priority' => 'string|in:low,medium,high,critical',
            'options.sender_id' => 'integer|min:1',
            'options.sender_type' => 'string|in:student,lecturer,admin'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'template.required' => 'Template name là bắt buộc',
            'template.string' => 'Template name phải là chuỗi',
            'recipients.required' => 'Danh sách người nhận là bắt buộc',
            'recipients.array' => 'Recipients phải là mảng',
            'recipients.min' => 'Phải có ít nhất 1 người nhận',
            'recipients.max' => 'Không được vượt quá 1000 người nhận',
            'recipients.*.user_id.required' => 'User ID là bắt buộc',
            'recipients.*.user_id.integer' => 'User ID phải là số nguyên',
            'recipients.*.user_type.required' => 'User type là bắt buộc',
            'recipients.*.user_type.in' => 'User type phải là student, lecturer hoặc admin',
            'recipients.*.channels.array' => 'Channels phải là mảng',
            'recipients.*.channels.in' => 'Channels phải là email, push, sms hoặc in_app',
            'data.array' => 'Data phải là mảng',
            'options.array' => 'Options phải là mảng',
            'options.priority.in' => 'Priority phải là low, medium, high hoặc critical',
            'options.sender_id.integer' => 'Sender ID phải là số nguyên',
            'options.sender_type.in' => 'Sender type phải là student, lecturer hoặc admin'
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'template' => 'tên template',
            'recipients' => 'danh sách người nhận',
            'data' => 'dữ liệu',
            'options' => 'tùy chọn'
        ];
    }
}
