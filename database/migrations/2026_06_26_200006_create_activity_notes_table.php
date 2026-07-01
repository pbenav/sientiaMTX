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
        Schema::create('activity_notes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->longText('content');
            // private = solo visible por el autor
            // internal = visible por miembros del equipo con permiso
            $table->enum('visibility', ['private', 'internal'])->default('private');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['activity_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_notes');
    }
};
