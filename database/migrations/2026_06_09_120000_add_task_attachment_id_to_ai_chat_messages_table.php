<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_chat_messages', function (Blueprint $table) {
            $table->foreignId('task_attachment_id')
                ->nullable()
                ->after('task_id')
                ->constrained('task_attachments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ai_chat_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('task_attachment_id');
        });
    }
};
