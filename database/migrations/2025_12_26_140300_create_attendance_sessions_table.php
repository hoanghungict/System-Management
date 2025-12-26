<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Bảng attendance_sessions - Buổi học
     * Mỗi buổi học được tự động tạo dựa trên thời khóa biểu của môn học
     */
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            
            // ===== LIÊN KẾT =====
            $table->foreignId('course_id')
                  ->constrained('courses')
                  ->onDelete('cascade');                    // Thuộc môn học nào
            
            // ===== THÔNG TIN BUỔI HỌC =====
            $table->integer('session_number');              // Số thứ tự buổi: 1, 2, 3...
            $table->date('session_date');                   // Ngày học: 2024-09-05
            $table->tinyInteger('day_of_week');             // Thứ trong tuần: 2-8 (CN=8)
            $table->time('start_time');                     // Giờ bắt đầu: 07:30
            $table->time('end_time');                       // Giờ kết thúc: 09:30
            
            // ===== NỘI DUNG =====
            $table->string('topic', 255)->nullable();       // Chủ đề buổi học: "Chương 1: Giới thiệu"
            $table->string('room', 50)->nullable();         // Phòng học (có thể khác với mặc định)
            $table->text('notes')->nullable();              // Ghi chú
            
            // ===== TRẠNG THÁI =====
            // scheduled: đã lên lịch, chưa điểm danh
            // in_progress: đang điểm danh
            // completed: đã hoàn thành điểm danh
            // cancelled: hủy buổi học
            // holiday: nghỉ lễ
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled', 'holiday'])
                  ->default('scheduled');
                  
            // ===== ĐIỂM DANH =====
            $table->timestamp('started_at')->nullable();    // Thời điểm bắt đầu điểm danh
            $table->timestamp('completed_at')->nullable();  // Thời điểm hoàn thành điểm danh
            
            $table->foreignId('marked_by')
                  ->nullable()
                  ->constrained('lecturer')
                  ->onDelete('set null');                   // Ai điểm danh
            
            $table->timestamps();
            
            // ===== INDEXES =====
            $table->unique(['course_id', 'session_number']); // Số buổi duy nhất trong môn
            $table->index(['course_id', 'session_date']);
            $table->index('status');
            $table->index('session_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
