<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('metric_snapshots')) {
            return;
        }
        Schema::create('metric_snapshots', function (Blueprint $table) {
            $table->id();
            $table->uuid()->index();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('type', ['daily_user', 'weekly_team', 'daily_team', 'wellness', 'productivity', 'engagement', 'gamification', 'appointments', 'executive'])->index();
            $table->date('snapshot_date');
            $table->json('metrics');
            $table->json('trends')->nullable();
            $table->json('alerts')->nullable();
            $table->decimal('wellness_score', 5, 2)->nullable();
            $table->decimal('productivity_score', 5, 2)->nullable();
            $table->decimal('engagement_score', 5, 2)->nullable();
            $table->integer('mood_index')->nullable();
            $table->integer('stress_index')->nullable();
            $table->integer('energy_index')->nullable();
            $table->integer('satisfaction_index')->nullable();
            $table->integer('burnout_risk_score')->nullable();
            $table->enum('burnout_risk_level', ['low', 'medium', 'high'])->nullable();
            $table->integer('activities_completed')->default(0);
            $table->integer('activities_in_progress')->default(0);
            $table->integer('activities_pending')->default(0);
            $table->integer('activities_overdue')->default(0);
            $table->decimal('completion_rate', 5, 2)->default(0);
            $table->decimal('on_time_delivery', 5, 2)->default(0);
            $table->decimal('estimation_accuracy', 5, 2)->default(0);
            $table->decimal('hours_logged', 8, 2)->default(0);
            $table->decimal('hours_overtime', 8, 2)->default(0);
            $table->integer('streak_days')->default(0);
            $table->decimal('team_wellness_avg', 5, 2)->nullable();
            $table->integer('team_size')->default(0);
            $table->integer('members_overloaded')->default(0);
            $table->integer('members_underloaded')->default(0);
            $table->integer('nudge_responsiveness')->default(0);
            $table->decimal('work_life_balance_index', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['uuid', 'type', 'snapshot_date', 'user_id', 'team_id'], 'unique_snapshot');
            $table->index(['type', 'snapshot_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metric_snapshots');
    }
};
