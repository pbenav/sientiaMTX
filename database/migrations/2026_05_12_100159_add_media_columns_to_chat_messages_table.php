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
            $table->text('message')->nullable()->change(); // Permite que el mensaje sea nulo si se envían solo adjuntos
            $table->string('file_path')->nullable()->after('message');
            $table->string('file_type')->nullable()->after('file_path'); // 'image', 'file', 'audio'
            $table->bigInteger('file_size')->nullable()->after('file_type');
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->text('message')->nullable(false)->change();
            $table->dropColumn(['file_path', 'file_type', 'file_size']);
        });
    }
};
