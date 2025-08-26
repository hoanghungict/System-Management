<?php

namespace Modules\Auth\app\Repositories\Interfaces;

use Modules\Auth\app\Models\StudentAccount;
use Modules\Auth\app\Models\LecturerAccount;

interface AuthRepositoryInterface
{
    /**
     * Tìm tài khoản sinh viên theo username
     */
    public function findStudentAccountByUsername(string $username): ?StudentAccount;
    
    /**
     * Tìm tài khoản giảng viên theo username
     */
    public function findLecturerAccountByUsername(string $username): ?LecturerAccount;
    
    /**
     * Tạo tài khoản sinh viên
     */
    public function createStudentAccount(array $data): StudentAccount;
    
    /**
     * Tạo tài khoản giảng viên
     */
    public function createLecturerAccount(array $data): LecturerAccount;
    
    /**
     * Cập nhật mật khẩu
     */
    public function updatePassword($account, string $newPassword): bool;
    
    /**
     * Kiểm tra username đã tồn tại chưa
     */
    public function isUsernameExists(string $username): bool;
    
    /**
     * Lấy thông tin sinh viên theo ID
     */
    public function findStudentById(int $id);
    
    /**
     * Lấy thông tin giảng viên theo ID
     */
    public function findLecturerById(int $id);
}
