<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
      Schema::create('student', function (Blueprint $table) {
        $table->id();
        $table->string('full_name', 255); // Student full name
        $table->date('birth_date')->nullable();
        $table->enum('gender', ['male', 'female', 'other'])->nullable();
        $table->string('address', 255)->nullable();
        $table->string('email', 255)->unique();
        $table->string('phone', 20)->nullable();
        $table->string('student_code', 50)->unique();
        $table->unsignedBigInteger('enrolled_id')->nullable();
        // Link to class table
        $table->unsignedBigInteger('class_id')->nullable();
        $table->foreign('class_id')
            ->references('id')->on('class')
            ->onDelete('set null');

        $table->timestamps();
        });
    }

    public function down(): void
    {
    Schema::dropIfExists('student');
    }
};
