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
        Schema::table('skills', function (Blueprint $table) {
            if (!Schema::hasColumn('skills', 'description')) {
                $table->string('description')->nullable()->after('name');
            }
            if (!Schema::hasColumn('skills', 'color')) {
                $table->string('color')->nullable()->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skills', function (Blueprint $table) {
            if (Schema::hasColumn('skills', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('skills', 'color')) {
                $table->dropColumn('color');
            }
        });
    }
};
