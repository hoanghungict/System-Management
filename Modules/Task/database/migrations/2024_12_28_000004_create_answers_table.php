<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Bảng answers - Câu trả lời của sinh viên
     */
    public function up(): void
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('submission_id')->index();
            $table->unsignedBigInteger('question_id')->index();
            $table->text('answer_text')->nullable()->comment('Câu TL tự luận hoặc đáp án chọn');
            $table->boolean('is_correct')->nullable()->comment('Đúng/sai (auto-check)');
            $table->decimal('score', 5, 2)->nullable()->comment('Điểm câu này');
            $table->text('feedback')->nullable()->comment('Nhận xét từng câu');
            $table->timestamps();

            // Foreign keys
            $table->foreign('submission_id')->references('id')->on('assignment_submissions')->onDelete('cascade');
            $table->foreign('question_id')->references('id')->on('questions')->onDelete('cascade');

            // Unique: 1 câu TL cho mỗi câu hỏi trong submission
            $table->unique(['submission_id', 'question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
