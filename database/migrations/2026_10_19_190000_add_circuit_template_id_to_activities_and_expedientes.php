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
        Schema::table('activities', function (Blueprint $table) {
            $table->foreignId('circuit_template_id')
                ->nullable()
                ->after('folder_id')
                ->constrained('circuit_templates')
                ->nullOnDelete();
        });

        Schema::table('expedientes', function (Blueprint $table) {
            $table->foreignId('circuit_template_id')
                ->nullable()
                ->constrained('circuit_templates')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['circuit_template_id']);
            $table->dropColumn('circuit_template_id');
        });

        Schema::table('expedientes', function (Blueprint $table) {
            $table->dropForeign(['circuit_template_id']);
            $table->dropColumn('circuit_template_id');
        });
    }
};
