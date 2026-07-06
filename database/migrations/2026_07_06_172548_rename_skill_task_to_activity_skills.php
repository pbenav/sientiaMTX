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
        if (Schema::hasTable('skill_task') && !Schema::hasTable('activity_skills')) {
            Schema::rename('skill_task', 'activity_skills');
        }

        if (Schema::hasTable('activity_skills')) {
            Schema::table('activity_skills', function (Blueprint $table) {
                if (Schema::hasColumn('activity_skills', 'task_id') && !Schema::hasColumn('activity_skills', 'activity_id')) {
                    $table->renameColumn('task_id', 'activity_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('activity_skills')) {
            Schema::table('activity_skills', function (Blueprint $table) {
                if (Schema::hasColumn('activity_skills', 'activity_id') && !Schema::hasColumn('activity_skills', 'task_id')) {
                    $table->renameColumn('activity_id', 'task_id');
                }
            });
        }

        if (Schema::hasTable('activity_skills') && !Schema::hasTable('skill_task')) {
            Schema::rename('activity_skills', 'skill_task');
        }
    }
};
