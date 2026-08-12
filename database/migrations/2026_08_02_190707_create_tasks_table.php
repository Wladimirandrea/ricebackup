<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_manager_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->enum('priority', ['baja', 'media', 'alta'])->default('media');
            $table->boolean('completed')->default(false);
            $table->timestamps();

            $table->index(['case_manager_id', 'completed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};