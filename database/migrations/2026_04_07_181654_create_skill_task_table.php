<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('skill_task')) {
            Schema::create('skill_task', function (Blueprint $table) {
                $table->id();
                $table->foreignId('skill_id')->constrained()->onDelete('cascade');
                $table->foreignId('task_id')->constrained()->onDelete('cascade');
                $table->unique(['skill_id', 'task_id']);
                $table->timestamps();
            });
        }

        // Data migration: move current skill_id to the pivot table
        $tasksWithSkills = DB::table('tasks')->whereNotNull('skill_id')->select('id', 'skill_id', 'created_at', 'updated_at')->get();
        foreach($tasksWithSkills as $task) {
            DB::table('skill_task')->insertOrIgnore([
                'skill_id' => $task->skill_id,
                'task_id' => $task->id,
                'created_at' => $task->created_at,
                'updated_at' => $task->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_task');
    }
};
