<?php

namespace Modules\Notifications\app\Handlers\Quiz;

use Illuminate\Support\Facades\Log;
use Modules\Notifications\app\Handlers\Contracts\NotificationEventHandler;
use Modules\Notifications\app\Handlers\HandleUtil\ensureString;
use Modules\Notifications\app\Services\NotificationService\NotificationService;

class QuizResultHandle implements NotificationEventHandler
{
    protected $notificationService;
    protected $ensureString;

    public function __construct(NotificationService $notificationService, EnsureString $ensureString)
    {
        $this->notificationService = $notificationService;
        $this->ensureString = $ensureString;
    }

    public function handle(string $channel, array $data): void
    {
        if (!isset($data['user_id'])) {
            Log::warning('QuizResultHandle: Thiếu user_id trong dữ liệu', ['data' => $data]);
            return;
        }

        $userId = (int) $data['user_id'];
        $userType = $data['user_type'] ?? 'student';

        Log::info('QuizResultHandle: Xử lý thông báo kết quả quiz', [
            'user_id' => $userId,
            'user_type' => $userType,
            'title' => $data['title'] ?? 'Quiz'
        ]);

        try {
            $templateData = $this->prepareTemplateData($data);

            Log::info('QuizResultHandle: Dữ liệu template từ Kafka message', [
                'template_data' => $templateData,
                'user_id' => $userId
            ]);

            $result = $this->notificationService->sendNotification(
                'quiz_result', // template key
                [
                    [
                        'user_id' => $userId,
                        'user_type' => $userType
                    ]
                ],
                $templateData,
                [
                    'priority' => 'medium',
                    'sender_id' => $data['sender_id'] ?? null,
                    'sender_type' => $data['sender_type'] ?? 'system'
                ]
            );
        } catch (\Exception $e) {
            Log::error('QuizResultHandle: Lỗi xảy ra', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function prepareTemplateData(array $data): array
    {
        $studentName = $this->ensureString->ensureString($data['student_name'] ?? 'Học sinh');
        $titleQuiz = $this->ensureString->ensureString($data['title_quiz'] ?? 'Quiz');
        $score = $this->ensureString->ensureString($data['score'] ?? '0');
        $quizUrl = $this->ensureString->ensureString($data['quiz_url'] ?? 'https://example.com/quiz');
        $date = $this->ensureString->ensureString($data['date'] ?? date('d/m/Y'));

        return [
            // matching template variables
            'student_name' => $studentName,
            'title_quiz' => $titleQuiz,
            'score' => $score,
            'quiz_url' => $quizUrl,
            'date' => $date,

            // hệ thống
            'year' => date('Y'),
            'original_data' => $this->ensureString->ensureString(json_encode($data, JSON_UNESCAPED_UNICODE))
        ];
    }
}
