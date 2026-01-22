<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Bảng question_banks - Ngân hàng câu hỏi theo môn học
     */
    public function up(): void
    {
        Schema::create('question_banks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id')->nullable()->index();
            $table->unsignedBigInteger('lecturer_id')->index();
            $table->string('name'); // VD: "Ngân hàng câu hỏi Mạng máy tính"
            $table->text('description')->nullable();
            $table->string('subject_code', 50)->nullable()->index(); // VD: "UMH45"
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedBigInteger('material_id')->nullable(); // Link to Materials
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
        Schema::dropIfExists('question_banks');
    }
};
