<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Bảng semesters - Quản lý học kỳ
     * Mỗi năm học có 2 học kỳ chính + học kỳ hè (tùy chọn)
     */
    public function up(): void
    {
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            
            // Thông tin cơ bản
            $table->string('name', 100);                    // "Học kỳ 1 2024-2025"
            $table->string('code', 20)->unique();           // "HK1_2024" - mã duy nhất
            $table->string('academic_year', 20);            // "2024-2025"
            $table->enum('semester_type', ['1', '2', '3'])->default('1'); // Loại học kỳ
            
            // Thời gian
            $table->date('start_date');                     // Ngày bắt đầu học kỳ
            $table->date('end_date');                       // Ngày kết thúc học kỳ
            
            // Trạng thái
            $table->boolean('is_active')->default(false);   // Học kỳ đang hoạt động
            $table->text('description')->nullable();        // Mô tả thêm
            
            $table->timestamps();
            
            // Index
            $table->index('academic_year');
            $table->index('is_active');
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('semesters');
    }
};
