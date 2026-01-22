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
        Schema::create('user_notifications', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->unsignedBigInteger('user_id');
            $blueprint->string('user_type', 255);
            $blueprint->unsignedBigInteger('notification_id');
            $blueprint->boolean('is_read')->default(false);
            $blueprint->timestamp('read_at')->nullable();
            $blueprint->boolean('email_sent')->default(false);
            $blueprint->timestamp('email_sent_at')->nullable();
            $blueprint->boolean('push_sent')->default(false);
            $blueprint->timestamp('push_sent_at')->nullable();
            $blueprint->boolean('sms_sent')->default(false);
            $blueprint->timestamp('sms_sent_at')->nullable();
            $blueprint->boolean('in_app_sent')->default(false);
            $blueprint->timestamp('in_app_sent_at')->nullable();
            $blueprint->timestamps();

            $blueprint->foreign('notification_id')->references('id')->on('notifications')->onDelete('cascade');
            $blueprint->index(['user_id', 'user_type'], 'user_notifications_user_id_user_type_index');
            $blueprint->index('notification_id', 'user_notifications_notification_id_index');
            $blueprint->index('is_read', 'user_notifications_is_read_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
