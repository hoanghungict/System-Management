<?php

namespace Modules\Auth\app\Repositories\AuthUserRepository;

use Modules\Auth\app\Repositories\Interfaces\AuthRepositoryInterface;
use Modules\Auth\app\Models\StudentAccount;
use Modules\Auth\app\Models\LecturerAccount;
use Modules\Auth\app\Models\Student;
use Modules\Auth\app\Models\Lecturer;
use Illuminate\Support\Facades\Hash;

class AuthRepository implements AuthRepositoryInterface
{
    /**
     * Tìm tài khoản sinh viên theo username
     */
    public function findStudentAccountByUsername(string $username): ?StudentAccount
    {
        return StudentAccount::where('username', $username)->first();
    }
    
    /**
     * Tìm tài khoản giảng viên theo username
     */
    public function findLecturerAccountByUsername(string $username): ?LecturerAccount
    {
        return LecturerAccount::where('username', $username)->first();
    }
    
    /**
     * Tạo tài khoản sinh viên
     */
    public function createStudentAccount(array $data): StudentAccount
    {
        return StudentAccount::create([
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'student_id' => $data['student_id']
        ]);
    }
    
    /**
     * Tạo tài khoản giảng viên
     */
    public function createLecturerAccount(array $data): LecturerAccount
    {
        return LecturerAccount::create([
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'lecturer_id' => $data['lecturer_id'],
            'is_admin' => $data['is_admin'] ?? false
        ]);
    }
    
    /**
     * Cập nhật mật khẩu
     */
    public function updatePassword($account, string $newPassword): bool
    {
        return $account->update([
            'password' => Hash::make($newPassword)
        ]);
    }
    
    /**
     * Kiểm tra username đã tồn tại chưa
     */
    public function isUsernameExists(string $username): bool
    {
        return StudentAccount::where('username', $username)->exists() ||
               LecturerAccount::where('username', $username)->exists();
    }
    
    /**
     * Lấy thông tin sinh viên theo ID
     */
    public function findStudentById(int $id)
    {
        return Student::find($id);
    }
    
    /**
     * Lấy thông tin giảng viên theo ID
     */
    public function findLecturerById(int $id)
    {
        return Lecturer::find($id);
    }
}

