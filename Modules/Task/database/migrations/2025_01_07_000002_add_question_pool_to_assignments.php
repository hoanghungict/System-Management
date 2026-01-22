<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Thêm cấu hình Question Pool cho Assignment (random đề thi)
     */
    public function up(): void
    {
        // 1. Thêm cấu hình question pool vào assignments
        Schema::table('assignments', function (Blueprint $table) {
            $table->boolean('question_pool_enabled')
                ->default(false)
                ->after('shuffle_options')
                ->comment('Bật chế độ random đề thi từ ngân hàng câu hỏi');
                
            $table->json('question_pool_config')
                ->nullable()
                ->after('question_pool_enabled')
                ->comment('Cấu hình số câu theo độ khó: {"easy": 10, "medium": 30, "hard": 10, "total": 50}');
        });
        
        // 2. Tạo bảng lưu câu hỏi đã random cho mỗi submission
        Schema::create('submission_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('submission_id')->index();
            $table->unsignedBigInteger('question_id')->index();
            $table->unsignedInteger('order_index')->default(0)->comment('Thứ tự hiển thị câu hỏi');
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('submission_id')
                ->references('id')
                ->on('assignment_submissions')
                ->onDelete('cascade');
                
            $table->foreign('question_id')
                ->references('id')
                ->on('questions')
                ->onDelete('cascade');
                
            // Unique constraint: mỗi câu hỏi chỉ xuất hiện 1 lần trong 1 submission
            $table->unique(['submission_id', 'question_id'], 'submission_question_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submission_questions');
        
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn(['question_pool_enabled', 'question_pool_config']);
        });
    }
};
