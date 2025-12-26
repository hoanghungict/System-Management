<?php

declare(strict_types=1);

namespace Modules\Auth\app\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Auth\app\Models\Student;
use Modules\Auth\app\Models\ImportJob;
use Modules\Auth\app\Models\ImportFailure;
use Modules\Auth\app\Repositories\Interfaces\AuthRepositoryInterface;
use Modules\Notifications\app\Services\KafkaService\KafkaProducerService;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class StudentImportService
{
    protected $authRepository;
    protected $kafkaProducer;

    public function __construct(
        AuthRepositoryInterface $authRepository,
        KafkaProducerService $kafkaProducer
    ) {
        $this->authRepository = $authRepository;
        $this->kafkaProducer = $kafkaProducer;
    }

    /**
     * Validate tất cả rows trong file Excel
     * 
     * @return array Array of errors, empty if no errors
     */
    public function validateAllRows(string $filePath, int $importJobId): array
    {
        $errors = [];
        
        try {
            // Resolve path (handles absolute path vs relative path mismatches)
            $resolved = $this->getResolvedPath($filePath);

            if (empty($resolved) || !file_exists($resolved) || !is_readable($resolved)) {
                Log::channel('daily')->error('File not found or not readable when validating', ['file_path' => $filePath, 'resolved' => $resolved]);
                return [['row' => 0, 'errors' => ['file' => 'File không tồn tại hoặc không thể đọc được bởi worker. Kiểm tra shared storage/volume.']]];
            }

            // Read Excel file
            try {
                $rows = Excel::toArray(null, $resolved);
            } catch (\Throwable $e) {
                Log::channel('daily')->error('Excel read failed during validateAllRows', ['error' => $e->getMessage(), 'file' => $resolved]);
                return [['row' => 0, 'errors' => ['file' => 'Lỗi đọc file Excel: ' . $e->getMessage()]]];
            }
            
            if (empty($rows) || empty($rows[0])) {
                return [['row' => 0, 'errors' => ['file' => 'File Excel trống hoặc không hợp lệ']]];
            }
            
            // Get header row (first row)
            $headerRow = $rows[0][0] ?? [];
            $headerMap = $this->mapHeaderToIndex($headerRow);
            
            // Skip header row (index 0)
            $dataRows = array_slice($rows[0], 1);
            
            foreach ($dataRows as $index => $row) {
                $rowNumber = $index + 2; // +2 vì có header và index bắt đầu từ 0
                
                // Map Excel columns to array using header
                $studentData = $this->mapExcelRowToStudentData($row, $headerMap);
                
                // Validate
                $validator = $this->validateStudentData($studentData, $rowNumber);
                
                if ($validator->fails()) {
                    // Lưu lỗi vào import_failures
                    ImportFailure::create([
                        'import_job_id' => $importJobId,
                        'row_number' => $rowNumber,
                        'attribute' => array_key_first($validator->errors()->toArray()),
                        'errors' => $validator->errors()->first(),
                        'values' => $studentData,
                    ]);
                    
                    $errors[] = [
                        'row' => $rowNumber,
                        'errors' => $validator->errors()->toArray()
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::channel('daily')->error('Error validating Excel file', [
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);
            
            return [['row' => 0, 'errors' => ['file' => 'Lỗi đọc file: ' . $e->getMessage()]]];
        }
        
        return $errors;
    }

    /**
     * Import tất cả rows sau khi đã validate
     */
    public function importAllRows(string $filePath, ImportJob $importJob): void
    {
        try {
            // Resolve file path
            $resolved = $this->getResolvedPath($filePath);
            if (empty($resolved) || !file_exists($resolved) || !is_readable($resolved)) {
                throw new \Exception('File không tồn tại hoặc không thể đọc được khi import');
            }

            // Read Excel file
            try {
                $rows = Excel::toArray(null, $resolved);
            } catch (\Throwable $e) {
                Log::channel('daily')->error('Excel read failed during importAllRows', ['error' => $e->getMessage(), 'file' => $resolved]);
                throw new \Exception('Lỗi đọc file Excel: ' . $e->getMessage());
            }
            
            // Get header row
            $headerRow = $rows[0][0] ?? [];
            $headerMap = $this->mapHeaderToIndex($headerRow);
            
            // Skip header row
            $dataRows = array_slice($rows[0], 1);
            
            $successCount = 0;
            
            foreach ($dataRows as $index => $row) {
                $rowNumber = $index + 2;
                
                // Map Excel row to student data using header
                $studentData = $this->mapExcelRowToStudentData($row, $headerMap);
                
                // Create student in transaction
                DB::transaction(function () use ($studentData, $importJob, &$successCount) {
                    // Create student
                    $student = Student::create([
                        'full_name' => $studentData['full_name'],
                        'email' => $studentData['email'],
                        'student_code' => $studentData['student_code'],
                        'birth_date' => $studentData['birth_date'] ?? null,
                        'gender' => $studentData['gender'] ?? null,
                        'address' => $studentData['address'] ?? null,
                        'phone' => $studentData['phone'] ?? null,
                        'class_id' => $studentData['class_id'] ?? null,
                        'import_job_id' => $importJob->id,
                        'imported_at' => now(),
                        'account_status' => 'inactive',
                    ]);
                    
                    // Create account
                    $this->createStudentAccount($student);
                    
                    $successCount++;
                });
                
                // Update progress
                $importJob->incrementProcessed();
                $importJob->incrementSuccess();
            }
            
        } catch (\Exception $e) {
            Log::channel('daily')->error('Error importing students', [
                'import_job_id' => $importJob->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Normalize and resolve a file path from DB to an actual accessible file path.
     * Returns full path string if found or null if not found.
     */
    public function getResolvedPath(string $filePath): ?string
    {
        // 1) Already absolute & readable
        if (file_exists($filePath) && is_readable($filePath)) {
            return $filePath;
        }

        // 2) If path looks like a relative storage path, use Storage disk
        try {
            if (\Illuminate\Support\Facades\Storage::disk('local')->exists($filePath)) {
                return \Illuminate\Support\Facades\Storage::disk('local')->path($filePath);
            }
        } catch (\Exception $e) {
            // ignore
        }

        // 3) Legacy path substitution: /storage/app/ -> /storage/app/private/
        $legacyCandidate = str_replace(storage_path('app/'), storage_path('app/private/'), $filePath);
        if (file_exists($legacyCandidate) && is_readable($legacyCandidate)) {
            return $legacyCandidate;
        }

        // 4) Try basename under imports folder
        $basename = basename((string) $filePath);
        $candidate = \Illuminate\Support\Facades\Storage::disk('local')->path('imports/' . $basename);
        if (file_exists($candidate) && is_readable($candidate)) {
            return $candidate;
        }

        return null;
    }

    /**
     * Map header row to column index
     * Hỗ trợ nhiều tên cột khác nhau (case-insensitive)
     */
    protected function mapHeaderToIndex(array $headerRow): array
    {
        $headerMap = [];
        
        // Mapping các tên cột có thể có
        $columnMappings = [
            'full_name' => ['full_name', 'fullname', 'họ và tên', 'ho ten', 'tên', 'name'],
            'email' => ['email', 'e-mail', 'mail'],
            'student_code' => ['student_code', 'studentcode', 'student_cc', 'mã sinh viên', 'ma sinh vien', 'mssv', 'code'],
            'birth_date' => ['birth_date', 'birthdate', 'ngày sinh', 'ngay sinh', 'dob', 'date_of_birth'],
            'gender' => ['gender', 'giới tính', 'gioi tinh', 'sex', 'nam', 'nữ', 'nu'],
            'address' => ['address', 'địa chỉ', 'dia chi', 'addr'],
            'phone' => ['phone', 'số điện thoại', 'so dien thoai', 'tel', 'mobile', 'sdt'],
            'class_id' => ['class_id', 'classid', 'lớp', 'lop', 'class', 'class_code', 'classcode'],
        ];
        
        foreach ($headerRow as $index => $header) {
            $headerLower = strtolower(trim($header));
            
            foreach ($columnMappings as $key => $aliases) {
                if (in_array($headerLower, $aliases)) {
                    $headerMap[$key] = $index;
                    break;
                }
            }
        }
        
        return $headerMap;
    }

    /**
     * Map Excel row to student data array
     * Đọc theo header name (linh hoạt, không phụ thuộc thứ tự cột)
     */
    protected function mapExcelRowToStudentData(array $row, array $headerMap): array
    {
        $getValue = function($key) use ($row, $headerMap) {
            if (!isset($headerMap[$key])) {
                return null;
            }

            $index = $headerMap[$key];

            if (!array_key_exists($index, $row)) {
                return null;
            }

            $val = $row[$index];

            // If it's already a string, trim it. If numeric or boolean, cast to string safely.
            if (is_string($val)) {
                return trim($val);
            }

            if (is_numeric($val) || is_bool($val)) {
                return trim((string) $val);
            }

            // For arrays/objects/other unexpected types, return null to avoid trim() errors
            return null;
        };

        return [
            'full_name' => $getValue('full_name') ?? '',
            'email' => $getValue('email') ?? '',
            'student_code' => $getValue('student_code') ?? '',
            'birth_date' => !empty($getValue('birth_date')) ? $this->parseDate($getValue('birth_date')) : null,
            'gender' => !empty($getValue('gender')) ? strtolower((string) $getValue('gender')) : null,
            'address' => $getValue('address'),
            'phone' => $getValue('phone'),
            'class_id' => !empty($getValue('class_id')) ? (int) $getValue('class_id') : null,
        ];
    }

    /**
     * Parse date from various formats
     */
    protected function parseDate($date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            // Handle Excel date format (số serial - Excel stores dates as numbers)
            if (is_numeric($date) && $date > 1) {
                // Excel epoch starts from 1900-01-01
                $excelEpoch = Carbon::create(1900, 1, 1);
                $days = (int) $date - 2; // Excel has a bug: it thinks 1900 is a leap year
                return $excelEpoch->addDays($days)->format('Y-m-d');
            }

            // Try common date formats
            $formats = ['Y-m-d', 'd/m/Y', 'Y/m/d', 'd-m-Y', 'm/d/Y'];
            
            foreach ($formats as $format) {
                try {
                    $parsed = Carbon::createFromFormat($format, trim($date));
                    return $parsed->format('Y-m-d');
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Try Carbon parse (auto-detect)
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            Log::channel('daily')->warning('Failed to parse date', [
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Validate student data
     */
    protected function validateStudentData(array $data, int $rowNumber): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($data, [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:student,email',
            'student_code' => 'required|string|max:50|unique:student,student_code',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'class_id' => 'nullable|exists:class,id',
        ], [
            'email.unique' => "Email đã tồn tại trong hệ thống (Row {$rowNumber})",
            'student_code.unique' => "Mã sinh viên đã tồn tại (Row {$rowNumber})",
            'class_id.exists' => "Lớp học không tồn tại (Row {$rowNumber})",
        ]);
    }

    /**
     * Create student account
     */
    protected function createStudentAccount(Student $student): void
    {
        $username = 'sv_' . $student->student_code;
        $password = '123456';
        
        $this->authRepository->createStudentAccount([
            'username' => $username,
            'password' => $password,
            'student_id' => $student->id
        ]);
        
        // Send Kafka event
        $this->kafkaProducer->send('student.registered', [
            'user_id' => $student->id,
            'name' => $student->full_name ?? "Unknown",
            'user_name' => $username,
            'password' => $password
        ]);
    }

    /**
     * Count total rows in Excel file
     */
    public function countTotalRows(string $filePath): int
    {
        // Ensure we resolve path before counting

        try {
            // Defensive check: ensure Excel facade or binding is available
            if (!class_exists(\Maatwebsite\Excel\Facades\Excel::class) || !app()->bound('excel')) {
                $msg = 'Excel package (maatwebsite/excel) is not available in this environment. Ensure it is installed and the service provider is registered, then restart queue workers.';
                Log::channel('daily')->error('Excel service not available', ['message' => $msg]);
                throw new \Exception($msg);
            }

            // Resolve the actual file path
            $resolved = $this->getResolvedPath($filePath);
            if (empty($resolved) || !file_exists($resolved) || !is_readable($resolved)) {
                Log::channel('daily')->error('File not found or not readable when counting rows', ['file_path' => $filePath, 'resolved' => $resolved]);
                throw new \Exception('File không tồn tại hoặc không thể đọc được (đường dẫn: ' . ($resolved ?? $filePath) . ')');
            }

            // Use null as import class per package docs
            $rows = Excel::toArray(null, $resolved);

            if (empty($rows) || empty($rows[0])) {
                return 0;
            }

            // Subtract header row
            return count($rows[0]) - 1;
        } catch (\Exception $e) {
            Log::channel('daily')->error('Error counting rows', ['error' => $e->getMessage(), 'file_path' => $filePath]);
            throw $e; // Let job catch and mark as failed with message
        }
    }
}

