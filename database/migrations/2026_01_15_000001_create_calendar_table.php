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
        Schema::create('calendar', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('title', 255);
            $blueprint->text('description')->nullable();
            $blueprint->dateTime('start_time');
            $blueprint->dateTime('end_time');
            $blueprint->string('event_type', 255)->default('task');
            $blueprint->unsignedBigInteger('task_id')->nullable();
            $blueprint->unsignedBigInteger('creator_id');
            $blueprint->enum('creator_type', ['lecturer', 'admin', 'student']);
            $blueprint->timestamps();

            $blueprint->foreign('task_id')->references('id')->on('task')->onDelete('cascade');
            $blueprint->index(['start_time', 'end_time'], 'calendar_start_time_end_time_index');
            $blueprint->index(['creator_id', 'creator_type'], 'calendar_creator_id_creator_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar');
    }
};
