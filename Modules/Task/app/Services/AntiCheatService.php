<?php

namespace Modules\Task\app\Services;

use Illuminate\Support\Facades\Log;

/**
 * AntiCheatService
 * Quản lý và ghi log các vi phạm anti-cheat
 */
class AntiCheatService
{
    /**
     * Các loại vi phạm
     */
    public const VIOLATION_TAB_SWITCH = 'tab_switch';
    public const VIOLATION_FULLSCREEN_EXIT = 'fullscreen_exit';
    public const VIOLATION_COPY_ATTEMPT = 'copy_attempt';
    public const VIOLATION_PASTE_ATTEMPT = 'paste_attempt';
    public const VIOLATION_RIGHT_CLICK = 'right_click';
    public const VIOLATION_KEYBOARD_SHORTCUT = 'keyboard_shortcut';
    public const VIOLATION_WINDOW_BLUR = 'window_blur';

    /**
     * Mô tả các vi phạm bằng tiếng Việt
     */
    public const VIOLATION_DESCRIPTIONS = [
        self::VIOLATION_TAB_SWITCH => 'Chuyển tab trình duyệt',
        self::VIOLATION_FULLSCREEN_EXIT => 'Thoát chế độ toàn màn hình',
        self::VIOLATION_COPY_ATTEMPT => 'Cố gắng sao chép nội dung',
        self::VIOLATION_PASTE_ATTEMPT => 'Cố gắng dán nội dung',
        self::VIOLATION_RIGHT_CLICK => 'Click chuột phải',
        self::VIOLATION_KEYBOARD_SHORTCUT => 'Sử dụng phím tắt không cho phép',
        self::VIOLATION_WINDOW_BLUR => 'Rời khỏi cửa sổ thi',
    ];

    /**
     * Ghi log vi phạm vào submission
     * 
     * @param \Modules\Task\app\Models\ExamSubmission $submission
     * @param string $type Loại vi phạm
     * @param array|null $details Chi tiết bổ sung
     * @return void
     */
    public function logViolation($submission, string $type, ?array $details = null): void
    {
        $submission->logAntiCheatViolation($type, $details);
        
        // Refresh to get updated counts if necessary
        $submission->refresh();

        // Dispatch realtime event
        \Modules\Task\app\Events\AntiCheatViolationDetected::dispatch($submission, $type, $details ?? []);
        
        Log::warning("Anti-cheat violation detected", [
            'submission_id' => $submission->id,
            'student_id' => $submission->student_id,
            'exam_id' => $submission->exam_id,
            'type' => $type,
            'description' => self::VIOLATION_DESCRIPTIONS[$type] ?? $type,
            'details' => $details,
        ]);
    }

    /**
     * Lấy danh sách vi phạm của 1 submission
     * 
     * @param \Modules\Task\app\Models\ExamSubmission $submission
     * @return array
     */
    public function getViolations($submission): array
    {
        $violations = $submission->anti_cheat_violations ?? [];
        
        // Thêm mô tả tiếng Việt
        return array_map(function ($v) {
            $v['description'] = self::VIOLATION_DESCRIPTIONS[$v['type']] ?? $v['type'];
            return $v;
        }, $violations);
    }

    /**
     * Đếm số lần vi phạm theo loại
     * 
     * @param \Modules\Task\app\Models\ExamSubmission $submission
     * @return array
     */
    public function countViolationsByType($submission): array
    {
        $violations = $submission->anti_cheat_violations ?? [];
        $counts = [];

        foreach ($violations as $v) {
            $type = $v['type'];
            if (!isset($counts[$type])) {
                $counts[$type] = 0;
            }
            $counts[$type]++;
        }

        return $counts;
    }

    /**
     * Kiểm tra có quá nhiều vi phạm không
     * 
     * @param \Modules\Task\app\Models\ExamSubmission $submission
     * @param int $threshold Ngưỡng vi phạm
     * @return bool
     */
    public function hasExcessiveViolations($submission, int $threshold = 10): bool
    {
        $violations = $submission->anti_cheat_violations ?? [];
        return count($violations) >= $threshold;
    }

    /**
     * Tạo báo cáo vi phạm
     * 
     * @param \Modules\Task\app\Models\ExamSubmission $submission
     * @return array
     */
    public function generateReport($submission): array
    {
        $violations = $this->getViolations($submission);
        $counts = $this->countViolationsByType($submission);

        return [
            'total_violations' => count($violations),
            'violations_by_type' => $counts,
            'violations' => $violations,
            'is_suspicious' => $this->hasExcessiveViolations($submission),
        ];
    }
}
