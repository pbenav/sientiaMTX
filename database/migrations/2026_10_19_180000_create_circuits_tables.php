<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

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
        // 1. Plantillas de circuito (definición reutilizable)
        Schema::create('circuit_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('target_type', 20)->default('both')->comment('activity, expediente, both');
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'is_active'], 'circuit_tmpl_team_active_idx');
        });

        // 2. Pasos o etapas de la plantilla de circuito
        Schema::create('circuit_template_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('circuit_template_id')->constrained('circuit_templates')->cascadeOnDelete();
            $table->unsignedSmallInteger('order')->default(1);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('action_type', 30)->default('approval')->comment('endorsement, justification, approval, publication');
            $table->string('assignee_type', 30)->default('specific_user')->comment('specific_user, team_role, activity_responsible, creator, dynamic');
            $table->unsignedBigInteger('assignee_id')->nullable()->comment('ID del usuario o del rol segun assignee_type');
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('allow_return')->default(true)->comment('Permite devolver para subsanacion');
            $table->json('action_config')->nullable();
            $table->timestamps();

            $table->index(['circuit_template_id', 'order'], 'circuit_tmpl_steps_order_idx');
        });

        // 3. Ejecuciones/Instancias activas de tramitación
        Schema::create('circuit_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('circuit_template_id')->nullable()->constrained('circuit_templates')->nullOnDelete();
            $table->string('circuitable_type');
            $table->unsignedBigInteger('circuitable_id');
            $table->string('title');
            $table->foreignId('initiated_by_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('current_step_id')->nullable();
            $table->string('status', 30)->default('in_progress')->comment('draft, in_progress, approved, rejected, returned, cancelled, published');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['circuitable_type', 'circuitable_id'], 'circuit_exec_circuitable_idx');
            $table->index(['team_id', 'status'], 'circuit_exec_team_status_idx');
            $table->index(['initiated_by_id', 'status'], 'circuit_exec_user_status_idx');
        });

        // 4. Pasos concretos de la tramitación en curso
        Schema::create('circuit_execution_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('circuit_execution_id')->constrained('circuit_executions')->cascadeOnDelete();
            $table->unsignedBigInteger('template_step_id')->nullable();
            $table->unsignedSmallInteger('order')->default(1);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('action_type', 30)->default('approval');
            $table->string('assignee_type', 30)->default('specific_user');
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to_role_id')->nullable()->constrained('team_roles')->nullOnDelete();
            $table->string('status', 30)->default('pending')->comment('pending, in_progress, approved, rejected, returned, skipped');
            $table->foreignId('action_taken_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision', 30)->nullable()->comment('approved, rejected, returned, skipped');
            $table->text('comments')->nullable()->comment('Observaciones, justificacion o motivo');
            $table->json('action_data')->nullable()->comment('IP, user_agent, hash, etc.');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['circuit_execution_id', 'order'], 'circuit_exec_steps_order_idx');
            $table->index(['assigned_to_user_id', 'status'], 'circuit_exec_steps_user_status_idx');
            $table->index(['assigned_to_role_id', 'status'], 'circuit_exec_steps_role_status_idx');
        });

        // 5. Publicación en Tablón Edictal / Portal Ciudadano
        Schema::create('circuit_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('circuit_execution_id')->constrained('circuit_executions')->cascadeOnDelete();
            $table->foreignId('published_by_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('document_code')->index();
            $table->text('summary')->nullable();
            $table->string('channel', 30)->default('internal_board')->comment('internal_board, microsite, external_api');
            $table->string('status', 30)->default('published')->comment('draft, published, expired, withdrawn');
            $table->string('public_slug')->unique();
            $table->string('public_token', 64)->nullable()->index();
            $table->date('publication_date');
            $table->date('expiration_date')->nullable();
            $table->longText('content_html')->nullable();
            $table->json('attachments')->nullable();
            $table->string('external_reference_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status'], 'circuit_pub_team_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circuit_publications');
        Schema::dropIfExists('circuit_execution_steps');
        Schema::dropIfExists('circuit_executions');
        Schema::dropIfExists('circuit_template_steps');
        Schema::dropIfExists('circuit_templates');
    }
};

