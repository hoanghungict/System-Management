<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lecturer', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 255); // Lecturer full name
            $table->date("birth_date")->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('address', 255)->nullable();
            $table->string('email', 255)->unique();
            $table->string('phone', 20)->nullable();
            $table->integer('experience_number')->default(0);
            $table->string('lecturer_code', 50)->unique();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('assignes_id')->nullable();

            $table->foreign('department_id')
                  ->references('id')->on('department')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
    Schema::dropIfExists('lecturer');
    }
};
