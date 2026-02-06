<?php

namespace Modules\Task\app\Lecturer\UseCases;

use Modules\Task\app\Lecturer\Repositories\LecturerTaskRepository;
use Modules\Task\app\Lecturer\DTOs\CreateTaskDTO;
use Modules\Task\app\Lecturer\Exceptions\LecturerTaskException;
use Modules\Task\app\Jobs\SendEmailJob;
use Modules\Task\app\DTOs\EmailReportDTO;
use Illuminate\Support\Facades\Log;

/**
 * Update Task Use Case
 */
class UpdateTaskUseCase
{
    protected $lecturerTaskRepository;

    public function __construct(LecturerTaskRepository $lecturerTaskRepository)
    {
        $this->lecturerTaskRepository = $lecturerTaskRepository;
    }

    public function execute($taskId, $data, $lecturerId, $userType)
    {
        try {
            // Lấy task cũ để so sánh
            $oldTask = $this->lecturerTaskRepository->findById($taskId);
            
            // Update task
            $task = $this->lecturerTaskRepository->update($taskId, $data, $lecturerId, $userType);
            
            // Load receivers để gửi email
            $task->load('receivers');
            
            // Gửi email thông báo update
            $this->dispatchTaskUpdateEmail($task, $oldTask);
            
            /* Log::info('Task updated successfully via UseCase', [
                'task_id' => $task->id,
                'title' => $task->title,
                'updated_by' => $lecturerId,
                'receivers_count' => $task->receivers->count()
            ]); */
            
            return $task;
        } catch (\Exception $e) {
            Log::error('Failed to update task via UseCase: ' . $e->getMessage());
            throw new LecturerTaskException('Failed to update task: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Gửi email thông báo task đã được cập nhật
     */
    private function dispatchTaskUpdateEmail($task, $oldTask): void
    {
        try {
            // Lấy danh sách email của receivers
            $recipientEmails = $this->getReceiverEmails($task);
            
            if (empty($recipientEmails)) {
                Log::warning('No valid email addresses found for task receivers', [
                    'task_id' => $task->id,
                    'receivers_count' => $task->receivers->count()
                ]);
                return;
            }

            // Tạo email template
            $emailContent = $this->generateTaskUpdateTemplate($task, $oldTask);
            
            // Tạo EmailReportDTO
            $emailDTO = new EmailReportDTO(
                recipients: $recipientEmails,
                subject: "📝 Task Đã Cập Nhật: {$task->title}",
                content: $emailContent,
                reportData: [
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'task_description' => $task->description,
                    'deadline' => $task->deadline,
                    'updated_by' => $this->getLecturerName($task),
                    'updated_at' => $task->updated_at->format('Y-m-d H:i:s'),
                    'notification_type' => 'task_updated'
                ]
            );

            // Dispatch email job
            SendEmailJob::dispatch($emailDTO)->onQueue('emails');
            
            /* Log::info('Task update notification email dispatched', [
                'task_id' => $task->id,
                'recipients_count' => count($recipientEmails),
                'recipients' => $recipientEmails
            ]); */
            
        } catch (\Exception $e) {
            Log::error('Failed to dispatch task update notification email', [
                'task_id' => $task->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Lấy danh sách email của receivers
     */
    private function getReceiverEmails($task): array
    {
        $emails = [];
        
        foreach ($task->receivers as $receiver) {
            $email = $this->getEmailByReceiverType($receiver->receiver_id, $receiver->receiver_type);
            if ($email) {
                $emails[] = $email;
            }
        }
        
        return array_unique($emails);
    }
    
    /**
     * Lấy email dựa trên receiver type
     */
    private function getEmailByReceiverType(int $receiverId, string $receiverType): ?string
    {
        try {
            return match($receiverType) {
                'student' => \Illuminate\Support\Facades\DB::table('student')
                    ->where('id', $receiverId)
                    ->value('email'),
                'lecturer' => \Illuminate\Support\Facades\DB::table('lecturer')
                    ->where('id', $receiverId)
                    ->value('email'),
                'admin' => \Illuminate\Support\Facades\DB::table('users')
                    ->where('id', $receiverId)
                    ->value('email'),
                default => null
            };
        } catch (\Exception $e) {
            Log::error('Failed to get email for receiver', [
                'receiver_id' => $receiverId,
                'receiver_type' => $receiverType,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Tạo email template cho task update notification
     */
    private function generateTaskUpdateTemplate($task, $oldTask): string
    {
        $lecturerName = $this->getLecturerName($task);
        $deadline = $task->deadline ? \Carbon\Carbon::parse($task->deadline)->format('d/m/Y H:i') : 'Chưa xác định';
        
        return "
📝 <strong>TASK ĐÃ ĐƯỢC CẬP NHẬT!</strong>

<strong>Tiêu đề:</strong> {$task->title}

<strong>Mô tả:</strong>
{$task->description}

<strong>Người cập nhật:</strong> {$lecturerName}

<strong>Hạn hoàn thành:</strong> {$deadline}

<strong>Ngày cập nhật:</strong> {$task->updated_at->format('d/m/Y H:i')}

---
<em>Vui lòng đăng nhập vào hệ thống để xem chi tiết và cập nhật tiến độ task.</em>

<strong>Hệ thống Quản lý Task</strong>
        ";
    }
    
    /**
     * Lấy tên của lecturer
     */
    private function getLecturerName($task): string
    {
        try {
            return \Illuminate\Support\Facades\DB::table('lecturer')
                ->where('id', $task->creator_id)
                ->value('name') ?? 'Giảng viên';
        } catch (\Exception $e) {
            Log::error('Failed to get lecturer name', [
                'lecturer_id' => $task->creator_id,
                'error' => $e->getMessage()
            ]);
            return 'Giảng viên';
        }
    }
}
