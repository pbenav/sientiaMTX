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
            if (!Schema::hasColumn('telegram_messages', 'photo_path')) {
                $table->string('photo_path', 255)->nullable()->after('text');
            }
            if (!Schema::hasColumn('telegram_messages', 'is_deleted_on_telegram')) {
                $table->boolean('is_deleted_on_telegram')->default(false)->after('is_from_web');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telegram_messages', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'is_deleted_on_telegram']);
        });
    }
};
