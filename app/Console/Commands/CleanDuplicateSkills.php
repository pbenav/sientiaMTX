<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Skill;
use Illuminate\Support\Facades\DB;

class CleanDuplicateSkills extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-duplicate-skills';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpiar habilidades duplicadas en el sistema reasignando tareas y usuarios de forma segura.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Buscando habilidades duplicadas dentro del mismo equipo o a nivel global...');

        // Buscamos duplicados exactos (mismo nombre y mismo team_id)
        $duplicates = DB::table('skills')
            ->select('team_id', 'name', DB::raw('COUNT(*) as total'))
            ->groupBy('team_id', 'name')
            ->having('total', '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('No hay habilidades duplicadas en la base de datos para depurar.');
            return;
        }

        foreach ($duplicates as $duplicate) {
            $this->warn("Procesando la habilidad '{$duplicate->name}' (ID de Equipo: " . ($duplicate->team_id ?? 'Global') . ") - Tiene {$duplicate->total} clones.");

            $query = Skill::where('name', $duplicate->name);
            if ($duplicate->team_id === null) {
                $query->whereNull('team_id');
            } else {
                $query->where('team_id', $duplicate->team_id);
            }
            $skills = $query->orderBy('id', 'asc')->get();

            // Guardamos el más antiguo (menor ID)
            $original = $skills->first();
            $duplicatesToRemove = $skills->slice(1);

            foreach ($duplicatesToRemove as $dup) {
                $this->line(" - Reasignando datos del ID conflictivo {$dup->id} hacia el original ID {$original->id}...");

                // 1. Reasignar Tareas (skill_task)
                $tasksWithDup = DB::table('skill_task')->where('skill_id', $dup->id)->pluck('task_id');
                foreach ($tasksWithDup as $taskId) {
                    $exists = DB::table('skill_task')
                        ->where('skill_id', $original->id)
                        ->where('task_id', $taskId)
                        ->exists();

                    if (!$exists) {
                        DB::table('skill_task')
                            ->where('skill_id', $dup->id)
                            ->where('task_id', $taskId)
                            ->update(['skill_id' => $original->id]);
                    } else {
                        // Si la tarea ya tenía la original, simplemente borramos el enlace duplicado
                        DB::table('skill_task')
                            ->where('skill_id', $dup->id)
                            ->where('task_id', $taskId)
                            ->delete();
                    }
                }

                // 2. Reasignar XP de Usuarios (user_skills)
                $usersWithDup = DB::table('user_skills')->where('skill_id', $dup->id)->get();
                foreach ($usersWithDup as $userSkill) {
                    $exists = DB::table('user_skills')
                        ->where('skill_id', $original->id)
                        ->where('user_id', $userSkill->user_id)
                        ->first();

                    if (!$exists) {
                        DB::table('user_skills')
                            ->where('skill_id', $dup->id)
                            ->where('user_id', $userSkill->user_id)
                            ->update(['skill_id' => $original->id]);
                    } else {
                        // Sumar expirience a la habilidad original
                        DB::table('user_skills')
                            ->where('skill_id', $original->id)
                            ->where('user_id', $userSkill->user_id)
                            ->update([
                                'total_xp' => $exists->total_xp + $userSkill->total_xp,
                                'level' => max($exists->level, $userSkill->level)
                            ]);
                        // Borramos el registro del clon
                        DB::table('user_skills')
                            ->where('skill_id', $dup->id)
                            ->where('user_id', $userSkill->user_id)
                            ->delete();
                    }
                }

                // Una vez vaciadas sus relaciones, podemos borrar el clon
                $dup->delete();
                $this->info("   > Clon ID {$dup->id} borrado de forma segura.");
            }
        }

        $this->info('¡Terminado! Base de datos de habilidades saneada de duplicados exactos.');

        $this->info('Buscando habilidades sombreadas (Global vs Local con el mismo nombre)...');
        $localSkills = Skill::whereNotNull('team_id')->get();
        foreach ($localSkills as $localSkill) {
            $globalSkill = Skill::whereNull('team_id')->where('name', $localSkill->name)->first();
            if ($globalSkill) {
                $this->warn("La habilidad local '{$localSkill->name}' (ID:{$localSkill->id}, Team:{$localSkill->team_id}) sombreada a la global (ID:{$globalSkill->id}). Migrando...");

                // 1. Migrar Tareas de este Equipo que usen la global
                $teamTaskIds = DB::table('tasks')->where('team_id', $localSkill->team_id)->pluck('id');
                if ($teamTaskIds->isNotEmpty()) {
                    // Skill_task table
                    $affectedTasks = DB::table('skill_task')
                        ->whereIn('task_id', $teamTaskIds)
                        ->where('skill_id', $globalSkill->id)
                        ->get();

                    foreach ($affectedTasks as $at) {
                        $alreadyHasLocal = DB::table('skill_task')
                            ->where('task_id', $at->task_id)
                            ->where('skill_id', $localSkill->id)
                            ->exists();

                        if (!$alreadyHasLocal) {
                            DB::table('skill_task')
                                ->where('task_id', $at->task_id)
                                ->where('skill_id', $globalSkill->id)
                                ->update(['skill_id' => $localSkill->id]);
                        } else {
                            DB::table('skill_task')
                                ->where('task_id', $at->task_id)
                                ->where('skill_id', $globalSkill->id)
                                ->delete();
                        }
                    }

                    // Task table legacy skill_id col
                    DB::table('tasks')
                        ->where('team_id', $localSkill->team_id)
                        ->where('skill_id', $globalSkill->id)
                        ->update(['skill_id' => $localSkill->id]);
                }

                // 2. Migrar XP de Usuarios de este Equipo
                $teamMemberIds = DB::table('team_user')->where('team_id', $localSkill->team_id)->pluck('user_id');
                if ($teamMemberIds->isNotEmpty()) {
                    foreach ($teamMemberIds as $userId) {
                        $globalUserSkill = DB::table('user_skills')
                            ->where('user_id', $userId)
                            ->where('skill_id', $globalSkill->id)
                            ->first();

                        if ($globalUserSkill) {
                            $localUserSkill = DB::table('user_skills')
                                ->where('user_id', $userId)
                                ->where('skill_id', $localSkill->id)
                                ->first();

                            if (!$localUserSkill) {
                                DB::table('user_skills')
                                    ->where('user_id', $userId)
                                    ->where('skill_id', $globalSkill->id)
                                    ->update(['skill_id' => $localSkill->id]);
                                $this->line("   - Movido XP de usuario ID:{$userId} de Global a Local.");
                            } else {
                                // Sumar XP
                                DB::table('user_skills')
                                    ->where('user_id', $userId)
                                    ->where('skill_id', $localSkill->id)
                                    ->update([
                                        'total_xp' => $localUserSkill->total_xp + $globalUserSkill->total_xp,
                                        'level' => max($localUserSkill->level, $globalUserSkill->level)
                                    ]);
                                // Borrar global para este usuario específico
                                DB::table('user_skills')
                                    ->where('user_id', $userId)
                                    ->where('skill_id', $globalSkill->id)
                                    ->delete();
                                $this->line("   - Fusionado XP de usuario ID:{$userId} Global -> Local.");
                            }
                        }
                    }
                }
            }
        }

        $this->info('¡Terminado! Base de datos de habilidades saneada y sincronizada.');
    }
}
