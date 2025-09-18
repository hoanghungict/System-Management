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
        Schema::table('task', function (Blueprint $table) {
            // Chỉ thêm các column chưa tồn tại
            if (!Schema::hasColumn('task', 'title')) {
                $table->string('title')->nullable();
            }
            if (!Schema::hasColumn('task', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('task', 'priority')) {
                $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            }
            if (!Schema::hasColumn('task', 'due_date')) {
                $table->date('due_date')->nullable();
            }
            if (!Schema::hasColumn('task', 'deadline')) {
                $table->datetime('deadline')->nullable();
            }
            if (!Schema::hasColumn('task', 'status')) {
                $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            }
            if (!Schema::hasColumn('task', 'creator_id')) {
                $table->unsignedBigInteger('creator_id')->nullable();
            }
            if (!Schema::hasColumn('task', 'creator_type')) {
                $table->enum('creator_type', ['lecturer', 'admin'])->nullable();
            }
            
            // Thêm indexes nếu chưa có
            $table->index(['creator_id', 'creator_type']);
            $table->index(['status']);
            $table->index(['due_date']);
            $table->index(['deadline']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task', function (Blueprint $table) {
            $table->dropColumn([
                'title', 'description', 'priority', 'due_date', 
                'deadline', 'status', 'creator_id', 'creator_type'
            ]);
        });
    }
};
