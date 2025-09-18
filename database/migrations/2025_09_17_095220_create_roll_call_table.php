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
        Schema::create('roll_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->nullable()->constrained('class')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('date');
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->foreignId('created_by')->constrained('lecturer')->onDelete('cascade');
            $table->timestamps();
            
            // Index để tối ưu truy vấn
            $table->index(['class_id', 'date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roll_calls');
    }
};
