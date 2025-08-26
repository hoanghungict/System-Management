<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class', function (Blueprint $table) {
            $table->id();
            $table->string('class_name');
            $table->string('class_code')->nullable()->unique();
            $table->unsignedBigInteger('faculty_id');
            $table->unsignedBigInteger('lecturer_id')->nullable();
            $table->string('school_year', 20)->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('faculty_id')->references('id')->on('unit')->onDelete('cascade');
            $table->foreign('lecturer_id')->references('id')->on('lecturer')->onDelete('set null');
        });
    }

    public function down(): void
    {
    Schema::dropIfExists('class');
    }
};
