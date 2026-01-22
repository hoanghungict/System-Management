<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Bảng question_import_logs - Tracking import câu hỏi
     */
    public function up(): void
    {
        Schema::create('question_import_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id')->index();
            $table->string('file_name');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->enum('status', ['processing', 'completed', 'failed'])->default('processing');
            $table->json('error_details')->nullable()->comment('Chi tiết lỗi: [{row, error, data}]');
            $table->unsignedBigInteger('imported_by')->index();
            $table->timestamps();

            // Foreign keys
            $table->foreign('assignment_id')->references('id')->on('assignments')->onDelete('cascade');
            $table->foreign('imported_by')->references('id')->on('lecturer')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_import_logs');
    }
};
