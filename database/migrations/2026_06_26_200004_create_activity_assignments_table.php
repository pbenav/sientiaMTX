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
        Schema::create('activity_assignments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->foreignId('assigned_by_id')->constrained('users');
            $table->dateTime('assigned_at');
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index('activity_id');
            $table->index('user_id');
            $table->index('group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_assignments');
    }
};
