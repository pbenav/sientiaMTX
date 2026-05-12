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
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->string('file_name')->nullable()->after('message');
            $table->string('storage_provider')->default('local')->after('file_size'); // 'local', 'google'
            $table->text('web_view_link')->nullable()->after('storage_provider');
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn(['file_name', 'storage_provider', 'web_view_link']);
        });
    }
};
