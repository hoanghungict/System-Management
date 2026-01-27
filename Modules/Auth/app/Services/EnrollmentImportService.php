<?php

declare(strict_types=1);

namespace Modules\Auth\app\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Auth\app\Models\Student;
use Modules\Auth\app\Models\Attendance\Course;
use Modules\Auth\app\Models\Attendance\CourseEnrollment;
use Modules\Auth\app\Models\ImportJob;
use Modules\Auth\app\Models\ImportFailure;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

/**
 * Service import Enrollment (Đăng ký môn học) từ Excel
 * 
 * Format Excel cần có:
 * - Mã sinh viên (student_code hoặc masv)
 * - Tên sinh viên (student_name, optional - để verify)
 */
class EnrollmentImportService
{
    /**
     * Validate tất cả rows trong file Excel
     * 
     * @return array Array of errors, empty if no errors
     */
    public function validateAllRows(string $filePath, int $courseId): array
    {
        Log::info('Starting enrollment validation', ['file' => $filePath, 'course_id' => $courseId]);
        try {
            $resolvedPath = $this->getResolvedPath($filePath);
            if (!$resolvedPath) {
                Log::error('Validation failed: File not found', ['path' => $filePath]);
                return [['row' => 0, 'error' => 'File không tồn tại']];
            }

            // Kiểm tra môn học có tồn tại không
            $course = Course::find($courseId);
            if (!$course) {
                Log::error('Validation failed: Course not found', ['course_id' => $courseId]);
                return [['row' => 0, 'error' => 'Môn học không tồn tại']];
            }
            Log::info('Validation: Course found', ['course_name' => $course->name]);

            $spreadsheet = IOFactory::load($resolvedPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();
            Log::info('Validation: Excel file loaded', ['highest_row' => $highestRow]);


            // Đọc header row
            $headerRow = [];
            $headerRowData = $worksheet->rangeToArray('A1:' . $worksheet->getHighestColumn() . '1')[0];
            foreach ($headerRowData as $cell) {
                $headerRow[] = trim((string)$cell);
            }

            $headerMap = $this->mapHeaderToIndex($headerRow);
            Log::info('Validation: Header mapped', ['header_map' => $headerMap]);
            
            if (!isset($headerMap['student_code'])) {
                Log::error('Validation failed: Missing student_code column');
                return [['row' => 0, 'error' => 'File Excel thiếu cột "Mã sinh viên"']];
            }

            $errors = [];
            $processedCodes = []; // Theo dõi mã SV đã xử lý (tránh trùng trong file)

            // Validate từng row
            for ($row = 2; $row <= $highestRow; $row++) {
                $rowData = $worksheet->rangeToArray("A{$row}:{$worksheet->getHighestColumn()}{$row}")[0];
                $enrollmentData = $this->mapExcelRowToEnrollmentData($rowData, $headerMap);
                Log::debug('Validating row', ['row' => $row, 'data' => $enrollmentData]);

                // Skip empty rows
                if (empty($enrollmentData['student_code'])) {
                    Log::info('Validation: Skipping empty row', ['row' => $row]);
                    continue;
                }

                // Kiểm tra trùng trong file
                if (in_array($enrollmentData['student_code'], $processedCodes)) {
                    $error = [
                        'row' => $row,
                        'student_code' => $enrollmentData['student_code'],
                        'error' => 'Mã sinh viên bị trùng trong file Excel'
                    ];
                    $errors[] = $error;
                    Log::warning('Validation error: Duplicate student code in file', $error);
                    continue;
                }

                $processedCodes[] = $enrollmentData['student_code'];

                // Validate
                $validationErrors = $this->validateEnrollmentData($enrollmentData, $row, $courseId);
                if (!empty($validationErrors)) {
                    $errors = array_merge($errors, $validationErrors);
                    Log::warning('Validation error found for row', ['row' => $row, 'errors' => $validationErrors]);
                } else {
                    Log::info('Validation successful for row', ['row' => $row]);
                }
            }

            if (empty($errors)) {
                Log::info('Enrollment validation finished successfully with no errors.');
            } else {
                Log::warning('Enrollment validation finished with errors.', ['error_count' => count($errors)]);
            }

            return $errors;
        } catch (\Exception $e) {
            Log::error('Fatal error during enrollment validation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [['row' => 0, 'error' => 'Lỗi hệ thống khi đọc file: ' . $e->getMessage()]];
        }
    }

    /**
     * Import tất cả rows sau khi đã validate
     */
    public function importAllRows(string $filePath, int $courseId, ?ImportJob $importJob = null): array
    {
        Log::info('Starting enrollment import', ['file' => $filePath, 'course_id' => $courseId]);
        DB::beginTransaction();
        try {
            $resolvedPath = $this->getResolvedPath($filePath);
            if (!$resolvedPath) {
                throw new \Exception('File không tồn tại');
            }

            $course = Course::findOrFail($courseId);
            Log::info('Course found', ['course_name' => $course->name]);

            $spreadsheet = IOFactory::load($resolvedPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();
            Log::info('Excel file loaded', ['highest_row' => $highestRow]);

            // Đọc header
            $headerRow = [];
            $headerRowData = $worksheet->rangeToArray('A1:' . $worksheet->getHighestColumn() . '1')[0];
            foreach ($headerRowData as $cell) {
                $headerRow[] = trim((string)$cell);
            }

            $headerMap = $this->mapHeaderToIndex($headerRow);
            Log::info('Header mapped', ['header_map' => $headerMap]);

            $successCount = 0;
            $skipCount = 0;
            $failureCount = 0;
            $processedRows = 0;

            // Import từng row
            for ($row = 2; $row <= $highestRow; $row++) {
                $rowData = $worksheet->rangeToArray("A{$row}:{$worksheet->getHighestColumn()}{$row}")[0];
                $enrollmentData = $this->mapExcelRowToEnrollmentData($rowData, $headerMap);

                // Skip empty rows
                if (empty($enrollmentData['student_code'])) {
                    Log::info('Skipping empty row', ['row' => $row]);
                    continue;
                }

                $processedRows++;
                Log::debug('Processing row', ['row' => $row, 'data' => $enrollmentData]);

                try {
                    // Tìm sinh viên theo mã
                    $student = Student::where('student_code', $enrollmentData['student_code'])->first();
                    
                    if (!$student) {
                        $failureCount++;
                        Log::warning('Student not found', ['row' => $row, 'student_code' => $enrollmentData['student_code']]);
                        if ($importJob) {
                            ImportFailure::create([
                                'import_job_id' => $importJob->id,
                                'row_number' => $row,
                                'attribute' => 'student_code',
                                'values' => $enrollmentData,
                                'errors' => json_encode(['Sinh viên không tồn tại trong hệ thống']),
                                'created_at' => now(),
                            ]);
                        }
                        continue;
                    }

                    // Kiểm tra đã đăng ký chưa
                    $existingEnrollment = CourseEnrollment::where('course_id', $courseId)
                        ->where('student_id', $student->id)
                        ->first();

                    if ($existingEnrollment) {
                        $skipCount++;
                        Log::info('Student already enrolled, skipping', ['row' => $row, 'student_code' => $enrollmentData['student_code']]);
                        continue;
                    }

                    // Tạo enrollment
                    CourseEnrollment::create([
                        'course_id' => $courseId,
                        'student_id' => $student->id,
                        'enrolled_at' => now(),
                        'status' => 'active',
                        'note' => 'Imported from Excel',
                    ]);

                    $successCount++;
                    Log::info('Enrollment created successfully', ['row' => $row, 'student_code' => $enrollmentData['student_code']]);

                    // Update progress
                    if ($importJob) {
                        $importJob->update([
                            'processed_rows' => $processedRows,
                            'success' => $successCount,
                            'failed' => $failureCount,
                        ]);
                    }
                } catch (\Exception $e) {
                    $failureCount++;
                    Log::error("Error importing enrollment at row {$row}", [
                        'row' => $row,
                        'student_code' => $enrollmentData['student_code'] ?? 'N/A',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    
                    if ($importJob) {
                        try {
                            ImportFailure::create([
                                'import_job_id' => $importJob->id,
                                'row_number' => $row,
                                'attribute' => 'enrollment',
                                'values' => $enrollmentData,
                                'errors' => json_encode([$e->getMessage()]),
                                'created_at' => now(),
                            ]);
                        } catch (\Exception $failureException) {
                            Log::error('Failed to create ImportFailure record', [
                                'import_job_id' => $importJob->id,
                                'row' => $row,
                                'error' => $failureException->getMessage(),
                            ]);
                        }
                    }
                }
            }

            // Update import job status
            if ($importJob) {
                $importJob->update([
                    'status' => 'done',
                    'processed_rows' => $processedRows,
                    'success' => $successCount,
                    'failed' => $failureCount,
                ]);
            }

            DB::commit();
            
            $summary = [
                'success' => $successCount,
                'skipped' => $skipCount,
                'failed' => $failureCount,
                'total_processed' => $processedRows,
            ];
            
            Log::info('Enrollment import finished successfully', $summary);
            
            return $summary;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Fatal error during enrollment import', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $filePath,
                'course_id' => $courseId,
            ]);
            
            if ($importJob) {
                $importJob->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
                
                // Lưu fatal error vào import_failures với row_number = 0
                try {
                    ImportFailure::create([
                        'import_job_id' => $importJob->id,
                        'row_number' => 0, // 0 = fatal error, không phải lỗi ở row cụ thể
                        'attribute' => 'system',
                        'values' => [
                            'file_path' => $filePath,
                            'course_id' => $courseId,
                            'error_type' => 'fatal_error',
                        ],
                        'errors' => json_encode([
                            'Fatal error: ' . $e->getMessage(),
                            'File: ' . $e->getFile(),
                            'Line: ' . $e->getLine(),
                        ]),
                        'created_at' => now(),
                    ]);
                } catch (\Exception $failureException) {
                    Log::error('Failed to create ImportFailure for fatal error', [
                        'import_job_id' => $importJob->id,
                        'error' => $failureException->getMessage(),
                    ]);
                }
            }
            
            throw $e;
        }
    }

    /**
     * Normalize and resolve a file path from DB to an actual accessible file path
     */
    private function getResolvedPath(string $filePath): ?string
    {
        // Try direct path first
        if (file_exists($filePath)) {
            return $filePath;
        }

        // Try storage path
        $storagePath = storage_path('app/' . $filePath);
        if (file_exists($storagePath)) {
            return $storagePath;
        }

        // Try public path
        $publicPath = public_path($filePath);
        if (file_exists($publicPath)) {
            return $publicPath;
        }

        // Try removing leading slash
        $trimmedPath = ltrim($filePath, '/');
        $storagePathTrimmed = storage_path('app/' . $trimmedPath);
        if (file_exists($storagePathTrimmed)) {
            return $storagePathTrimmed;
        }

        return null;
    }

    /**
     * Map header row to column index
     */
    private function mapHeaderToIndex(array $headerRow): array
    {
        $map = [];

        foreach ($headerRow as $index => $header) {
            $normalizedHeader = strtolower(trim($header));

            // Map student code
            if (in_array($normalizedHeader, ['mã sinh viên', 'masv', 'student_code', 'ma sv', 'student code'])) {
                $map['student_code'] = $index;
            }

            // Map student name (optional)
            if (in_array($normalizedHeader, ['tên sinh viên', 'họ tên', 'student_name', 'ten sv', 'student name', 'full name'])) {
                $map['student_name'] = $index;
            }

            // Map note (optional)
            if (in_array($normalizedHeader, ['ghi chú', 'note', 'notes', 'ghi chu'])) {
                $map['note'] = $index;
            }
        }

        return $map;
    }

    /**
     * Map Excel row to enrollment data array
     */
    private function mapExcelRowToEnrollmentData(array $row, array $headerMap): array
    {
        $data = [];

        if (isset($headerMap['student_code'])) {
            $data['student_code'] = trim((string)($row[$headerMap['student_code']] ?? ''));
        }

        if (isset($headerMap['student_name'])) {
            $data['student_name'] = trim((string)($row[$headerMap['student_name']] ?? ''));
        }

        if (isset($headerMap['note'])) {
            $data['note'] = trim((string)($row[$headerMap['note']] ?? ''));
        }

        return $data;
    }

    /**
     * Validate enrollment data
     */
    private function validateEnrollmentData(array $data, int $rowNumber, int $courseId): array
    {
        $errors = [];

        // Validate student code
        if (empty($data['student_code'])) {
            $errors[] = [
                'row' => $rowNumber,
                'field' => 'student_code',
                'error' => 'Mã sinh viên không được để trống'
            ];
            return $errors; // Return early nếu không có mã SV
        }

        // Kiểm tra sinh viên có tồn tại không
        $student = Student::where('student_code', $data['student_code'])->first();
        if (!$student) {
            $errors[] = [
                'row' => $rowNumber,
                'student_code' => $data['student_code'],
                'error' => 'Sinh viên không tồn tại trong hệ thống'
            ];
            return $errors;
        }

        // Kiểm tra đã đăng ký chưa
        $existingEnrollment = CourseEnrollment::where('course_id', $courseId)
            ->where('student_id', $student->id)
            ->exists();

        if ($existingEnrollment) {
            $errors[] = [
                'row' => $rowNumber,
                'student_code' => $data['student_code'],
                'error' => 'Sinh viên đã đăng ký môn học này'
            ];
        }

        return $errors;
    }

    /**
     * Count total rows in Excel file
     */
    public function countTotalRows(string $filePath): int
    {
        try {
            $resolvedPath = $this->getResolvedPath($filePath);
            if (!$resolvedPath) {
                return 0;
            }

            $spreadsheet = IOFactory::load($resolvedPath);
            $worksheet = $spreadsheet->getActiveSheet();
            
            // -1 để trừ header row
            return max(0, $worksheet->getHighestRow() - 1);
        } catch (\Exception $e) {
            Log::error('Error counting rows: ' . $e->getMessage());
            return 0;
        }
    }
}
