<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Bảng chapters - Chương trong môn học
     */
    public function up(): void
    {
        Schema::create('chapters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_bank_id')->index();
            $table->string('name'); // VD: "Chương 1: Giới thiệu TCP/IP"
            $table->string('code', 50)->nullable(); // VD: "UMH45_1"
            $table->unsignedInteger('order_index')->default(0);
            $table->timestamps();

            // Foreign keys
            $table->foreign('question_bank_id')->references('id')->on('question_banks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chapters');
    }
};
