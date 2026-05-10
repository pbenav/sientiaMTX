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
        Schema::create('expedientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by_id')->constrained('users')->onDelete('cascade');
            $table->string('code')->unique()->comment('Reference code, e.g. EXP-2026-001');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['open', 'in_progress', 'blocked', 'closed', 'archived'])->default('open');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expedientes');
    }
};
