<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Bảng exam_submissions - Bài làm của sinh viên
     */
    public function up(): void
    {
        Schema::create('exam_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_id')->index();
            $table->unsignedBigInteger('exam_code_id')->index();
            $table->unsignedBigInteger('student_id')->index();
            
            // Lần làm bài (1 hoặc 2)
            $table->unsignedTinyInteger('attempt')->default(1);
            
            // Thời gian
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            
            // Kết quả
            $table->unsignedInteger('correct_count')->default(0)->comment('Số câu trả lời đúng');
            $table->unsignedInteger('wrong_count')->default(0)->comment('Số câu trả lời sai');
            $table->unsignedInteger('unanswered_count')->default(0)->comment('Số câu chưa trả lời');
            
            // Điểm: correct_count * (10 / total_questions)
            $table->decimal('total_score', 5, 2)->default(0)->comment('Điểm thang 10');
            
            // Điểm do giáo viên sửa (nếu có)
            $table->decimal('manual_score', 5, 2)->nullable()->comment('Điểm giáo viên sửa');
            $table->unsignedBigInteger('graded_by')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->text('grader_note')->nullable();
            
            // Trạng thái
            $table->enum('status', ['in_progress', 'submitted', 'graded'])->default('in_progress');
            
            // Anti-cheat log
            $table->json('anti_cheat_violations')->nullable()->comment('Log các vi phạm');
            
            // Câu trả lời của sinh viên
            // Format: {"question_id": "selected_answer", ...} VD: {"5": "A", "12": "C", ...}
            $table->json('answers')->nullable()->comment('Câu trả lời');
            
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
            $table->foreign('exam_code_id')->references('id')->on('exam_codes')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('student')->onDelete('cascade');
            $table->foreign('graded_by')->references('id')->on('lecturer')->onDelete('set null');
            
            // Unique: mỗi sinh viên chỉ có 1 submission in_progress tại 1 thời điểm
            // Nhưng có thể có nhiều attempts
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_submissions');
    }
};
