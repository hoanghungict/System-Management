<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Bảng exams - Đề thi (tách riêng khỏi assignment)
     */
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_bank_id')->index();
            $table->unsignedBigInteger('course_id')->nullable()->index();
            $table->unsignedBigInteger('lecturer_id')->index();
            
            $table->string('title');
            $table->text('description')->nullable();
            
            // Cấu hình thời gian và số câu
            $table->unsignedInteger('time_limit')->comment('Thời gian làm bài (phút)');
            $table->unsignedInteger('total_questions')->comment('Tổng số câu trong đề');
            
            // Số lần làm bài tối đa (mặc định 2 cho thi)
            $table->unsignedInteger('max_attempts')->default(2);
            
            // Cấu hình độ khó: {"easy": 30, "medium": 20, "hard": 10}
            $table->json('difficulty_config')->nullable()->comment('Tỉ lệ độ khó: easy, medium, hard');
            
            // Số mã đề cần tạo
            $table->unsignedInteger('exam_codes_count')->default(4)->comment('Số mã đề');
            
            // Cấu hình hiển thị
            $table->boolean('show_answers_after_submit')->default(true);
            $table->boolean('shuffle_questions')->default(true);
            $table->boolean('shuffle_options')->default(true);
            
            // Anti-cheat
            $table->boolean('anti_cheat_enabled')->default(true);
            
            // Trạng thái
            $table->enum('status', ['draft', 'published', 'closed'])->default('draft');
            
            // Thời gian mở/đóng thi
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('question_bank_id')->references('id')->on('question_banks')->onDelete('cascade');
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('set null');
            $table->foreign('lecturer_id')->references('id')->on('lecturer')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
