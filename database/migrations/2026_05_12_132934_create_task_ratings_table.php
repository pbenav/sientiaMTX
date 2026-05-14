<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('task_ratings')) {
            Schema::create('task_ratings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->tinyInteger('score')->comment('1 to 5 quality rating');
                $table->text('comment')->nullable();
                $table->timestamps();

                // Unique key to ensure one vote per user per task
                $table->unique(['task_id', 'user_id']);
            });
        }
        
        // Add dynamic column to tracking total quality score cache on the Task model for faster DB reads
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'avg_quality_score')) {
                $table->decimal('avg_quality_score', 3, 2)->default(0.00)->after('cognitive_load');
            }
            if (!Schema::hasColumn('tasks', 'quality_reward_issued')) {
                $table->boolean('quality_reward_issued')->default(false)->after('avg_quality_score');
            }
        });
    }

    public function down()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['avg_quality_score', 'quality_reward_issued']);
        });
        Schema::dropIfExists('task_ratings');
    }
};
