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
        Schema::table('quick_notes', function (Blueprint $table) {
            $table->boolean('is_hidden')->default(false)->after('is_minimized');
        });
    }

    public function down(): void
    {
        Schema::table('quick_notes', function (Blueprint $table) {
            $table->dropColumn('is_hidden');
        });
    }
};
