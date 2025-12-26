<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Bảng holidays - Ngày nghỉ lễ
     * Dùng để loại trừ khi tự động tạo lịch học
     */
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            
            $table->string('name', 100);                    // "Quốc khánh 2/9"
            $table->date('date');                           // 2024-09-02
            $table->boolean('is_recurring')->default(false);// Có lặp lại hàng năm không
            $table->text('description')->nullable();        // Mô tả
            
            $table->timestamps();
            
            // Index
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
