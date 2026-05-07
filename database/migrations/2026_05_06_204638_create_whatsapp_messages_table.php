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
        if (!Schema::hasTable('whatsapp_messages')) {
            Schema::create('whatsapp_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('team_id')->constrained()->cascadeOnDelete();
                $table->string('message_id')->unique()->nullable();
                $table->boolean('from_me')->default(false);
                $table->string('author')->nullable();
                $table->text('text')->nullable();
                $table->string('file_type')->nullable(); // photo, voice, sticker, animation
                $table->string('file_mime_type')->nullable();
                $table->string('photo_path')->nullable();
                $table->string('voice_path')->nullable();
                $table->string('sticker_path')->nullable();
                $table->string('animation_path')->nullable();
                $table->unsignedBigInteger('file_size')->default(0);
                $table->string('reply_to_id')->nullable();
                $table->text('reply_to_text')->nullable();
                $table->boolean('is_deleted')->default(false);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
