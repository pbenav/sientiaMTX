<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Repara las asignaciones de instancias de Plan Maestro (activities con parent_id)
 * que quedaron huérfanas durante la migración de Tasks → Activities.
 *
 * Estrategia:
 *  1. Via activity_task_mapping → tasks.assigned_user_id (incluyendo soft-deleted)
 *  2. Via activity_task_mapping → task_assignments (incluyendo soft-deleted)
 *  3. Fallback: si el plan maestro tiene un único miembro asignado, usar ese
 *
 * Uso:
 *   php artisan activities:repair-assignments          (dry-run, solo muestra lo que haría)
 *   php artisan activities:repair-assignments --fix    (aplica los cambios)
 *   php artisan activities:repair-assignments --fix --parent=563  (solo un plan maestro)
 */
class RepairActivityAssignments extends Command
{
    protected $signature = 'activities:repair-assignments
                            {--fix : Aplica los cambios (por defecto es dry-run)}
                            {--parent= : Limitar a un plan maestro específico (activity ID)}';

    protected $description = 'Repara asignaciones de instancias de Plan Maestro perdidas durante la migración';

    private int $fixed   = 0;
    private int $missing = 0;

    public function handle(): int
    {
        $isDryRun  = ! $this->option('fix');
        $parentId  = $this->option('parent');

        $this->info('');
        $this->info('=== RepairActivityAssignments' . ($isDryRun ? ' [DRY-RUN]' : ' [APLICANDO]') . ' ===');
        $this->info('');

        // Instancias sin asignación que tienen una task mapeada
        $query = DB::table('activity_task_mapping as m')
            ->join('activities as a', 'a.id', '=', 'm.activity_id')
            ->whereNotNull('a.parent_id')
            ->where('a.is_template', false)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('activity_assignments')
                  ->whereColumn('activity_assignments.activity_id', 'a.id');
            })
            ->select('a.id as activity_id', 'a.parent_id', 'a.title', 'm.task_id');

        if ($parentId) {
            $query->where('a.parent_id', $parentId);
        }

        $orphans = $query->get();

        if ($orphans->isEmpty()) {
            $this->info('✅ No se encontraron instancias sin asignación. Todo OK.');
            return self::SUCCESS;
        }

        $this->warn("⚠  Encontradas {$orphans->count()} instancias sin asignación.");
        $this->info('');

        $headers = ['Activity ID', 'Parent ID', 'Título', 'Task ID', 'Usuario recuperado', 'Fuente'];
        $rows    = [];

        foreach ($orphans as $orphan) {
            $userId   = null;
            $source   = null;

            // ── Estrategia 1: tasks.assigned_user_id (incluye soft-deleted) ──
            $task = DB::table('tasks')
                ->where('id', $orphan->task_id)
                ->whereNotNull('assigned_user_id')
                ->first(['assigned_user_id', 'created_by_id']);

            if ($task?->assigned_user_id) {
                $userId = $task->assigned_user_id;
                $source = 'tasks.assigned_user_id';
            }

            // ── Estrategia 2: task_assignments (por si hay más de uno) ──
            if (! $userId) {
                $ta = DB::table('task_assignments')
                    ->where('task_id', $orphan->task_id)
                    ->whereNotNull('user_id')
                    ->first();

                if ($ta) {
                    $userId = $ta->user_id;
                    $source = 'task_assignments';
                }
            }

            // ── Estrategia 3: fallback al único miembro del plan maestro ──
            if (! $userId) {
                $parentMembers = DB::table('activity_assignments')
                    ->where('activity_id', $orphan->parent_id)
                    ->whereNotNull('user_id')
                    ->get(['user_id']);

                if ($parentMembers->count() === 1) {
                    $userId = $parentMembers->first()->user_id;
                    $source = 'plan_maestro_unico_miembro';
                }
            }

            // Resolver nombre del usuario
            $userName = $userId
                ? (DB::table('users')->where('id', $userId)->value('name') ?? "User#{$userId}")
                : null;

            $rows[] = [
                $orphan->activity_id,
                $orphan->parent_id,
                mb_strimwidth($orphan->title, 0, 40, '...'),
                $orphan->task_id,
                $userName ?? '❌ NO RECUPERADO',
                $source    ?? '—',
            ];

            if ($userId && ! $isDryRun) {
                DB::table('activity_assignments')->insertOrIgnore([
                    'activity_id'    => $orphan->activity_id,
                    'user_id'        => $userId,
                    'assigned_at'    => now(),
                    'assigned_by_id' => $task?->created_by_id ?? $userId,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
                $this->fixed++;
            } elseif ($userId) {
                $this->fixed++;   // contamos los "reparables" en dry-run
            } else {
                if (! $isDryRun) {
                    \App\Models\Activity::where('id', $orphan->activity_id)->forceDelete();
                }
                $this->missing++;
            }
        }

        $this->table($headers, $rows);

        $this->info('');

        if ($isDryRun) {
            $this->info("🔍 DRY-RUN: {$this->fixed} instancias se podrían reparar, {$this->missing} huérfanos se eliminarían por falta de datos.");
            $this->warn('   Ejecuta con --fix para aplicar los cambios.');
        } else {
            $this->info("✅ Reparadas: {$this->fixed} | 🗑️ Eliminadas (sin datos): {$this->missing}");
        }

        return self::SUCCESS;
    }
}
