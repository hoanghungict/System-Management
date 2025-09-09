<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('task_submissions')) {
            Schema::create('task_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('student_id');
            $table->text('submission_content')->nullable();
            $table->json('submission_files')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->enum('status', ['pending', 'submitted', 'graded', 'overdue'])->default('pending');
            $table->decimal('grade', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->unsignedBigInteger('graded_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('graded_by')->references('id')->on('lecturers')->onDelete('set null');

            // Indexes
            $table->index(['task_id', 'student_id']);
            $table->index(['student_id', 'status']);
            $table->index(['task_id', 'status']);
            $table->index('submitted_at');
            $table->index('graded_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_submissions');
    }
};