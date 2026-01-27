<?php

namespace Modules\Auth\app\Http\Controllers\AttendanceController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Auth\app\Services\EnrollmentImportService;
use Modules\Auth\app\Models\ImportJob;
use Modules\Auth\app\Models\ImportFailure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Controller xử lý import enrollment từ Excel
 * 
 * @group Enrollment Import
 */
class EnrollmentImportController extends Controller
{
    protected EnrollmentImportService $importService;

    public function __construct(EnrollmentImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Upload file và validate
     * 
     * POST /v1/attendance/courses/{courseId}/import-enrollments
     * 
     * @bodyParam file file required File Excel (.xlsx, .xls)
     */
    public function upload(Request $request, int $courseId): JsonResponse
    {
        Log::info('Upload debug', [
            'has_file' => $request->hasFile('file'),
            'all_files' => array_keys($request->allFiles()),
            'content_type' => $request->header('Content-Type'),
            'course_id' => $courseId,
        ]);
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls|max:10240', // Max 10MB
            ]);
            try {
                $file = $request->file('file');
                // $fileName = time() . '_' . $file->getClientOriginalName();
                // $filePath = $file->storeAs('imports/enrollments', $fileName);
                $filePath = $file->store('imports', 'local');
                $absolutePath = Storage::disk('local')->path($filePath);
                
                Log::info('File stored', [
                    'relative' => $filePath,
                    'absolute' => $absolutePath,
                    'exists'   => file_exists($absolutePath),
                ]);
            } catch (\Exception $e) {
                Log::channel('daily')->error('Lecturer file upload failed', [
                    'error' => $e->getMessage()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Tải lên file thất bại',
                    'error' => $e->getMessage()
                ], 500);
            }
            
            // Tạo import job
            $importJob = ImportJob::create([
                'entity_type' => 'student', // Enrollment là về student enrollment
                'file_path' => $filePath,
                'status' => 'processing',
                'total' => 0,
                'processed_rows' => 0,
                'success' => 0,
                'failed' => 0,
            ]);

            // Count total rows
            $totalRows = $this->importService->countTotalRows($absolutePath);
            $importJob->update(['total' => $totalRows]);

            $errors = $this->importService->validateAllRows($absolutePath, $courseId);

            if (!empty($errors)) {
                $importJob->update([
                    'status' => 'failed',
                    'error_message' => count($errors) . ' lỗi validation',
                ]);

                // Save errors to import_failures table
                foreach ($errors as $error) {
                    try {
                        ImportFailure::create([
                            'import_job_id' => $importJob->id,
                            'row_number' => $error['row'] ?? 0,
                            'attribute' => $error['field'] ?? 'validation',
                            'values' => [
                                'student_code' => $error['student_code'] ?? null,
                                'student_name' => $error['student_name'] ?? null,
                            ],
                            'errors' => json_encode([$error['error'] ?? 'Unknown error']),
                            'created_at' => now(),
                        ]);
                    } catch (\Exception $failureException) {
                        Log::error('Failed to create ImportFailure for validation error', [
                            'import_job_id' => $importJob->id,
                            'row' => $error['row'] ?? 0,
                            'error' => $failureException->getMessage(),
                        ]);
                    }
                }

                return response()->json([
                    'success' => false,
                    'message' => 'File có lỗi validation',
                    'import_job_id' => $importJob->id,
                    'total' => $totalRows,
                    'error_count' => count($errors),
                    'errors' => array_slice($errors, 0, 50), // Chỉ trả về 50 lỗi đầu
                ], 422);
            }

            // Validation successful
            $importJob->update(['status' => 'pending']);

            return response()->json([
                'success' => true,
                'message' => 'File hợp lệ, sẵn sàng import',
                'import_job_id' => $importJob->id,
                'total' => $totalRows,
            ]);
        } catch (\Exception $e) {
            Log::error('Error uploading enrollment import file: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi upload file',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Thực hiện import sau khi validate
     * 
     * POST /v1/attendance/import-enrollments/{importJobId}/process
     */
    public function process(int $courseId, int $importJobId): JsonResponse
    {
        try {
            $importJob = ImportJob::findOrFail($importJobId);

            if ($importJob->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Import job không ở trạng thái pending',
                ], 400);
            }

            $importJob->update(['status' => 'processing']);

            // Process import
            $absolutePath = Storage::disk('local')->path($importJob->file_path);

            $result = $this->importService->importAllRows(
                $absolutePath,
                $courseId,
                $importJob
            );

            return response()->json([
                'success' => true,
                'message' => 'Import hoàn tất',
                'import_job_id' => $importJob->id,
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing enrollment import', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'import_job_id' => $importJobId ?? null,
                'course_id' => $courseId,
            ]);
            
            if (isset($importJob)) {
                $importJob->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
                
                // Lưu lỗi vào import_failures với row_number = 0
                try {
                    ImportFailure::create([
                        'import_job_id' => $importJob->id,
                        'row_number' => 0, // 0 = lỗi ở mức process, không phải lỗi ở row cụ thể
                        'attribute' => 'process',
                        'values' => [
                            'course_id' => $courseId,
                            'file_path' => $importJob->file_path ?? null,
                            'error_type' => 'process_error',
                        ],
                        'errors' => json_encode([
                            'Process error: ' . $e->getMessage(),
                            'File: ' . $e->getFile(),
                            'Line: ' . $e->getLine(),
                        ]),
                        'created_at' => now(),
                    ]);
                } catch (\Exception $failureException) {
                    Log::error('Failed to create ImportFailure for process error', [
                        'import_job_id' => $importJob->id,
                        'error' => $failureException->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi import',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy tiến trình import (polling)
     * 
     * GET /v1/attendance/import-enrollments/{importJobId}/progress
     */
    public function getProgress(int $importJobId): JsonResponse
    {
        try {
            $importJob = ImportJob::with('failures')->findOrFail($importJobId);

            // Calculate progress percentage
            $progress = $importJob->total > 0 
                ? round(($importJob->processed_rows / $importJob->total) * 100, 2) 
                : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $importJob->id,
                    'status' => $importJob->status,
                    'progress' => $progress,
                    'total' => $importJob->total,
                    'processed_rows' => $importJob->processed_rows,
                    'success' => $importJob->success,
                    'failed' => $importJob->failed,
                    'error' => $importJob->error,
                    'failure_count' => $importJob->failures->count(),
                    'created_at' => $importJob->created_at,
                    'updated_at' => $importJob->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting import progress: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy import job',
            ], 404);
        }
    }

    /**
     * Lấy chi tiết import job và các lỗi
     * 
     * GET /v1/attendance/import-enrollments/{importJobId}
     */
    public function show(int $importJobId): JsonResponse
    {
        try {
            $importJob = ImportJob::with('failures')->findOrFail($importJobId);

            return response()->json([
                'success' => true,
                'data' => [
                    'import_job' => $importJob,
                    'failures' => $importJob->failures,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error showing import job: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy import job',
            ], 404);
        }
    }

    /**
     * Download template Excel để import
     * 
     * GET /v1/attendance/import-enrollments/download-template
     */
    public function downloadTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $headers = [
            'Mã sinh viên',
            'Tên sinh viên (tùy chọn)',
            'Ghi chú (tùy chọn)',
        ];

        $sampleData = [
            ['SV001', 'Nguyễn Văn A', 'Đăng ký muộn'],
            ['SV002', 'Trần Thị B', ''],
            ['SV003', 'Lê Văn C', ''],
        ];

        // Create spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $col++;
        }

        // Set sample data
        $row = 2;
        foreach ($sampleData as $data) {
            $col = 'A';
            foreach ($data as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }

        // Auto size columns
        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Save to temp file
        $fileName = 'enrollment_import_template.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}
