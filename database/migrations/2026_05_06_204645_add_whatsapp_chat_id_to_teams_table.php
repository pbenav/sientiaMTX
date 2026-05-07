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
        if (Schema::hasTable('teams') && !Schema::hasColumn('teams', 'whatsapp_chat_id')) {
            Schema::table('teams', function (Blueprint $table) {
                $table->string('whatsapp_chat_id')->nullable()->after('telegram_chat_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('teams') && Schema::hasColumn('teams', 'whatsapp_chat_id')) {
            Schema::table('teams', function (Blueprint $table) {
                $table->dropColumn('whatsapp_chat_id');
            });
        }
    }
};
