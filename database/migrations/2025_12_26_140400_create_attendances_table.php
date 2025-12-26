<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Bảng attendances - Chi tiết điểm danh từng sinh viên
     * Mỗi record = 1 sinh viên trong 1 buổi học
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            
            // ===== LIÊN KẾT =====
            $table->foreignId('session_id')
                  ->constrained('attendance_sessions')
                  ->onDelete('cascade');                    // Thuộc buổi học nào
                  
            $table->foreignId('student_id')
                  ->constrained('student')
                  ->onDelete('cascade');                    // Sinh viên nào
            
            // ===== TRẠNG THÁI ĐIỂM DANH =====
            // present: có mặt
            // absent: vắng không phép
            // late: đến muộn
            // excused: vắng có phép
            // not_marked: chưa điểm danh
            $table->enum('status', ['present', 'absent', 'late', 'excused', 'not_marked'])
                  ->default('not_marked');
            
            // ===== THÔNG TIN CHI TIẾT =====
            $table->time('check_in_time')->nullable();      // Giờ check-in thực tế
            $table->integer('minutes_late')->default(0);    // Số phút đến muộn
            
            // ===== GHI CHÚ & XIN PHÉP =====
            $table->text('note')->nullable();               // Ghi chú
            $table->string('excuse_reason')->nullable();    // Lý do xin phép
            $table->string('excuse_document')->nullable();  // Link file đơn xin phép
            
            // ===== AI ĐIỂM DANH =====
            $table->foreignId('marked_by')
                  ->nullable()
                  ->constrained('lecturer')
                  ->onDelete('set null');
            $table->timestamp('marked_at')->nullable();     // Thời điểm điểm danh
            
            $table->timestamps();
            
            // ===== INDEXES & CONSTRAINTS =====
            $table->unique(['session_id', 'student_id']);   // 1 SV chỉ có 1 record/buổi
            $table->index('student_id');
            $table->index('status');
            $table->index('marked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
