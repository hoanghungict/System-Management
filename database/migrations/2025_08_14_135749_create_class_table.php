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
        Schema::create('class', function (Blueprint $table) {
            $table->id();
            $table->string('class_name');
            $table->string('class_code')->unique()->nullable();
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('lecturer_id')->nullable();
            $table->string('school_year', 20)->nullable();
            $table->timestamps();
            
            $table->foreign('department_id')->references('id')->on('department')->onDelete('cascade');
            $table->foreign('lecturer_id')->references('id')->on('lecturer')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class');
    }
};
