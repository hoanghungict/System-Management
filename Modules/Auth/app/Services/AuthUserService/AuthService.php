<?php

namespace Modules\Auth\app\Services\AuthUserService;

use Modules\Auth\app\Repositories\Interfaces\AuthRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthService
{
    protected $authRepository;

    public function __construct(AuthRepositoryInterface $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    /**
     * Đăng nhập sinh viên
     */
    public function loginStudent(string $username, string $password)
    {
        $studentAccount = $this->authRepository->findStudentAccountByUsername($username);
        
        if (!$studentAccount || !Hash::check($password, $studentAccount->password)) {
            return null;
        }
        
        $student = $studentAccount->student;
        $token = $this->generateJWTToken($student, 'student');
        $student->token = $token;
        
        return $student;
    }
    
    /**
     * Đăng nhập giảng viên
     */
    public function loginLecturer(string $username, string $password)
    {
        $lecturerAccount = $this->authRepository->findLecturerAccountByUsername($username);
        
        if (!$lecturerAccount || !Hash::check($password, $lecturerAccount->password)) {
            return null;
        }
        
        $lecturer = $lecturerAccount->lecturer;
        $token = $this->generateJWTToken($lecturer, 'lecturer');
        $lecturer->token = $token;
        
        return $lecturer;
    }
    
    /**
     * Tạo JWT token
     */
    private function generateJWTToken($user, string $userType): string
    {
        $payload = [
            'sub' => $user->id,
            'user_type' => $userType,
            'username' => $user->account->username ?? null,
            'email' => $user->email ?? null,
            'full_name' => $user->full_name ?? null,
            'iat' => time(),
            'exp' => time() + (config('jwt.ttl', 60) * 60), // Sử dụng config TTL
        ];

        $secret = config('jwt.secret');
        $algo = config('jwt.algorithm', 'HS256');

        if (!$secret) {
            throw new \Exception('JWT secret is not configured');
        }

        return JWT::encode($payload, $secret, $algo);
    }
    
    /**
     * Xác thực JWT token
     */
    public function validateToken(string $token)
    {
        try {
            $secret = config('jwt.secret');
            $algo = config('jwt.algorithm', 'HS256');
            
            if (!$secret) {
                return null;
            }
            
            $decoded = JWT::decode($token, new Key($secret, $algo));
            return $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Làm mới JWT token
     */
    public function refreshToken(string $token): string
    {
        try {
            $payload = $this->validateToken($token);
            
            if (!$payload) {
                throw new \Exception('Invalid token');
            }
            
            // Tạo token mới với thời gian gia hạn
            $newPayload = [
                'sub' => $payload->sub,
                'user_type' => $payload->user_type,
                'username' => $payload->username,
                'email' => $payload->email ?? null,
                'full_name' => $payload->full_name ?? null,
                'iat' => time(),
                'exp' => time() + (config('jwt.ttl', 60) * 60),
            ];
            
            $secret = config('jwt.secret');
            $algo = config('jwt.algorithm', 'HS256');
            
            return JWT::encode($newPayload, $secret, $algo);
        } catch (\Exception $e) {
            throw new \Exception('Không thể làm mới token: ' . $e->getMessage());
        }
    }
    
    /**
     * Vô hiệu hóa JWT token (thêm vào blacklist nếu cần)
     */
    public function invalidateToken(string $token): bool
    {
        // Với Firebase JWT, chúng ta không thể vô hiệu hóa token
        // Nhưng có thể thêm vào blacklist trong database nếu cần
        // Hoặc client sẽ tự xóa token
        return true;
    }
}
