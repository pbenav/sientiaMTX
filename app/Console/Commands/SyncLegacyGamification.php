<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Task;
use App\Models\Skill;
use Illuminate\Support\Facades\DB;

class SyncLegacyGamification extends Command
{
    protected $signature = 'gamification:sync-legacy {--force : Sobrescribir XP actual si ya existe}';
    protected $description = 'Sincroniza el sistema de gamificación con el histórico de tareas completadas.';

    // Mapeo de palabras clave a NOMBRES de habilidades
    protected $mapping = [
        'Pedagogía' => ['taller', 'educación', 'formación', 'pedagog', 'curso'],
        'Soporte Técnico' => ['soporte', 'técnico', 'pc', 'instalación', 'arreglo', 'cable', 'hardware'],
        'Administración' => ['gestión', 'fac', 'factura', 'informe', 'report', 'paper', 'contab', 'admin'],
        'Gestión Emocional' => ['emocion', 'psicolo', 'ayuda', 'apoyo', 'escucha', 'acompañamiento'],
        'Impacto Rural' => ['rural', 'territorio', 'mapa', 'zafarraya', 'pueblo', 'comunidad'],
        'Animación Sociocultural' => ['ocio', 'dinamización', 'ocio', 'evento', 'reunión', 'actividad'],
        'Internet' => ['web', 'plataforma', 'sientia', 'mtx', 'redes', 'hosting', 'dominio'],
        'Informática' => ['software', 'app', 'program', 'código', 'bug', 'github', 'desarrollo'],
        'Jurídico General' => ['jurídico', 'ley', 'contrato', 'legal', 'abogado'],
    ];

    public function handle()
    {
        $this->info('Iniciando sincronización de gamificación retroactiva... 🚀');
        
        $force = $this->option('force');

        // Cacheamos las habilidades existentes por nombre para ir rápido
        $skillsCache = Skill::all()->pluck('id', 'name')->toArray();

        // 1. Paso: Asignar habilidades a tareas que no tengan una
        $this->info('Paso 1: Mapeando habilidades a tareas antiguas...');
        $unassignedTasks = Task::whereNull('skill_id')->where('is_template', false)->get();
        $mappedCount = 0;

        foreach ($unassignedTasks as $task) {
            $content = strtolower($task->title . ' ' . $task->description);
            $skillId = null;

            foreach ($this->mapping as $skillName => $keywords) {
                foreach ($keywords as $kw) {
                    if (str_contains($content, $kw)) {
                        $skillId = $skillsCache[$skillName] ?? null;
                        break 2;
                    }
                }
            }

            if ($skillId) {
                $task->skill_id = $skillId;
                $task->saveQuietly();
                $mappedCount++;
            }
        }
        $this->info("He mapeado {$mappedCount} tareas a habilidades por palabras clave.");

        // 2/3. Paso: Recalcular XP por usuario y habilidad
        $this->info('Paso 2: Calculando XP retroactivo desde los registros de tiempo...');
        $users = User::all();
        $totalXpAdded = 0;

        foreach ($users as $user) {
            $this->comment("Procesando usuario: {$user->name}");
            
            // Si no forzamos y ya tiene XP configurado, saltamos este usuario para no duplicar
            if (!$force && $user->experience_points > 0) {
                $this->warn("   - Saltando: ya tiene datos de experiencia.");
                continue;
            }

            // Agrupamos el tiempo tracked por habilidad
            // Necesitamos los logs asociados a tareas con skill_id
            $logsBySkill = DB::table('time_logs')
                ->join('tasks', 'time_logs.task_id', '=', 'tasks.id')
                ->where('time_logs.user_id', $user->id)
                ->whereNotNull('tasks.skill_id')
                ->whereNotNull('time_logs.end_at')
                ->select('tasks.skill_id', DB::raw('SUM(TIMESTAMPDIFF(SECOND, time_logs.start_at, time_logs.end_at)) as total_seconds'))
                ->groupBy('tasks.skill_id')
                ->get();

            $userTotalXP = 0;

            foreach ($logsBySkill as $logData) {
                // Formula: 1 XP cada 6 minutos (360 segundos)
                $xpToAdd = (int) floor($logData->total_seconds / 360);
                
                if ($xpToAdd > 0) {
                    // Actualizar pivot user_skills
                    $currentLevel = $this->calculateLevel($xpToAdd);
                    
                    $user->skills()->syncWithoutDetaching([
                        $logData->skill_id => [
                            'total_xp' => $xpToAdd,
                            'level' => $currentLevel
                        ]
                    ]);
                    
                    $userTotalXP += $xpToAdd;
                }
            }

            // Actualizar total general del usuario
            $user->experience_points = $userTotalXP;
            // Puntos de resiliencia (tareas sin habilidad?)
            $resilienceXp = (int) floor($user->timeLogs()->where('type', 'task')->whereNotNull('end_at')
                ->whereHas('task', fn($q) => $q->whereNull('skill_id'))
                ->get()
                ->sum(fn($log) => $log->start_at->diffInSeconds($log->end_at)) / 720); // Resiliencia cuesta el doble (12 mins = 1 XP)
            
            $user->resilience_points = $resilienceXp;
            $user->save();
            
            $totalXpAdded += $userTotalXP;
        }

        $this->info("¡Sincronización completada! Total XP retroactivo recalculado: {$totalXpAdded} XP.");
        return 0;
    }

    protected function calculateLevel($xp)
    {
        $thresholds = [1 => 0, 2 => 50, 3 => 150, 4 => 350, 5 => 750];
        $level = 1;
        foreach ($thresholds as $l => $t) {
            if ($xp >= $t) $level = $l;
        }
        return $level;
    }
}
