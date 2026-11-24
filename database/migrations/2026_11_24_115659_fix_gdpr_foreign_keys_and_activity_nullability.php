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
            $table->dropForeign(['created_by_id']);
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by_id')->nullable()->change();
            $table->foreign('created_by_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('activity_attachments', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by_id']);
        });

        Schema::table('activity_attachments', function (Blueprint $table) {
            $table->unsignedBigInteger('uploaded_by_id')->nullable()->change();
            $table->foreign('uploaded_by_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('activity_assignments', function (Blueprint $table) {
            $table->dropForeign(['assigned_by_id']);
        });

        Schema::table('activity_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_by_id')->nullable()->change();
            $table->foreign('assigned_by_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('activity_histories', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('activity_histories', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('attachment_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('attachment_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op for safety in rollback
    }
};
