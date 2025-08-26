<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('department', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255); // Unit name
            $table->enum('type', ['school', 'faculty', 'department']); 
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->unsignedBigInteger('parent_id')->nullable();

            // Self reference (recursive)
            $table->foreign('parent_id')
                  ->references('id')->on('unit')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
    Schema::dropIfExists('unit');
    }
};
