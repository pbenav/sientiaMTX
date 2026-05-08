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
            Schema::table('whatsapp_messages', function (Blueprint $table) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = $sm->listTableIndexes('whatsapp_messages');
                
                if (!array_key_exists('whatsapp_messages_team_id_created_at_index', $indexes)) {
                    $table->index(['team_id', 'created_at']);
                }
                if (!array_key_exists('whatsapp_messages_team_id_id_index', $indexes)) {
                    $table->index(['team_id', 'id']);
                }
            });
        }

        // 2. Índices compuestos para acelerar el Polling y listado de Telegram
        if (Schema::hasTable('telegram_messages')) {
            Schema::table('telegram_messages', function (Blueprint $table) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = $sm->listTableIndexes('telegram_messages');

                if (!array_key_exists('telegram_messages_team_id_created_at_index', $indexes)) {
                    $table->index(['team_id', 'created_at']);
                }
                if (!array_key_exists('telegram_messages_team_id_id_index', $indexes)) {
                    $table->index(['team_id', 'id']);
                }
            });
        }

        // 3. Índice para acelerar el procesamiento de tareas autoprogramables
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = $sm->listTableIndexes('tasks');

                if (!array_key_exists('tasks_is_autoprogrammable_index', $indexes)) {
                    $table->index('is_autoprogrammable');
                }
            });
        }

        // 4. Índice para hilos de mensajes en foros
        if (Schema::hasTable('forum_messages')) {
            Schema::table('forum_messages', function (Blueprint $table) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = $sm->listTableIndexes('forum_messages');

                if (!array_key_exists('forum_messages_parent_id_index', $indexes)) {
                    $table->index('parent_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('whatsapp_messages')) {
            Schema::table('whatsapp_messages', function (Blueprint $table) {
                $table->dropIndex(['team_id', 'created_at']);
                $table->dropIndex(['team_id', 'id']);
            });
        }

        if (Schema::hasTable('telegram_messages')) {
            Schema::table('telegram_messages', function (Blueprint $table) {
                $table->dropIndex(['team_id', 'created_at']);
                $table->dropIndex(['team_id', 'id']);
            });
        }

        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropIndex(['is_autoprogrammable']);
            });
        }

        if (Schema::hasTable('forum_messages')) {
            Schema::table('forum_messages', function (Blueprint $table) {
                $table->dropIndex(['parent_id']);
            });
        }
    }
};
