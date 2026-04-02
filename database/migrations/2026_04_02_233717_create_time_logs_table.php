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
        Schema::create('time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('task_id')->nullable()->constrained()->onDelete('cascade');
            
            $table->enum('type', ['workday', 'task'])->default('task');
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            
            $table->string('note')->nullable();
            $table->timestamps();

            // Indexes for faster reporting
            $table->index(['user_id', 'type']);
            $table->index(['task_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_logs');
    }
};
