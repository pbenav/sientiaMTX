<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('metric_alerts')) {
            return;
        }
        Schema::create('metric_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('category', ['wellness', 'productivity', 'appointments', 'surveys', 'security', 'team'])->index();
            $table->enum('severity', ['info', 'low', 'medium', 'high', 'critical'])->default('info');
            $table->string('code')->index();
            $table->string('title');
            $table->text('message')->nullable();
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('action_url')->nullable();
            $table->timestamps();

            $table->index(['category', 'severity', 'is_read']);
            $table->index(['user_id', 'is_read', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metric_alerts');
    }
};
