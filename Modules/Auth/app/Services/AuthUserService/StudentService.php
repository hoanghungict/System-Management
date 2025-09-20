<?php

namespace Modules\Auth\app\Services\AuthUserService;

use Modules\Auth\app\Repositories\Interfaces\AuthRepositoryInterface;
use Modules\Auth\app\Models\Student;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Modules\Notifications\app\Services\KafkaService\KafkaProducerService;

class StudentService
{
    protected $authRepository;
    protected $kafkaProducer;
    public function __construct(AuthRepositoryInterface $authRepository, KafkaProducerService $kafkaProducer)
    {
        $this->authRepository = $authRepository;
        $this->kafkaProducer = $kafkaProducer;
    }

    /**
     * Lấy tất cả sinh viên
     */
    public function getAllStudents()
    {
        return Cache::remember('students:all', 1800, function() {
            return Student::with('account', 'classroom')->get();
        });
    }
    
    /**
     * Lấy sinh viên theo ID
     */
    public function getStudentById(int $id)
    {
        return Cache::remember("students:{$id}", 1800, function() use ($id) {
            return Student::with('account', 'classroom')->find($id);
        });
    }
    
    /**
     * Tạo sinh viên mới và tự động tạo tài khoản
     */
    public function createStudentWithAccount(array $studentData): Student
    {
        // Tạo sinh viên mới
        $student = Student::create($studentData);
        
        // Tự động tạo tài khoản
        $this->createStudentAccount($student);
        
        // Xóa cache students
        $this->clearStudentsCache();
        
        return $student;
    }
    
    /**
     * Tự động tạo tài khoản cho sinh viên
     */
    private function createStudentAccount(Student $student): void
    {
        $username = $this->generateUsername($student->student_code);
        $password = $this->generateDefaultPassword();
        
        $this->authRepository->createStudentAccount([
            'username' => $username,
            'password' => $password,
            'student_id' => $student->id
        ]);
        $dataStudent = Student::find($student->id);
        $this->kafkaProducer->send('student.registered', [
            'user_id' => $student->id,
            'name' => $dataStudent->full_name ?? "Unknown",
            'user_name' =>$dataStudent->full_name ?? "Unknown",
            'password' => $password
        ]);
        // Gửi notification thông báo tài khoản mới
        // $this->sendRegistrationNotification($student, $username, $password);
    }
    
    /**
     * Tạo username từ mã sinh viên
     */
    private function generateUsername(string $studentCode): string
    {
        return 'sv_' . $studentCode;
    }
    
    /**
     * Tạo mật khẩu mặc định
     */
    private function generateDefaultPassword(): string
    {
        // Mật khẩu mặc định: ngày sinh (YYYYMMDD)
        return '123456';
    }
    
    /**
     * Cập nhật thông tin sinh viên
     */
    public function updateStudent(Student $student, array $data): Student
    {
        $student->update($data);
        
        // Xóa cache students
        $this->clearStudentsCache();
        
        return $student;
    }
    
    /**
     * Xóa sinh viên và tài khoản liên quan
     */
    public function deleteStudent(Student $student): bool
    {
        // Xóa tài khoản trước
        if ($student->account) {
            $student->account->delete();
        }
        
        // Xóa sinh viên
        $deleted = $student->delete();
        
        if ($deleted) {
            // Xóa cache students
            $this->clearStudentsCache();
        }
        
        return $deleted;
    }
    
    /**
     * Gửi notification thông báo tài khoản mới
     */
    private function sendRegistrationNotification(Student $student, string $username, string $password): void
    {
        try {
            // Gọi notification service để gửi thông báo
            if (class_exists('\Modules\Notifications\app\Services\NotificationService\NotificationService')) {
                $notificationService = app('\Modules\Notifications\app\Services\NotificationService\NotificationService');
                
                $notificationService->sendNotification(
                    'student_account_created',
                    [['user_id' => $student->id, 'user_type' => 'student']],
                    [
                        'user_name' => $student->full_name ?? $student->student_code,
                        'username' => $username,
                        'password' => $password,
                        'user_email' => $student->email ?? 'no-email@example.com'
                    ]
                );
                
                Log::info('Notification sent for new student account', [
                    'student_id' => $student->id,
                    'username' => $username
                ]);
            } else {
                Log::warning('Notification service not available');
            }
        } catch (\Exception $e) {
            Log::error('Failed to send registration notification', [
                'student_id' => $student->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Xóa tất cả cache students
     */
    private function clearStudentsCache(): void
    {
        Cache::forget('students:all');

        // Xóa cache cho GetStudentByClassId
        $classIds = Student::distinct()->pluck('class_id')->filter();
        foreach ($classIds as $classId) {
            Cache::forget("students:class:{$classId}");
        }

        // Xóa cache individual students
        $students = Student::pluck('id');
        foreach ($students as $id) {
            Cache::forget("students:{$id}");
        }
    }

    /**
     * Xóa cache danh sách sinh viên theo lớp
     */
    private function clearStudentsByClassCache(int $classId): void
    {
        Cache::forget("students:class:{$classId}");
    }

    /**
     * Lấy sinh viên theo ID lớp
     */
    public function getStudentByClassId(int $classId)
    {
        return Cache::remember("students:class:{$classId}", 1800, function () use ($classId) {
            return Student::where('class_id', $classId)->get();
        });
    }
}
