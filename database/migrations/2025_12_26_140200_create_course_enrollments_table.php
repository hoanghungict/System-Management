<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Bảng course_enrollments - Đăng ký môn học của sinh viên
     * Liên kết sinh viên với môn học cụ thể
     */
    public function up(): void
    {
        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            
            // ===== LIÊN KẾT =====
            $table->foreignId('course_id')
                  ->constrained('courses')
                  ->onDelete('cascade');                    // Môn học
                  
            $table->foreignId('student_id')
                  ->constrained('student')
                  ->onDelete('cascade');                    // Sinh viên
            
            // ===== THÔNG TIN ĐĂNG KÝ =====
            $table->date('enrolled_at');                    // Ngày đăng ký
            $table->enum('status', ['active', 'dropped', 'completed', 'failed'])
                  ->default('active');                      // Trạng thái
            
            // ===== GHI CHÚ =====
            $table->text('note')->nullable();               // Ghi chú (VD: đăng ký muộn)
            $table->date('dropped_at')->nullable();         // Ngày hủy đăng ký (nếu có)
            $table->string('drop_reason')->nullable();      // Lý do hủy
            
            $table->timestamps();
            
            // ===== INDEXES & CONSTRAINTS =====
            $table->unique(['course_id', 'student_id']);    // 1 SV chỉ đăng ký 1 lần/môn
            $table->index('student_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_enrollments');
    }
};
