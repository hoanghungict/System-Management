<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
      Schema::create('student_account', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('student_id');
        $table->string('username', 100)->unique();
        $table->string('password', 255);

        $table->foreign('student_id')
            ->references('id')->on('student')
            ->onDelete('cascade');

        $table->index('student_id');
        });
    }

    public function down(): void
    {
    Schema::dropIfExists('student_account');
    }
};
