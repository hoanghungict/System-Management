<?php

namespace Modules\Auth\app\Services\AuthUserService;

use Modules\Auth\app\Repositories\Interfaces\AuthRepositoryInterface;
use Modules\Auth\app\Models\Lecturer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class LecturerService
{
    protected $authRepository;

    public function __construct(AuthRepositoryInterface $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    /**
     * Lấy tất cả giảng viên
     */
    public function getAllLecturers()
    {
        return Cache::remember('lecturers:all', 1800, function() {
            return Lecturer::with('account', 'department')->get();
        });
    }
    
    /**
     * Lấy giảng viên theo ID
     */
    public function getLecturerById(int $id)
    {
        return Cache::remember("lecturers:{$id}", 1800, function() use ($id) {
            return Lecturer::with('account', 'department')->find($id);
        });
    }
    
    /**
     * Tạo giảng viên mới và tự động tạo tài khoản
     */
    public function createLecturerWithAccount(array $lecturerData): Lecturer
    {
        // Tạo giảng viên mới
        $lecturer = Lecturer::create($lecturerData);
        
        // Tự động tạo tài khoản
        $this->createLecturerAccount($lecturer);
        
        // Xóa cache lecturers
        $this->clearLecturersCache();
        
        return $lecturer;
    }
    
    /**
     * Tự động tạo tài khoản cho giảng viên
     */
    private function createLecturerAccount(Lecturer $lecturer): void
    {
        $username = $this->generateUsername($lecturer->lecturer_code);
        $password = $this->generateDefaultPassword();
        
        $this->authRepository->createLecturerAccount([
            'username' => $username,
            'password' => $password,
            'lecturer_id' => $lecturer->id,
            'is_admin' => false // Mặc định không phải admin
        ]);
        
        // Gửi notification thông báo tài khoản mới
        $this->sendRegistrationNotification($lecturer, $username, $password);
    }
    
    /**
     * Tạo username từ mã giảng viên
     */
    private function generateUsername(string $lecturerCode): string
    {
        return 'gv_' . $lecturerCode;
    }
    
    /**
     * Tạo mật khẩu mặc định
     */
    private function generateDefaultPassword(): string
    {
        // Mật khẩu mặc định
        return '123456';
    }
    
    /**
     * Cập nhật thông tin giảng viên
     */
    public function updateLecturer(Lecturer $lecturer, array $data): Lecturer
    {
        $lecturer->update($data);
        
        // Xóa cache lecturers
        $this->clearLecturersCache();
        
        return $lecturer;
    }
    
    /**
     * Xóa giảng viên và tài khoản liên quan
     */
    public function deleteLecturer(Lecturer $lecturer): bool
    {
        // Xóa tài khoản trước
        if ($lecturer->account) {
            $lecturer->account->delete();
        }
        
        // Xóa giảng viên
        $deleted = $lecturer->delete();
        
        if ($deleted) {
            // Xóa cache lecturers
            $this->clearLecturersCache();
        }
        
        return $deleted;
    }
    
    /**
     * Cập nhật quyền admin cho giảng viên
     */
    public function updateAdminStatus(Lecturer $lecturer, bool $isAdmin): bool
    {
        if ($lecturer->account) {
            $updated = $lecturer->account->update(['is_admin' => $isAdmin]);
            
            if ($updated) {
                // Xóa cache lecturers
                $this->clearLecturersCache();
            }
            
            return $updated;
        }
        
        return false;
    }
    
    /**
     * Gửi notification thông báo tài khoản mới
     */
    private function sendRegistrationNotification(Lecturer $lecturer, string $username, string $password): void
    {
        try {
            // Gọi notification service để gửi thông báo
            if (class_exists('\Modules\Notifications\app\Services\NotificationService\NotificationService')) {
                $notificationService = app('\Modules\Notifications\app\Services\NotificationService\NotificationService');
                
                $notificationService->sendNotification(
                    'user_registered',
                    [['user_id' => $lecturer->id, 'user_type' => 'lecturer']],
                    [
                        'user_name' => $lecturer->full_name ?? $lecturer->lecturer_code,
                        'username' => $username,
                        'password' => $password,
                        'user_email' => $lecturer->email ?? 'no-email@example.com'
                    ]
                );
                
                Log::info('Notification sent for new lecturer account', [
                    'lecturer_id' => $lecturer->id,
                    'username' => $username
                ]);
            } else {
                Log::warning('Notification service not available');
            }
        } catch (\Exception $e) {
            Log::error('Failed to send registration notification', [
                'lecturer_id' => $lecturer->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Xóa tất cả cache lecturers
     */
    private function clearLecturersCache(): void
    {
        Cache::forget('lecturers:all');
        
        // Xóa cache individual lecturers
        $lecturers = Lecturer::pluck('id');
        foreach ($lecturers as $id) {
            Cache::forget("lecturers:{$id}");
        }
    }
}
