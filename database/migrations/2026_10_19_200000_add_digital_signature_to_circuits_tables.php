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
        // 1. Agregar requiere firma a los pasos de plantilla
        Schema::table('circuit_template_steps', function (Blueprint $table) {
            $table->boolean('requires_signature')->default(false)->after('allow_return');
        });

        // 2. Agregar soporte de firma digital a los pasos de tramitación en curso
        Schema::table('circuit_execution_steps', function (Blueprint $table) {
            $table->boolean('requires_signature')->default(false)->after('status');
            $table->timestamp('signed_at')->nullable()->after('completed_at');
            $table->json('signature_data')->nullable()->after('signed_at');
        });

        // 3. Agregar ruta del documento final firmado a la ejecución del circuito
        Schema::table('circuit_executions', function (Blueprint $table) {
            $table->string('signed_document_path')->nullable()->after('metadata');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('circuit_executions', function (Blueprint $table) {
            $table->dropColumn('signed_document_path');
        });

        Schema::table('circuit_execution_steps', function (Blueprint $table) {
            $table->dropColumn(['requires_signature', 'signed_at', 'signature_data']);
        });

        Schema::table('circuit_template_steps', function (Blueprint $table) {
            $table->dropColumn('requires_signature');
        });
    }
};

