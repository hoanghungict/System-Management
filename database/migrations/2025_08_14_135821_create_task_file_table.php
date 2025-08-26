<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
      Schema::create('task_file', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('task_id');
        $table->string('file_path', 500); // File path

        $table->foreign('task_id')
            ->references('id')->on('task')
            ->onDelete('cascade');

        $table->index('task_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_file');
    }
};
