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
        Schema::create('student', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('address')->nullable();
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable();
            $table->string('student_code', 50)->unique();
            $table->unsignedBigInteger('enrolled_id')->nullable();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->timestamps();
            
            $table->foreign('class_id')->references('id')->on('class')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student');
    }
};
