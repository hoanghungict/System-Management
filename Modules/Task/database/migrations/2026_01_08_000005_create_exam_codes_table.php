<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Bảng exam_codes - Mã đề thi với thứ tự câu hỏi đã random
     */
    public function up(): void
    {
        Schema::create('exam_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_id')->index();
            
            $table->string('code', 10); // VD: "001", "002", "A", "B"
            
            // Danh sách question_id đã được random và sắp xếp
            // VD: [5, 12, 3, 7, 18, ...] - thứ tự câu hỏi trong mã đề này
            $table->json('question_order')->comment('Mảng question_id theo thứ tự');
            
            // Bản đồ xáo trộn đáp án cho mỗi câu hỏi
            // VD: {"5": {"A":"C", "B":"A", "C":"D", "D":"B"}, ...}
            $table->json('option_shuffle_map')->nullable()->comment('Map xáo trộn đáp án');
            
            $table->timestamps();

            // Foreign keys
            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
            
            // Unique: mỗi exam có mã đề duy nhất
            $table->unique(['exam_id', 'code'], 'exam_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_codes');
    }
};
