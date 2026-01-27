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
        Schema::table('attendance_sessions', function (Blueprint $table) {
            // Thêm cột shift (ca học): sáng, chiều, tối
            $table->enum('shift', ['morning', 'afternoon', 'evening'])
                  ->default('morning')
                  ->after('end_time')
                  ->comment('Ca học: morning (sáng), afternoon (chiều), evening (tối)');
            
            $table->index('shift');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->dropIndex(['shift']);
            $table->dropColumn('shift');
        });
    }
};
