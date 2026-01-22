<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Thêm cột difficulty và rubric cho bảng questions
     */
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->enum('difficulty', ['easy', 'medium', 'hard'])
                ->default('medium')
                ->after('type')
                ->comment('Mức độ khó: easy, medium, hard');
            
            $table->text('rubric')
                ->nullable()
                ->after('explanation')
                ->comment('Tiêu chí chấm điểm cho câu tự luận');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['difficulty', 'rubric']);
        });
    }
};
