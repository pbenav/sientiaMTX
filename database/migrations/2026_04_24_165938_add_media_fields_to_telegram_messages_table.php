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
        Schema::table('telegram_messages', function (Blueprint $table) {
            $table->string('voice_path')->nullable()->after('photo_path');
            $table->integer('voice_duration')->nullable()->after('voice_path');
            $table->string('sticker_path')->nullable()->after('voice_duration');
            $table->string('file_type')->nullable()->after('sticker_path'); // voice, sticker, photo, text
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telegram_messages', function (Blueprint $table) {
            $table->dropColumn(['voice_path', 'voice_duration', 'sticker_path', 'file_type']);
        });
    }
};
