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
        \App\Models\Setting::updateOrCreate(
            ['key' => 'quick_notes_audio_max_duration'],
            ['value' => '60'] // Aumentado a 60s por defecto según sugerencia de que 30s era corto
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \App\Models\Setting::where('key', 'quick_notes_audio_max_duration')->delete();
    }
};
