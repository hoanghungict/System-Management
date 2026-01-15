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
        Schema::create('notifications', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('title', 255);
            $blueprint->text('content')->nullable();
            $blueprint->string('type', 255);
            $blueprint->string('priority', 255)->default('medium');
            $blueprint->json('data')->nullable();
            $blueprint->unsignedBigInteger('template_id')->nullable();
            $blueprint->unsignedBigInteger('sender_id')->nullable();
            $blueprint->string('sender_type', 255)->nullable();
            $blueprint->timestamp('scheduled_at')->nullable();
            $blueprint->timestamp('sent_at')->nullable();
            $blueprint->string('status', 255)->default('pending');
            $blueprint->timestamps();

            $blueprint->foreign('template_id')->references('id')->on('notification_templates')->onDelete('set null');
            $blueprint->index(['type', 'priority'], 'notifications_type_priority_index');
            $blueprint->index(['status', 'scheduled_at'], 'notifications_status_scheduled_at_index');
            $blueprint->index(['sender_id', 'sender_type'], 'notifications_sender_id_sender_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
