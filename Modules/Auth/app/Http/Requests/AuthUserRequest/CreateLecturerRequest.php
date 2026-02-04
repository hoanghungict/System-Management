<?php

namespace Modules\Auth\app\Http\Requests\AuthUserRequest;

use Illuminate\Foundation\Http\FormRequest;

class CreateLecturerRequest extends FormRequest
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
            'gender' => 'required|in:male,female,other',
            'address' => 'required|string',
            'email' => 'required|email|unique:lecturer,email',
            'phone' => 'required|string|max:20',
            'lecturer_code' => 'required|string|unique:lecturer,lecturer_code',
            'department_id' => 'required|exists:department,id',
            'birth_date' => 'nullable|date',
            'experience_number' => 'nullable|integer|min:0|max:50',
            'bang_cap' => 'nullable|string|max:255',
            'ngay_bat_dau_lam_viec' => 'nullable|date',
            'hinh_anh' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
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
            'lecturer_code.required' => 'Mã giảng viên là bắt buộc',
            'lecturer_code.string' => 'Mã giảng viên phải là chuỗi',
            'lecturer_code.unique' => 'Mã giảng viên đã tồn tại',
            'department_id.required' => 'Khoa/phòng ban là bắt buộc',
            'department_id.exists' => 'Khoa/phòng ban không tồn tại',
            'birth_date.date' => 'Ngày sinh không đúng định dạng',
            'experience_number.integer' => 'Số năm kinh nghiệm phải là số nguyên',
            'experience_number.min' => 'Số năm kinh nghiệm phải lớn hơn hoặc bằng 0',
            'experience_number.max' => 'Số năm kinh nghiệm không được vượt quá 50'
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
