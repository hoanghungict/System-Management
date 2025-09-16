<?php

namespace Modules\Task\app\Student\DTOs;

/**
 * Student Profile DTO
 */
class StudentProfileDTO
{
    public $student_id;
    public $full_name;
    public $email;
    public $phone;
    public $address;
    public $class_id;
    public $student_code;
    public $date_of_birth;
    public $avatar;
    public $bio;

    public function __construct(array $data = [])
    {
        $this->student_id = $data['student_id'] ?? null;
        $this->full_name = $data['full_name'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->phone = $data['phone'] ?? null;
        $this->address = $data['address'] ?? null;
        $this->class_id = $data['class_id'] ?? null;
        $this->student_code = $data['student_code'] ?? null;
        $this->date_of_birth = $data['date_of_birth'] ?? null;
        $this->avatar = $data['avatar'] ?? null;
        $this->bio = $data['bio'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'student_id' => $this->student_id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'class_id' => $this->class_id,
            'student_code' => $this->student_code,
            'date_of_birth' => $this->date_of_birth,
            'avatar' => $this->avatar,
            'bio' => $this->bio,
        ];
    }

    public function validate(): array
    {
        $errors = [];

        // Chỉ validate email nếu có và phải đúng format
        if (!empty($this->email) && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        // Validate phone nếu có
        if (!empty($this->phone) && !preg_match('/^[0-9+\-\s()]+$/', $this->phone)) {
            $errors[] = 'Invalid phone format';
        }

        // Validate date of birth nếu có
        if (!empty($this->date_of_birth) && !strtotime($this->date_of_birth)) {
            $errors[] = 'Invalid date of birth format';
        }

        return $errors;
    }
}
