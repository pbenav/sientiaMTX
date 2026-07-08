<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('metric_reports')) {
            return;
        }
        Schema::create('metric_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('type', ['daily_personal', 'weekly_personal', 'weekly_manager', 'monthly_executive', 'wellness_team', 'appointments', 'surveys', 'gamification'])->index();
            $table->string('period_label');
            $table->date('period_start');
            $table->date('period_end');
            $table->json('summary');
            $table->json('charts_data')->nullable();
            $table->json('insights')->nullable();
            $table->enum('status', ['pending', 'generating', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->string('format', 10)->default('json');
            $table->string('file_path')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metric_reports');
    }
};
