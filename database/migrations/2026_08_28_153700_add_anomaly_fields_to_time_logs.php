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
        Schema::table('time_logs', function (Blueprint $table) {
            // Marca registros de jornada que superan el umbral esperado (ej. jornada olvidada abierta)
            $table->boolean('is_anomalous')->default(false)->after('note');
            // Razón de la anomalía: 'exceeded_threshold' | 'exceeded_schedule' | 'manual'
            $table->string('anomaly_reason')->nullable()->after('is_anomalous');
            // Minutos esperados del usuario ese día (para estadísticas normalizadas)
            $table->unsignedSmallInteger('expected_minutes')->nullable()->after('anomaly_reason');

            $table->index(['user_id', 'type', 'is_anomalous']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_logs', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'type', 'is_anomalous']);
            $table->dropColumn(['is_anomalous', 'anomaly_reason', 'expected_minutes']);
        });
    }
};
