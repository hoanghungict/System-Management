<?php

declare(strict_types=1);

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
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('task')->onDelete('cascade');
            $table->unsignedBigInteger('user_id'); // No foreign key constraint
            $table->enum('user_type', ['student', 'lecturer', 'admin']);
            $table->enum('reminder_type', ['email', 'push', 'sms', 'in_app']);
            $table->datetime('reminder_time');
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed', 'cancelled'])->default('pending');
            $table->datetime('sent_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['user_id', 'user_type']);
            $table->index(['task_id', 'status']);
            $table->index(['reminder_time', 'status']);
            $table->index(['reminder_type', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
