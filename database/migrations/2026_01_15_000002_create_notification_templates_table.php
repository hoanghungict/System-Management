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
        Schema::create('notification_templates', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('name', 255)->unique();
            $blueprint->string('title', 255)->nullable();
            $blueprint->string('subject', 255)->nullable();
            $blueprint->text('email_template')->nullable();
            $blueprint->text('sms_template')->nullable();
            $blueprint->text('push_template')->nullable();
            $blueprint->text('in_app_template')->nullable();
            $blueprint->json('channels');
            $blueprint->string('priority', 255)->default('medium');
            $blueprint->string('category', 255)->nullable();
            $blueprint->text('description')->nullable();
            $blueprint->boolean('is_active')->default(true);
            $blueprint->timestamps();

            $blueprint->index(['name', 'is_active'], 'notification_templates_name_is_active_index');
            $blueprint->index(['category', 'is_active'], 'notification_templates_category_is_active_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
