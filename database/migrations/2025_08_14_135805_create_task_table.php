<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('task', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255); // Task title
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unsignedBigInteger('receiver_id');
            $table->enum('receiver_type', ['lecturer', 'student']);

            $table->unsignedBigInteger('creator_id');
            $table->enum('creator_type', ['lecturer', 'student']);

            // Index for fast query
            $table->index(['receiver_type', 'receiver_id']);
            $table->index(['creator_type', 'creator_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task');
    }
};
