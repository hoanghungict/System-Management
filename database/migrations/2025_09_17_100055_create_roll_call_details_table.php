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
        Schema::create('roll_call_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roll_call_id')->constrained('roll_calls')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('student')->onDelete('cascade');
            $table->enum('status', ['Có Mặt', 'Vắng Mặt', 'Có Phép', 'Muộn'])->default('Có Mặt');
            $table->text('note')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
            
            // Index để tối ưu truy vấn
            $table->index(['roll_call_id', 'student_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roll_call_details');
    }
};
