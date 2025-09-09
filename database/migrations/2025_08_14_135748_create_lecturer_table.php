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
        Schema::create('lecturer', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('address')->nullable();
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable();
            $table->integer('experience_number')->default(0);
            $table->string('lecturer_code', 50)->unique();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('assigned_id')->nullable();
            
            $table->foreign('department_id')->references('id')->on('department')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lecturer');
    }
};
