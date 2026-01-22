<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Bảng extension_requests - Yêu cầu gia hạn deadline
     */
    public function up(): void
    {
        Schema::create('extension_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id')->index();
            $table->unsignedBigInteger('student_id')->index();
            $table->text('reason')->comment('Lý do xin gia hạn');
            $table->timestamp('new_deadline')->comment('Deadline mới đề xuất');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reviewer_note')->nullable()->comment('Ghi chú của GV');
            $table->timestamps();

            // Foreign keys
            $table->foreign('assignment_id')->references('id')->on('assignments')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('student')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('lecturer')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extension_requests');
    }
};
