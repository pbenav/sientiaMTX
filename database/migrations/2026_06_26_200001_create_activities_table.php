<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            // Identificadores
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();

            // Contexto
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('created_by_id')->constrained('users');
            $table->unsignedBigInteger('parent_id')->nullable();   // auto-referencia
            $table->foreignId('expediente_id')->nullable()->constrained('expedientes')->nullOnDelete();

            // Tipo (STI — Single Table Inheritance)
            // Valores: task, document, note, link, decision, meeting, reminder, custom
            $table->string('type', 50)->index();

            // Contenido base
            $table->string('title', 255);
            $table->longText('description')->nullable();

            // Estado flexible por tipo (JSON)
            // Para 'task': {"value": "pending"} | "in_progress" | "completed" | "cancelled" | "blocked"
            // Para 'meeting': {"value": "scheduled"} | "in_progress" | "completed" | "cancelled"
            // Para 'reminder': {"value": "pending"} | "triggered" | "dismissed"
            // Para 'document': {"value": "draft"} | "review" | "approved" | "archived"
            $table->json('status')->nullable();

            // Metadatos específicos por tipo (JSON extensible)
            $table->json('metadata')->nullable();

            // Visibilidad
            $table->enum('visibility', ['public', 'private', 'semi-private'])->default('private')->index();

            // Temporalización (disponible para TODOS los tipos)
            $table->dateTime('due_date')->nullable()->index();
            $table->dateTime('scheduled_date')->nullable()->index();
            $table->dateTime('original_due_date')->nullable();

            // Prioridad y progreso (disponibles para todos)
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium')->index();
            $table->boolean('auto_priority')->default(false);
            $table->unsignedTinyInteger('progress_percentage')->default(0);

            // Vistas de organización (Kanban, Matrix, Gantt)
            $table->foreignId('kanban_column_id')->nullable()->constrained('kanban_columns')->nullOnDelete();
            $table->unsignedInteger('kanban_order')->nullable();
            $table->unsignedInteger('matrix_order')->nullable();

            // Flags de estado
            $table->boolean('is_archived')->default(false)->index();
            $table->boolean('is_template')->default(false);

            // Google Sync (principalmente para task y meeting)
            $table->string('google_task_id')->nullable();
            $table->string('google_task_list_id')->nullable();
            $table->string('google_calendar_event_id')->nullable();
            $table->string('google_calendar_id')->nullable();
            $table->dateTime('google_synced_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices compuestos
            $table->index(['team_id', 'type']);
            $table->index(['team_id', 'is_archived']);
            $table->index(['expediente_id', 'type']);
            $table->index(['parent_id']);
            $table->index(['created_by_id']);
            $table->index(['kanban_column_id', 'kanban_order']);
        });

        // FK auto-referencia (no se puede hacer inline porque la tabla debe existir antes)
        Schema::table('activities', function (Blueprint $table) {
            $table->foreign('parent_id')
                ->references('id')
                ->on('activities')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
        });
        Schema::dropIfExists('activities');
    }
};
