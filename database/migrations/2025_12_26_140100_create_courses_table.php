<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Bảng courses - Môn học/Lớp học phần
     * Đây là đơn vị chính để điểm danh (thay vì class như trước)
     */
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            
            // ===== THÔNG TIN CƠ BẢN =====
            $table->string('code', 50);                     // "LTMT_01" - mã lớp học phần
            $table->string('name', 255);                    // "Lập trình máy tính"
            $table->integer('credits')->default(3);         // Số tín chỉ
            $table->text('description')->nullable();        // Mô tả môn học
            
            // ===== LIÊN KẾT =====
            $table->foreignId('semester_id')
                  ->constrained('semesters')
                  ->onDelete('cascade');                    // Thuộc học kỳ nào
                  
            $table->foreignId('lecturer_id')
                  ->nullable()
                  ->constrained('lecturer')
                  ->onDelete('set null');                   // Giảng viên phụ trách
                  
            $table->foreignId('department_id')
                  ->nullable()
                  ->constrained('department')
                  ->onDelete('set null');                   // Thuộc khoa nào
            
            // ===== THỜI KHÓA BIỂU =====
            // schedule_days: JSON array các ngày học trong tuần
            // VD: [2, 4, 6] = Thứ 2, Thứ 4, Thứ 6
            // VD: [5, 7] = Thứ 5, Thứ 7 (Chủ nhật = 8)
            $table->json('schedule_days')->nullable();      // [5, 7]
            $table->time('start_time')->nullable();         // "07:30:00"
            $table->time('end_time')->nullable();           // "09:30:00"
            $table->string('room', 50)->nullable();         // "A101"
            
            // ===== CẤU HÌNH ĐIỂM DANH =====
            $table->integer('total_sessions')->default(30);     // Tổng số buổi học dự kiến
            $table->integer('max_absences')->default(3);        // Số buổi tối đa được nghỉ
            $table->integer('absence_warning')->default(2);     // Cảnh báo khi nghỉ >= X buổi
            $table->integer('late_threshold_minutes')->default(15); // Muộn sau X phút = vắng
            
            // ===== THỜI GIAN HỌC =====
            $table->date('start_date');                     // Ngày bắt đầu môn học
            $table->date('end_date');                       // Ngày kết thúc môn học
            
            // ===== TRẠNG THÁI =====
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])
                  ->default('draft');                       // Trạng thái môn học
            $table->boolean('sessions_generated')->default(false); // Đã tạo lịch học chưa
            
            $table->timestamps();
            $table->softDeletes();
            
            // ===== INDEXES =====
            $table->unique(['code', 'semester_id']);        // Mã môn duy nhất trong 1 học kỳ
            $table->index('semester_id');
            $table->index('lecturer_id');
            $table->index('status');
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
