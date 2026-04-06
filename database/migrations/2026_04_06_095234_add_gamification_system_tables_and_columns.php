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
        // 1. Skills Table (The "Skill Tree")
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category')->default('General'); // Pedagogía, Soporte Técnico, Administración, Gestión Emocional
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        // 2. User Skills Pivot
        Schema::create('user_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('skill_id')->constrained()->onDelete('cascade');
            $table->integer('level')->default(1); // 1-5
            $table->timestamps();
        });

        // 3. Kudos (Social Economy)
        Schema::create('kudos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('to_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('task_id')->nullable()->constrained()->onDelete('set null');
            $table->string('message')->nullable();
            $table->timestamps();
        });

        // 4. Gamification Logs (History of effort recognition)
        Schema::create('gamification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->integer('points');
            $table->string('type'); // resilience, task, backstage, kudo
            $table->string('source_type')->nullable(); // App\Models\Task, etc.
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // 5. Alter Tasks Table
        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('is_out_of_skill_tree')->default(false);
            $table->integer('cognitive_load')->default(1); // 1-5 (Semáforo de bienestar)
            $table->boolean('is_backstage')->default(false); // Registro de "Backstage" (estudio/preparación)
            $table->integer('impact_human_metric')->nullable(); // Traducir a impacto humano (ej: horas ahorradas)
        });

        // 6. Alter Users Table
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('resilience_points')->default(0);
            $table->unsignedBigInteger('experience_points')->default(0);
            $table->integer('energy_level')->default(100); // 0-100
        });

        // Seed some initial skills
        $initialSkills = [
            ['name' => 'Pedagogía', 'slug' => 'pedagogia', 'category' => 'Social'],
            ['name' => 'Soporte Técnico', 'slug' => 'soporte-tecnico', 'category' => 'Tecnología'],
            ['name' => 'Administración', 'slug' => 'administracion', 'category' => 'Gestión'],
            ['name' => 'Gestión Emocional', 'slug' => 'gestion-emocional', 'category' => 'Social'],
            ['name' => 'Impacto Rural', 'slug' => 'impacto-rural', 'category' => 'Territorio'],
        ];

        foreach ($initialSkills as $skill) {
            \Illuminate\Support\Facades\DB::table('skills')->insert(array_merge($skill, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['resilience_points', 'experience_points', 'energy_level']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['is_out_of_skill_tree', 'cognitive_load', 'is_backstage', 'impact_human_metric']);
        });

        Schema::dropIfExists('gamification_logs');
        Schema::dropIfExists('kudos');
        Schema::dropIfExists('user_skills');
        Schema::dropIfExists('skills');
    }
};
