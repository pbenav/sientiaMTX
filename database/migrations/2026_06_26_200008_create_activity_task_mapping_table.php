<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de mapeo entre tasks (legacy) y activities (nueva arquitectura).
     *
     * Esta tabla es la clave de la estrategia de migración sin ruptura:
     * - Cada Task existente mantiene su fila en la tabla `tasks`.
     * - Al migrar, se crea su Activity equivalente y se registra aquí el mapeo.
     * - El modelo Task actúa como wrapper delegando en su Activity asociada.
     * - En el futuro, cuando se elimine la tabla tasks, este mapeo se elimina también.
     */
    public function up(): void
    {
        Schema::create('activity_task_mapping', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('task_id')->unique();
            $table->foreignId('activity_id')->unique()->constrained('activities')->cascadeOnDelete();
            $table->timestamps();

            $table->index('task_id');
            $table->index('activity_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_task_mapping');
    }
};
