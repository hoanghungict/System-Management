<?php

declare(strict_types=1);

namespace Modules\Auth\app\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Modules\Auth\app\Models\Lecturer;
use Modules\Auth\app\Models\LecturerAccount;
use Modules\Auth\app\Models\ImportJob;
use Modules\Auth\app\Models\ImportFailure;
use Modules\Auth\app\Repositories\Interfaces\AuthRepositoryInterface;
use Modules\Notifications\app\Services\KafkaService\KafkaProducerService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;

class LecturerImportService
{
    protected AuthRepositoryInterface $authRepository;
    protected KafkaProducerService $kafkaProducerService;

    public function __construct(
        AuthRepositoryInterface $authRepository,
        KafkaProducerService $kafkaProducerService
    ) {
        $this->authRepository = $authRepository;
        $this->kafkaProducerService = $kafkaProducerService;
    }

    /**
     * Đếm tổng số rows trong file Excel (trừ header)
     */
    public function countTotalRows(string $filePath): int
    {
        $resolvedPath = $this->getResolvedPath($filePath);
        $spreadsheet = IOFactory::load($resolvedPath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        return max(0, $highestRow - 1); // Trừ header row
    }

    /**
     * Giải quyết đường dẫn file
     */
    public function getResolvedPath(string $storedPath): string
    {
        // Nếu storedPath là đường dẫn tuyệt đối và file tồn tại -> dùng luôn
        if (file_exists($storedPath)) {
            return $storedPath;
        }

        // Thử tìm ở storage/app
        $storagePath = storage_path('app/' . $storedPath);
        if (file_exists($storagePath)) {
            return $storagePath;
        }

        // Thử tìm ở storage/app/imports
        $importsPath = storage_path('app/imports/' . basename($storedPath));
        if (file_exists($importsPath)) {
            return $importsPath;
        }

        // Không tìm thấy
        Log::channel('daily')->warning('File not found for import', [
            'stored_path' => $storedPath,
            'tried_paths' => [$storedPath, $storagePath, $importsPath]
        ]);

        return '';
    }

    /**
     * Validate tất cả rows trước khi import
     * Return array của các lỗi (rỗng nếu không có lỗi)
     */
    public function validateAllRows(string $filePath, int $importJobId): array
    {
        $errors = [];
        $resolvedPath = $this->getResolvedPath($filePath);
        $spreadsheet = IOFactory::load($resolvedPath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        if (empty($rows)) {
            return ['File Excel trống'];
        }

        // Row đầu tiên là header
        $headerRow = $rows[0];
        $headerMap = $this->mapHeaderToIndex($headerRow);

        Log::channel('daily')->info('Lecturer import header mapping', [
            'import_job_id' => $importJobId,
            'headers' => $headerRow,
            'header_map' => $headerMap,
        ]);

        // Validate từng row (bắt đầu từ row 2, index 1)
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $rowNumber = $i + 1; // Row number trong Excel (1-indexed)

            // Skip empty rows
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $lecturerData = $this->mapExcelRowToLecturerData($row, $headerMap);
            $validationErrors = $this->validateLecturerData($lecturerData, $rowNumber);

            if (!empty($validationErrors)) {
                $errors[] = [
                    'row' => $rowNumber,
                    'errors' => $validationErrors,
                    'data' => $lecturerData
                ];

                // Flatten validation errors for database storage
                // $validationErrors is a nested array like ['field' => ['error1', 'error2']]
                $flattenedErrors = [];
                foreach ($validationErrors as $field => $fieldErrors) {
                    if (is_array($fieldErrors)) {
                        $flattenedErrors = array_merge($flattenedErrors, $fieldErrors);
                    } else {
                        $flattenedErrors[] = $fieldErrors;
                    }
                }

                // Lưu lỗi vào database
                ImportFailure::create([
                    'import_job_id' => $importJobId,
                    'row_number' => $rowNumber,
                    'attribute' => array_key_first($validationErrors),
                    'errors' => implode('; ', $flattenedErrors),
                    'values' => $lecturerData,
                ]);
            }
        }

        return $errors;
    }

    /**
     * Import tất cả rows (chỉ gọi sau khi validate thành công)
     */
    public function importAllRows(string $filePath, ImportJob $importJob): void
    {
        $resolvedPath = $this->getResolvedPath($filePath);
        $spreadsheet = IOFactory::load($resolvedPath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $headerRow = $rows[0];
        $headerMap = $this->mapHeaderToIndex($headerRow);

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $lecturerData = $this->mapExcelRowToLecturerData($row, $headerMap);

            try {
                DB::transaction(function () use ($lecturerData, $importJob) {
                    // Tạo Lecturer
                    $lecturer = Lecturer::create([
                        'full_name' => $lecturerData['full_name'],
                        'email' => $lecturerData['email'],
                        'lecturer_code' => $lecturerData['lecturer_code'],
                        'phone' => $lecturerData['phone'] ?? null,
                        'address' => $lecturerData['address'] ?? null,
                        'gender' => $lecturerData['gender'] ?? null,
                        'birth_date' => $lecturerData['birth_date'] ?? null,
                        'department_id' => $lecturerData['department_id'] ?? null,
                        'experience_number' => $lecturerData['experience_number'] ?? null,
                    ]);

                    // Tạo account cho Lecturer
                    $username = 'gv_' . $lecturer->lecturer_code;
                    $password = '123456';

                    $this->authRepository->createLecturerAccount([
                        'username' => $username,
                        'password' => $password,
                        'lecturer_id' => $lecturer->id,
                        'is_admin' => false
                    ]);

                    // Gửi Kafka event
                    $this->kafkaProducerService->send('lecturer.registered', [
                        'user_id' => $lecturer->id,
                        'name' => $lecturer->full_name ?? "Unknown",
                        'user_name' => $username,
                        'password' => $password
                    ]);

                    $importJob->incrementSuccess();
                });
            } catch (\Exception $e) {
                $importJob->incrementFailed();
                Log::channel('daily')->error('Failed to import lecturer row', [
                    'import_job_id' => $importJob->id,
                    'row' => $i + 1,
                    'error' => $e->getMessage()
                ]);
            }

            $importJob->incrementProcessed();
        }
    }

    /**
     * Map header row thành index
     */
    private function mapHeaderToIndex(array $headerRow): array
    {
        $map = [];
        $headerMappings = [
            'full_name' => ['họ tên', 'ho ten', 'họ và tên', 'ho va ten', 'full name', 'fullname', 'full_name', 'tên', 'ten', 'name'],
            'email' => ['email', 'e-mail', 'mail', 'địa chỉ email', 'dia chi email'],
            'lecturer_code' => ['mã gv', 'ma gv', 'mã giảng viên', 'ma giang vien', 'lecturer code', 'lecturer_code', 'code', 'msgv'],
            'phone' => ['sđt', 'sdt', 'số điện thoại', 'so dien thoai', 'phone', 'điện thoại', 'dien thoai'],
            'address' => ['địa chỉ', 'dia chi', 'address'],
            'gender' => ['giới tính', 'gioi tinh', 'gender', 'gt'],
            'birth_date' => ['ngày sinh', 'ngay sinh', 'birth date', 'birth_date', 'birthday', 'dob', 'sinh nhật'],
            'department_id' => ['mã khoa', 'ma khoa', 'khoa', 'department', 'department_id', 'phòng ban', 'phong ban'],
            'experience_number' => ['kinh nghiệm', 'kinh nghiem', 'experience', 'experience_number', 'số năm kinh nghiệm', 'so nam kinh nghiem', 'năm kinh nghiệm'],
        ];

        foreach ($headerRow as $index => $header) {
            if ($header === null) continue;
            $normalizedHeader = mb_strtolower(trim($header));

            foreach ($headerMappings as $field => $possibleNames) {
                if (in_array($normalizedHeader, $possibleNames)) {
                    $map[$field] = $index;
                    break;
                }
            }
        }

        return $map;
    }

    /**
     * Map Excel row thành lecturer data array
     */
    private function mapExcelRowToLecturerData(array $row, array $headerMap): array
    {
        $data = [];

        foreach ($headerMap as $field => $index) {
            $value = $row[$index] ?? null;

            if ($field === 'birth_date' && $value !== null) {
                $value = $this->parseDate($value);
            }

            if ($field === 'experience_number' && $value !== null) {
                $value = (int) $value;
            }

            if ($field === 'department_id' && $value !== null) {
                $value = (int) $value;
            }

            $data[$field] = $value;
        }

        return $data;
    }

    /**
     * Parse date từ nhiều format khác nhau
     */
    private function parseDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            // Nếu là số (Excel date serial number)
            if (is_numeric($value)) {
                $date = ExcelDate::excelToDateTimeObject($value);
                return $date->format('Y-m-d');
            }

            // Parse từ string
            $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y', 'Y/m/d'];
            foreach ($formats as $format) {
                try {
                    $date = Carbon::createFromFormat($format, $value);
                    if ($date) {
                        return $date->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Thử Carbon::parse
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Kiểm tra row có rỗng không
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string)$cell) !== '') {
                return false;
            }
        }
        return true;
    }

    /**
     * Validate dữ liệu giảng viên
     */
    private function validateLecturerData(array $data, int $rowNumber): array
    {
        $rules = [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:lecturer,email',
            'lecturer_code' => 'required|string|max:50|unique:lecturer,lecturer_code',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'gender' => 'nullable|string|in:Nam,Nữ,male,female,M,F',
            'birth_date' => 'nullable|date',
            'department_id' => 'nullable|integer|exists:department,id',
            'experience_number' => 'nullable|integer|min:0|max:100',
        ];

        $messages = [
            'full_name.required' => "Họ tên không được để trống (Dòng {$rowNumber})",
            'email.required' => "Email không được để trống (Dòng {$rowNumber})",
            'email.email' => "Email không hợp lệ (Dòng {$rowNumber})",
            'email.unique' => "Email đã tồn tại trong hệ thống (Dòng {$rowNumber})",
            'lecturer_code.required' => "Mã giảng viên không được để trống (Dòng {$rowNumber})",
            'lecturer_code.unique' => "Mã giảng viên đã tồn tại trong hệ thống (Dòng {$rowNumber})",
            'department_id.exists' => "Khoa/phòng ban không tồn tại (Dòng {$rowNumber})",
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            return $validator->errors()->toArray();
        }

        return [];
    }
}
