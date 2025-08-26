<?php

namespace Modules\Auth\app\Http\Resources\AuthUserResources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\app\Models\Student;
use Modules\Auth\app\Models\Lecturer;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $user = $this->resource;
        
        $baseData = [
            'id' => $user->id,
            'email' => $user->email,
            'full_name' => $user->full_name ?? null,
            'phone' => $user->phone ?? null,
            'address' => $user->address ?? null,
            'user_type' => $this->getUserType($user),
            'account' => [
                'username' => $user->account->username ?? null,
                'is_admin' => $user->account->is_admin ?? false
            ]
        ];

        // Thêm thông tin cụ thể cho từng loại user
        if ($user instanceof Student) {
            $baseData['student_info'] = [
                'student_code' => $user->student_code,
                'birth_date' => $user->birth_date,
                'gender' => $user->gender,
                'class' => $user->classroom ? [
                    'id' => $user->classroom->id,
                    'class_name' => $user->classroom->class_name,
                    'class_code' => $user->classroom->class_code
                ] : null
            ];
        } elseif ($user instanceof Lecturer) {
            $baseData['lecturer_info'] = [
                'lecturer_code' => $user->lecturer_code,
                'gender' => $user->gender,
                'unit' => $user->unit ? [
                    'id' => $user->unit->id,
                    'name' => $user->unit->name,
                    'type' => $user->unit->type
                ] : null
            ];
        }

        return $baseData;
    }
    
    /**
     * Xác định loại người dùng
     */
    private function getUserType($user): string
    {
        // Kiểm tra dựa trên thuộc tính có sẵn
        if (isset($user->student_code)) {
            return 'student';
        }
        
        if (isset($user->lecturer_code)) {
            return 'lecturer';
        }
        
        // Kiểm tra dựa trên instance
        if ($user instanceof Student) {
            return 'student';
        }
        
        if ($user instanceof Lecturer) {
            return 'lecturer';
        }
        
        return 'unknown';
    }
}

