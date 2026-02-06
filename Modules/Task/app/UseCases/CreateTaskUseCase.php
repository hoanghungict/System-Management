<?php

namespace Modules\Task\app\UseCases;

use Modules\Task\app\Services\Interfaces\TaskServiceInterface;
use Modules\Task\app\DTOs\TaskDTO;
use Modules\Task\app\Models\Task;
use Modules\Task\app\Exceptions\TaskException;
use Modules\Task\app\Jobs\SendEmailJob;
use Modules\Task\app\DTOs\EmailReportDTO;
use Illuminate\Support\Facades\Log;

/**
 * Use Case: Tạo Task mới
 * 
 * Tuân thủ Clean Architecture: Use Case chứa business logic cụ thể
 * Tách biệt khỏi Controller và Service
 */
class CreateTaskUseCase
{
    public function __construct(
        private TaskServiceInterface $taskService
    ) {}

    /**
     * Thực hiện tạo task mới
     * 
     * @param array $data Dữ liệu task
     * @return Task Task đã được tạo
     * @throws \Exception Nếu có lỗi
     */
    public function execute(array $data): Task
    {
        try {
            // Validate business rules
            $this->validateBusinessRules($data);

            // Tạo DTO
            $taskDTO = TaskDTO::forCreate($data);

            // Tạo user context từ data
            $userContext = (object) [
                'id' => $data['creator_id'] ?? 1,
                'user_type' => $data['creator_type'] ?? 'lecturer'
            ];

            // Tạo task thông qua service với user context
            $task = $this->taskService->createTask($taskDTO->toArray(), $userContext);

            // Load receivers để lấy email
            $task->load('receivers');

            // Dispatch email job cho receivers
            $this->dispatchTaskNotificationEmail($task);

            // Log success
            /* Log::info('Task created successfully via UseCase', [
                'task_id' => $task->id,
                'creator_id' => $task->creator_id,
                'receivers_count' => $task->receivers->count()
            ]); */

            return $task;
        } catch (\Exception $e) {
            Log::error('Error creating task via UseCase: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate business rules
     * 
     * @param array $data Dữ liệu cần validate
     * @throws TaskException Nếu vi phạm business rules
     */
    private function validateBusinessRules(array $data): void
    {
        // Kiểm tra deadline không được trong quá khứ
        if (isset($data['deadline'])) {
            $deadline = \Carbon\Carbon::parse($data['deadline']);
            if ($deadline->isPast()) {
                throw TaskException::businessRuleViolation(
                    'Deadline cannot be in the past',
                    ['deadline' => $data['deadline']]
                );
            }
        }

        // ✅ Kiểm tra due_date không được trong quá khứ
        if (isset($data['due_date'])) {
            $dueDate = \Carbon\Carbon::parse($data['due_date']);
            if ($dueDate->isPast()) {
                throw TaskException::businessRuleViolation(
                    'Due date cannot be in the past',
                    ['due_date' => $data['due_date']]
                );
            }
        }

        // Kiểm tra ít nhất 1 receiver
        if (empty($data['receivers'])) {
            throw TaskException::businessRuleViolation(
                'At least one receiver is required',
                ['receivers' => $data['receivers'] ?? []]
            );
        }

        // Kiểm tra creator phải là lecturer hoặc student
        // Admin thực chất là lecturer với is_admin: true
        if (!in_array($data['creator_type'], ['lecturer', 'student'])) {
            throw TaskException::businessRuleViolation(
                'Creator type must be lecturer or student',
                ['creator_type' => $data['creator_type']]
            );
        }
    }

    /**
     * Dispatch email notification cho receivers của task
     * 
     * @param Task $task Task đã được tạo
     * @return void
     */
    private function dispatchTaskNotificationEmail(Task $task): void
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
            $emailContent = $this->generateTaskNotificationTemplate($task);

            // Tạo EmailReportDTO
            $emailDTO = new EmailReportDTO(
                recipients: $recipientEmails,
                subject: "📋 Task Mới: {$task->title}",
                content: $emailContent,
                reportData: [
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'task_description' => $task->description,
                    'deadline' => $task->deadline,
                    'creator_name' => $this->getCreatorName($task),
                    'created_at' => $task->created_at->format('Y-m-d H:i:s'),
                    'notification_type' => 'task_created'
                ]
            );

            // Dispatch email job
            SendEmailJob::dispatch($emailDTO)->onQueue('emails');

            /* Log::info('Task notification email dispatched', [
                'task_id' => $task->id,
                'receivers_count' => $task->receivers->count(),
                'recipients' => $recipientEmails
            ]); */
        } catch (\Exception $e) {
            Log::error('Failed to dispatch task notification email', [
                'task_id' => $task->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Lấy danh sách email của receivers từ database
     * 
     * @param Task $task
     * @return array
     */
    private function getReceiverEmails(Task $task): array
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
     * 
     * @param int $receiverId
     * @param string $receiverType
     * @return string|null
     */
    private function getEmailByReceiverType(int $receiverId, string $receiverType): ?string
    {
        try {
            return match ($receiverType) {
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
     * Tạo email template cho task notification
     * 
     * @param Task $task
     * @return string
     */
    private function generateTaskNotificationTemplate(Task $task): string
    {
        $creatorName = $this->getCreatorName($task);
        $deadline = $task->deadline ? \Carbon\Carbon::parse($task->deadline)->format('d/m/Y H:i') : 'Chưa xác định';

        return "
📋 <strong>BẠN CÓ TASK MỚI ĐƯỢC GIAO!</strong>

<strong>Tiêu đề:</strong> {$task->title}

<strong>Mô tả:</strong>
{$task->description}

<strong>Người giao:</strong> {$creatorName}

<strong>Hạn hoàn thành:</strong> {$deadline}

<strong>Ngày tạo:</strong> {$task->created_at->format('d/m/Y H:i')}

---
<em>Vui lòng đăng nhập vào hệ thống để xem chi tiết và cập nhật tiến độ task.</em>

<strong>Hệ thống Quản lý Task</strong>
        ";
    }

    /**
     * Lấy tên của người tạo task
     * 
     * @param Task $task
     * @return string
     */
    private function getCreatorName(Task $task): string
    {
        try {
            return match ($task->creator_type) {
                'student' => \Illuminate\Support\Facades\DB::table('student')
                    ->where('id', $task->creator_id)
                    ->value('name') ?? 'Sinh viên',
                'lecturer' => \Illuminate\Support\Facades\DB::table('lecturer')
                    ->where('id', $task->creator_id)
                    ->value('name') ?? 'Giảng viên',
                'admin' => \Illuminate\Support\Facades\DB::table('users')
                    ->where('id', $task->creator_id)
                    ->value('name') ?? 'Quản trị viên',
                default => 'Hệ thống'
            };
        } catch (\Exception $e) {
            Log::error('Failed to get creator name', [
                'creator_id' => $task->creator_id,
                'creator_type' => $task->creator_type,
                'error' => $e->getMessage()
            ]);
            return 'Hệ thống';
        }
    }
}
