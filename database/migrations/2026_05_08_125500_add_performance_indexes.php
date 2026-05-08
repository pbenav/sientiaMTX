<?php

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
        // 1. Índices compuestos para acelerar el Polling y listado de WhatsApp
        if (Schema::hasTable('whatsapp_messages')) {
            try {
                Schema::table('whatsapp_messages', function (Blueprint $table) {
                    $table->index(['team_id', 'created_at']);
                });
            } catch (\Exception $e) {}

            try {
                Schema::table('whatsapp_messages', function (Blueprint $table) {
                    $table->index(['team_id', 'id']);
                });
            } catch (\Exception $e) {}
        }

        // 2. Índices compuestos para acelerar el Polling y listado de Telegram
        if (Schema::hasTable('telegram_messages')) {
            try {
                Schema::table('telegram_messages', function (Blueprint $table) {
                    $table->index(['team_id', 'created_at']);
                });
            } catch (\Exception $e) {}

            try {
                Schema::table('telegram_messages', function (Blueprint $table) {
                    $table->index(['team_id', 'id']);
                });
            } catch (\Exception $e) {}
        }

        // 3. Índice para acelerar el procesamiento de tareas autoprogramables
        if (Schema::hasTable('tasks')) {
            try {
                Schema::table('tasks', function (Blueprint $table) {
                    $table->index('is_autoprogrammable');
                });
            } catch (\Exception $e) {}
        }

        // 4. Índice para hilos de mensajes en foros
        if (Schema::hasTable('forum_messages')) {
            try {
                Schema::table('forum_messages', function (Blueprint $table) {
                    $table->index('parent_id');
                });
            } catch (\Exception $e) {}
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('whatsapp_messages')) {
            try {
                Schema::table('whatsapp_messages', function (Blueprint $table) {
                    $table->dropIndex(['team_id', 'created_at']);
                    $table->dropIndex(['team_id', 'id']);
                });
            } catch (\Exception $e) {}
        }

        if (Schema::hasTable('telegram_messages')) {
            try {
                Schema::table('telegram_messages', function (Blueprint $table) {
                    $table->dropIndex(['team_id', 'created_at']);
                    $table->dropIndex(['team_id', 'id']);
                });
            } catch (\Exception $e) {}
        }

        if (Schema::hasTable('tasks')) {
            try {
                Schema::table('tasks', function (Blueprint $table) {
                    $table->dropIndex(['is_autoprogrammable']);
                });
            } catch (\Exception $e) {}
        }

        if (Schema::hasTable('forum_messages')) {
            try {
                Schema::table('forum_messages', function (Blueprint $table) {
                    $table->dropIndex(['parent_id']);
                });
            } catch (\Exception $e) {}
        }
    }
};
