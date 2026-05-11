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
            // Self-referencing Hierarchy (Parent/Container)
            $table->foreignId('parent_id')
                ->nullable()
                ->after('team_id')
                ->constrained('expedientes')
                ->nullOnDelete();

            // Self-referencing Chronology (Workflow predecessor)
            $table->foreignId('predecessor_id')
                ->nullable()
                ->after('parent_id')
                ->constrained('expedientes')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
            $table->dropForeign(['predecessor_id']);
            $table->dropColumn('predecessor_id');
        });
    }
};
