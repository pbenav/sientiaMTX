<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('receiver_id')->nullable()->change();
            $table->foreignId('chat_group_id')->nullable()->constrained('chat_groups')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropForeign(['chat_group_id']);
            $table->dropColumn('chat_group_id');
            // Reverting receiver_id to non-nullable is tricky if there are nulls, so we leave it nullable or handle it.
            // Assuming MySQL 8+, simple change:
            // $table->unsignedBigInteger('receiver_id')->nullable(false)->change();
        });
    }
};
