<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('type');
            $table->string('priority')->default('medium');
            $table->json('data')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->string('sender_type')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            
            $table->foreign('template_id')->references('id')->on('notification_templates')->onDelete('set null');
            $table->index(['type', 'priority']);
            $table->index(['status', 'scheduled_at']);
            $table->index(['sender_id', 'sender_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
