<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
      Schema::create('calendar', function (Blueprint $table) {
        $table->id();
        $table->string('title', 255); // Event title
        $table->text('description')->nullable();
        $table->dateTime('start_time');
        $table->dateTime('end_time');

        $table->enum('event_type', ['task', 'event']);
        $table->unsignedBigInteger('task_id')->nullable();

        $table->unsignedBigInteger('participant_id');
        $table->enum('participant_type', ['lecturer', 'student']);

        $table->unsignedBigInteger('creator_id');
        $table->enum('creator_type', ['lecturer', 'student']);

        $table->foreign('task_id')
            ->references('id')->on('task')
            ->onDelete('set null');

        // Indexes for weekly calendar & user filter
        $table->index(['participant_type', 'participant_id']);
        $table->index('start_time');
        $table->index('task_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar');
    }
};
