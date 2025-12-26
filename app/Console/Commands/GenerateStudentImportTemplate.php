<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class GenerateStudentImportTemplate extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'import:generate-student-template 
                            {--path=storage/app/templates/student_import_template.xlsx : Path to save the template}';

    /**
     * The console command description.
     */
    protected $description = 'Generate Excel template for importing students';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = $this->option('path');
        $fullPath = base_path($path);
        
        // Create directory if not exists
        $directory = dirname($fullPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        try {
            // Prepare data
            $headers = [
                'full_name',
                'email',
                'student_code',
                'birth_date',
                'gender',
                'address',
                'phone',
                'class_id',
            ];
            
            $sampleData = [
                ['Nguyễn Văn A', 'sv001@example.com', 'SV001', '2000-01-15', 'male', '123 Đường ABC, Quận 1', '0123456789', '1'],
                ['Trần Thị B', 'sv002@example.com', 'SV002', '2001-05-20', 'female', '456 Đường XYZ, Quận 2', '0987654321', '1'],
                ['Lê Văn C', 'sv003@example.com', 'SV003', '1999-12-10', 'male', '789 Đường DEF, Quận 3', '', '2'],
                ['Phạm Thị D', 'sv004@example.com', 'SV004', '2000-08-25', 'female', '', '', '1'],
            ];
            
            // Combine headers and data
            $allData = array_merge([$headers], $sampleData);
            
            // Get relative path
            $relativePath = str_replace(storage_path('app') . '/', '', $fullPath);
            if ($relativePath === $fullPath) {
                $relativePath = str_replace(base_path() . '/', '', $fullPath);
            }
            
            // Create Excel file using simple array export
            Excel::store(
                new \App\Exports\StudentImportTemplateExport($allData),
                $relativePath,
                'local'
            );
            
            $savedPath = storage_path('app/' . $relativePath);
            
            $this->info("✅ File Excel template đã được tạo thành công!");
            $this->info("📁 Đường dẫn: {$savedPath}");
            $this->info("");
            $this->info("📋 File bao gồm:");
            $this->info("   - Header row: full_name, email, student_code, birth_date, gender, address, phone, class_id");
            $this->info("   - 4 dòng dữ liệu mẫu");
            $this->info("");
            $this->info("💡 Bạn có thể mở file này bằng Excel và xóa các dòng mẫu trước khi điền dữ liệu thực tế.");
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("❌ Lỗi khi tạo file template: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

