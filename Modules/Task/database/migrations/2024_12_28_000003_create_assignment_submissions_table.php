<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Bảng assignment_submissions - Bài làm của sinh viên
     */
    public function up(): void
    {
        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id')->index();
            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedInteger('attempt')->default(1)->comment('Lần làm thứ mấy');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('auto_score', 5, 2)->nullable()->comment('Điểm tự động chấm');
            $table->decimal('manual_score', 5, 2)->nullable()->comment('Điểm GV chấm');
            $table->decimal('total_score', 5, 2)->nullable()->comment('Tổng điểm');
            $table->enum('status', ['in_progress', 'submitted', 'graded'])->default('in_progress');
            $table->unsignedBigInteger('graded_by')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->text('feedback')->nullable()->comment('Nhận xét của GV');
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('assignment_id')->references('id')->on('assignments')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('student')->onDelete('cascade');
            $table->foreign('graded_by')->references('id')->on('lecturer')->onDelete('set null');

            // Unique: 1 SV chỉ có 1 bài làm cho mỗi attempt
            $table->unique(['assignment_id', 'student_id', 'attempt']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
    }
};
