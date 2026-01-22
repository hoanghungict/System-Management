<?php

declare(strict_types=1);

namespace Modules\Auth\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportLecturerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:10240', // 10MB
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Vui lòng chọn file Excel để import',
            'file.file' => 'File không hợp lệ',
            'file.mimes' => 'File phải là định dạng Excel (.xlsx, .xls)',
            'file.max' => 'File không được vượt quá 10MB',
        ];
    }
}
