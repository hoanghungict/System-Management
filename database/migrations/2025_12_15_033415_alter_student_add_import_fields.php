<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('student', function (Blueprint $table) {
            $table->timestamp('imported_at')->nullable()->after('class_id');
            $table->unsignedBigInteger('import_job_id')->nullable()->after('imported_at');
            $table->enum('account_status', ['active', 'inactive', 'locked'])
                  ->default('inactive')
                  ->after('import_job_id');
            $table->softDeletes();

            // Indexes
            $table->index('import_job_id');
            $table->index('account_status');
            $table->index('deleted_at');

            // Foreign key constraint
            $table->foreign('import_job_id')
                  ->references('id')
                  ->on('import_jobs')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student', function (Blueprint $table) {
            $table->dropForeign(['import_job_id']);
            $table->dropIndex(['import_job_id']);
            $table->dropColumn([
                'imported_at',
                'import_job_id',
                'account_status',
                'deleted_at'
            ]);
        });
    }
};
