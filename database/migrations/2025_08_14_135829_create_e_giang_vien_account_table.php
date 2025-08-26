<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
      Schema::create('lecturer_account', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('lecturer_id');
        $table->string('username', 100)->unique();
        $table->string('password', 255);
        $table->tinyInteger('is_admin')->default(0); // 0: not admin, 1: is admin
        $table->foreign('lecturer_id')
            ->references('id')->on('lecturer')
            ->onDelete('cascade');

        $table->index('lecturer_id');
        });
    }

    public function down(): void
    {
    Schema::dropIfExists('lecturer_account');
    }
};
