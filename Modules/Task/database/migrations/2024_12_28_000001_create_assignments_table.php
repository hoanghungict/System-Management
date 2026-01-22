<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Bảng assignments - Bài tập/Đề bài
     */
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id')->nullable()->index();
            $table->unsignedBigInteger('lecturer_id')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['quiz', 'essay', 'mixed'])->default('mixed');
            $table->timestamp('deadline')->nullable();
            $table->unsignedInteger('time_limit')->nullable()->comment('Minutes');
            $table->unsignedInteger('max_attempts')->default(1);
            $table->boolean('show_answers')->default(true)->comment('Show answers after submit');
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('shuffle_options')->default(false);
            $table->enum('status', ['draft', 'published', 'closed'])->default('draft');
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('set null');
            $table->foreign('lecturer_id')->references('id')->on('lecturer')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
