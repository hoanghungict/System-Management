<?php

namespace Modules\Task\app\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;
use Modules\Task\app\Models\QuestionImportLog;
use Modules\Task\app\Models\Question;
use Modules\Auth\app\Models\AuditLog;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Job Import câu hỏi từ Excel
 */
class ImportQuestionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $importLogId;
    protected $filePath;
    protected $lecturerId;
    protected $assignmentId;

    public function __construct(int $importLogId, string $filePath)
    {
        $this->importLogId = $importLogId;
        $this->filePath = $filePath;
    }

    public function handle(): void
    {
        $importLog = QuestionImportLog::find($this->importLogId);

        if (!$importLog) {
            Log::error("Import log not found: {$this->importLogId}");
            return;
        }

        $this->lecturerId = $importLog->imported_by;
        $this->assignmentId = $importLog->assignment_id;

        try {
            // Check file existence
            if (!file_exists($this->filePath)) {
                throw new Exception("File not found at processing path");
            }

            \Illuminate\Support\Facades\Log::info("Job debugging: File exists at " . $this->filePath);
            
            // Parse Excel - read only the first sheet (index 0)
            $allSheets = Excel::toCollection(null, $this->filePath);
            
            // Get only the first sheet
            if ($allSheets->isEmpty()) {
                throw new Exception("File Excel trống hoặc không có sheet nào");
            }
            
            $firstSheet = $allSheets->first();
            Log::info("Reading first sheet with " . $firstSheet->count() . " rows");
            
            // Process using our custom importer
            $sheetImport = new QuestionsSheetImport($this->assignmentId, $importLog);
            $sheetImport->processCollection($firstSheet);
            
            // Delete file after successful import
            if (file_exists($this->filePath)) {
                unlink($this->filePath);
            }

        } catch (Exception $e) {
            $importLog->markFailed(0, "System Error: " . $e->getMessage());
            AuditLog::log('import_system_failed', $this->lecturerId, 'Assignment', $this->assignmentId, ['error' => $e->getMessage()]);
            
            if (file_exists($this->filePath)) {
                unlink($this->filePath);
            }
            
            // Re-throw to let controller know about the error
            throw $e;
        }
    }
}

/**
 * Import handler - đọc sheet đầu tiên của file Excel
 * Xử lý collection thủ công để đảm bảo đọc đúng sheet
 */
class QuestionsSheetImport
{
    protected $assignmentId;
    protected $assignmentType;
    protected $importLog;
    protected $processedCount = 0;

    public function __construct($assignmentId, $importLog)
    {
        $this->assignmentId = $assignmentId;
        $this->importLog = $importLog;
        
        // Load assignment type for validation
        $assignment = \Modules\Task\app\Models\Assignment::find($assignmentId);
        $this->assignmentType = $assignment ? $assignment->type : 'mixed';
    }

    /**
     * Get allowed question types based on assignment type
     */
    protected function getAllowedQuestionTypes(): array
    {
        switch ($this->assignmentType) {
            case 'quiz':
                return ['multiple_choice', 'short_answer'];
            case 'essay':
                return ['essay'];
            case 'mixed':
            default:
                return ['multiple_choice', 'short_answer', 'essay'];
        }
    }

    /**
     * Get human-readable name for assignment type
     */
    protected function getAssignmentTypeName(): string
    {
        switch ($this->assignmentType) {
            case 'quiz': return 'Trắc nghiệm (Quiz)';
            case 'essay': return 'Tự luận (Essay)';
            case 'mixed': return 'Hỗn hợp (Mixed)';
            default: return $this->assignmentType;
        }
    }

    /**
     * Process collection from the first sheet
     * First row is treated as header, remaining rows as data
     */
    public function processCollection(Collection $rawRows)
    {
        Log::info("Start processing Excel collection. Total raw rows: " . $rawRows->count());
        
        // Check if file has any data (at least header + 1 data row)
        if ($rawRows->count() < 2) {
            throw new Exception("File Excel trống hoặc không có dữ liệu");
        }
        
        // First row is the header
        $headerRow = $rawRows->first();
        $headers = collect($headerRow)->map(function ($value) {
            return strtolower(trim((string) $value));
        })->toArray();
        
        Log::info("Excel headers found: " . implode(', ', $headers));
        Log::info("Assignment type: " . $this->assignmentType);
        
        // Skip header row, get data rows
        $dataRows = $rawRows->slice(1);
        
        // Convert each row to associative array using headers
        $rows = $dataRows->map(function ($row) use ($headers) {
            $values = collect($row)->values()->toArray();
            $mapped = [];
            foreach ($headers as $index => $header) {
                $mapped[$header] = $values[$index] ?? null;
            }
            return collect($mapped);
        });
        
        Log::info("Data rows count: " . $rows->count());
        
        // Validate required columns based on assignment type
        // Essay template doesn't require 'type' column (all questions are essays)
        if ($this->assignmentType === 'essay') {
            $requiredColumns = ['content', 'points'];
        } else {
            $requiredColumns = ['type', 'content', 'points'];
        }
        
        $missingColumns = [];
        
        foreach ($requiredColumns as $col) {
            if (!in_array($col, $headers)) {
                $missingColumns[] = $col;
            }
        }
        
        if (!empty($missingColumns)) {
            $templateHint = $this->assignmentType === 'essay' 
                ? "Template Essay cần có các cột: difficulty, content, points, rubric, explanation"
                : "File cần có các cột: type, content, points, correct_answer, A, B, C, D (cho trắc nghiệm), explanation";
                
            throw new Exception(
                "Thiếu các cột bắt buộc trong file Excel: " . implode(', ', $missingColumns) . ". " .
                $templateHint
            );
        }
        
        // Đếm số hàng thực sự có data (không trống)
        $validRows = $rows->filter(function ($row) {
            return !$this->isEmptyRow($row);
        });
        
        $this->importLog->update(['total_rows' => $validRows->count()]);
        Log::info("Valid rows (non-empty): " . $validRows->count());
        
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 vì header là 1, và index bắt đầu từ 0
            
            // Skip hàng trống
            if ($this->isEmptyRow($row)) {
                Log::info("Skipping empty row {$rowNumber}");
                continue;
            }
            
            Log::info("Processing row {$rowNumber}", ['data' => $row->toArray()]);

            try {
                $this->processRow($row, $rowNumber);
                $this->processedCount++;
                
                // Update progress
                $this->importLog->updateProgress($this->processedCount, 1);
                
            } catch (Exception $e) {
                Log::error("Error processing row {$rowNumber}: " . $e->getMessage());
                // STOP IMMEDIATELY ON ERROR as requested
                $this->importLog->markFailed($rowNumber, $e->getMessage(), $row->toArray());
                
                AuditLog::log('import_failed', $this->importLog->imported_by, 
                    'Assignment', $this->assignmentId, 
                    ['row' => $rowNumber, 'error' => $e->getMessage()]
                );
                
                throw $e; // Throw to stop Excel import
            }
        }

        $this->importLog->markCompleted();
        Log::info("Import process completed for assignment {$this->assignmentId}");
        
        AuditLog::log('questions_imported', $this->importLog->imported_by, 
            'Assignment', $this->assignmentId, 
            ['total_rows' => $validRows->count()]
        );
    }

    /**
     * Kiểm tra xem hàng có trống không
     * Chỉ check content vì type có thể thiếu (sẽ báo lỗi trong processRow)
     */
    protected function isEmptyRow($row): bool
    {
        // Hàng được coi là trống nếu content trống
        $content = isset($row['content']) ? trim((string) $row['content']) : '';
        
        return empty($content);
    }

    protected function processRow($row, $rowNumber)
    {
        // 1. Validate required fields - content and points
        if (!isset($row['content']) || empty(trim($row['content']))) {
            throw new Exception("Thiếu nội dung câu hỏi (cột 'content')");
        }
        
        if (!isset($row['points'])) {
            throw new Exception("Thiếu điểm (cột 'points')");
        }
        
        // 2. Check type column - handle based on assignment type
        $type = null;
        
        // If type column exists in Excel
        if (isset($row['type']) && !empty(trim((string) $row['type']))) {
            $excelType = strtolower(trim($row['type']));
            
            // Map input type to database enum type
            if (in_array($excelType, ['multiple_choice', 'mc'])) {
                $type = 'multiple_choice';
            } elseif (in_array($excelType, ['short_answer', 'sa'])) {
                $type = 'short_answer';
            } elseif (in_array($excelType, ['essay', 'es'])) {
                $type = 'essay';
            }
            
            if (!$type) {
                throw new Exception("Invalid question type: {$row['type']}. Supported: multiple_choice (MC), short_answer (SA), essay (ES)");
            }
        } else {
            // Type column not provided - auto-detect based on assignment type
            if ($this->assignmentType === 'essay') {
                // Essay template doesn't have type column, auto-set to essay
                $type = 'essay';
            } else {
                throw new Exception(
                    "Thiếu loại câu hỏi (cột 'type'). " .
                    "Vui lòng thêm cột 'type' với giá trị: multiple_choice (MC), short_answer (SA), hoặc essay (ES)"
                );
            }
        }
        
        // Validate question type against assignment type
        $allowedTypes = $this->getAllowedQuestionTypes();
        if (!in_array($type, $allowedTypes)) {
            $allowedTypesStr = implode(', ', $allowedTypes);
            $typeName = $this->getAssignmentTypeName();
            throw new Exception(
                "Loại câu hỏi '{$type}' không được phép cho bài tập loại '{$typeName}'. " .
                "Các loại được phép: {$allowedTypesStr}"
            );
        }
        
        $content = trim($row['content']);
        $points = (float) $row['points'];
        $correctAnswer = isset($row['correct_answer']) ? trim($row['correct_answer']) : null;
        
        // Handle difficulty level (default to 'medium')
        $difficulty = 'medium';
        if (isset($row['difficulty']) && !empty(trim((string) $row['difficulty']))) {
            $inputDifficulty = strtolower(trim($row['difficulty']));
            $validDifficulties = ['easy', 'medium', 'hard'];
            if (in_array($inputDifficulty, $validDifficulties)) {
                $difficulty = $inputDifficulty;
            }
        }
        
        // Handle rubric (grading criteria for essay questions)
        $rubric = isset($row['rubric']) ? trim($row['rubric']) : null;
        
        $options = null;

        // 2. Handle Multiple Choice Options
        if ($type === 'multiple_choice') {
            $options = [];
            // Check columns A, B, C, D...
            foreach (range('A', 'E') as $char) {
                if (isset($row[strtolower($char)]) && !empty($row[strtolower($char)])) {
                    $options[] = [
                        'key' => $char,
                        'text' => trim($row[strtolower($char)])
                    ];
                }
            }

            if (empty($options)) {
                throw new Exception("Multiple choice question requires at least one option");
            }
            
            if (!$correctAnswer) {
                throw new Exception("Multiple choice question requires a correct answer");
            }
        }

        // 3. Create Question
        Question::create([
            'assignment_id' => $this->assignmentId,
            'type' => $type,
            'content' => $content,
            'points' => $points,
            'correct_answer' => $correctAnswer,
            'options' => $options,
            'order_index' => $rowNumber,
            'difficulty' => $difficulty,
            'rubric' => $rubric,
            'explanation' => isset($row['explanation']) ? trim($row['explanation']) : null,
        ]);
    }
}

