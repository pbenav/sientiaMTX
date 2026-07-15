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
        Schema::table('activity_skills', function (Blueprint $table) {
            // Eliminar la FK antigua si existe
            $foreignKeys = array_map(function ($fk) {
                return $fk['name'];
            }, Schema::getForeignKeys('activity_skills'));

            if (in_array('skill_task_task_id_foreign', $foreignKeys)) {
                $table->dropForeign('skill_task_task_id_foreign');
            }

            // Eliminar registros huérfanos antes de añadir la nueva FK
            \Illuminate\Support\Facades\DB::table('activity_skills')
                ->whereNotIn('activity_id', function ($query) {
                    $query->select('id')->from('activities');
                })
                ->delete();

            // Añadir la nueva FK apuntando a activities
            if (!in_array('activity_skills_activity_id_foreign', $foreignKeys)) {
                $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_skills', function (Blueprint $table) {
            $foreignKeys = array_map(function ($fk) {
                return $fk['name'];
            }, Schema::getForeignKeys('activity_skills'));

            if (in_array('activity_skills_activity_id_foreign', $foreignKeys)) {
                $table->dropForeign('activity_skills_activity_id_foreign');
            }

            if (!in_array('skill_task_task_id_foreign', $foreignKeys)) {
                $table->foreign('activity_id', 'skill_task_task_id_foreign')->references('id')->on('tasks')->onDelete('cascade');
            }
        });
    }
};
