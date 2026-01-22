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
        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('predecessor_task_id')->comment('Task mà dependency này phụ thuộc vào');
            $table->unsignedBigInteger('successor_task_id')->comment('Task hiện tại');
            $table->enum('dependency_type', [
                'finish_to_start',
                'start_to_start',
                'finish_to_finish',
                'start_to_finish'
            ])->default('finish_to_start')->comment('Loại dependency');
            $table->integer('lag_days')->default(0)->comment('Số ngày delay');
            $table->json('metadata')->nullable()->comment('Thông tin bổ sung');
            $table->unsignedBigInteger('created_by')->nullable()->comment('Người tạo');
            $table->string('created_by_type')->nullable()->comment('Loại người tạo (admin, lecturer, student)');
            $table->timestamps();

            // Foreign keys
            $table->foreign('predecessor_task_id')->references('id')->on('task')->onDelete('cascade');
            $table->foreign('successor_task_id')->references('id')->on('task')->onDelete('cascade');
            // $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['predecessor_task_id', 'successor_task_id'], 'idx_task_dependency_pair');
            $table->index('dependency_type');
            $table->index('created_by');
            $table->index('created_at');

            // Unique constraint để tránh duplicate dependencies
            $table->unique(['predecessor_task_id', 'successor_task_id'], 'unique_task_dependency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_dependencies');
    }
};