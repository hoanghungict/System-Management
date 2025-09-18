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
        Schema::create('task_receivers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('receiver_id');
            $table->enum('receiver_type', ['lecturer', 'student', 'all_students', 'all_lecturers', 'classes', 'department'])->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->foreign('task_id')->references('id')->on('task')->onDelete('cascade');
            $table->index(['receiver_type', 'receiver_id']);
            $table->index(['task_id', 'receiver_type']);
            $table->index(['receiver_id', 'receiver_type', 'task_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_receivers');
    }
};
