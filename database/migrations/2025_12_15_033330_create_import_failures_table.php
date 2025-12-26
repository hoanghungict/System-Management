<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('import_failures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_job_id')
                  ->constrained('import_jobs')
                  ->cascadeOnDelete();
            $table->integer('row_number');
            $table->string('attribute', 100)->nullable();
            $table->text('errors')->nullable();
            $table->json('values')->nullable();
            $table->timestamp('created_at')->nullable();

            // Indexes for better query performance
            $table->index('import_job_id');
            $table->index('row_number');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_failures');
    }
};
