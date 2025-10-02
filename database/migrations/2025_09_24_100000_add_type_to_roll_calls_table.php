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
        Schema::table('roll_calls', function (Blueprint $table) {
            // Thêm cột type cho roll call
            $table->enum('type', ['class_based', 'manual'])->default('class_based')->after('class_id');
            
            // Thêm cột để lưu số lượng sinh viên dự kiến (cho manual type)
            $table->integer('expected_participants')->nullable()->after('type');
            
            // Thêm metadata để lưu thông tin bổ sung (JSON)
            $table->json('metadata')->nullable()->after('expected_participants');
            
            // Update index để bao gồm type
            $table->index(['type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roll_calls', function (Blueprint $table) {
            $table->dropIndex(['type', 'status']);
            $table->dropColumn(['type', 'expected_participants', 'metadata']);
        });
    }
};
