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
        Schema::table('expedientes', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
            $table->dropForeign(['predecessor_id']);
            $table->dropColumn('predecessor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->constrained('expedientes')->nullOnDelete();
            $table->foreignId('predecessor_id')->nullable()->constrained('expedientes')->nullOnDelete();
        });
    }
};
