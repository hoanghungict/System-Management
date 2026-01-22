<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Cập nhật bảng questions - thêm question_bank_id, chapter_id, subject_code
     */
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            // Cho phép câu hỏi thuộc ngân hàng câu hỏi (không chỉ assignment)
            $table->unsignedBigInteger('question_bank_id')
                ->nullable()
                ->after('id')
                ->index();
            
            // Chương của câu hỏi
            $table->unsignedBigInteger('chapter_id')
                ->nullable()
                ->after('question_bank_id')
                ->index();
            
            // Mã môn học để dễ filter
            $table->string('subject_code', 50)
                ->nullable()
                ->after('chapter_id')
                ->index();

            // Foreign keys
            $table->foreign('question_bank_id')
                ->references('id')
                ->on('question_banks')
                ->onDelete('set null');
                
            $table->foreign('chapter_id')
                ->references('id')
                ->on('chapters')
                ->onDelete('set null');
        });

        // Cập nhật assignment_id thành nullable (câu hỏi có thể thuộc bank mà không thuộc assignment)
        Schema::table('questions', function (Blueprint $table) {
            $table->unsignedBigInteger('assignment_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['question_bank_id']);
            $table->dropForeign(['chapter_id']);
            $table->dropColumn(['question_bank_id', 'chapter_id', 'subject_code']);
        });
    }
};
