<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Bảng questions - Câu hỏi trong bài tập
     */
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id')->index();
            $table->enum('type', ['multiple_choice', 'short_answer', 'essay'])->default('multiple_choice');
            $table->text('content')->comment('Nội dung câu hỏi');
            $table->json('options')->nullable()->comment('Đáp án cho trắc nghiệm: [{"key":"A","text":"..."},...]');
            $table->text('correct_answer')->nullable()->comment('Đáp án đúng: "A" hoặc keyword');
            $table->decimal('points', 5, 2)->default(1)->comment('Điểm của câu hỏi');
            $table->unsignedInteger('order_index')->default(0);
            $table->text('explanation')->nullable()->comment('Giải thích đáp án');
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('assignment_id')->references('id')->on('assignments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
