<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\ActivityAttachment;
use App\Models\ActivityNote;
use App\Models\ActivityRating;
use App\Models\Appointment;
use App\Models\CircuitExecution;
use App\Models\ForumThread;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskPrivateNote;
use App\Models\TimeLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Repara y sincroniza toda la información anexa y relaciones de actividades que fueron convertidas de tipo.
 *
 * Transfiere:
 *  - Hilos de foro (ForumThread)
 *  - Registros horarios (TimeLog)
 *  - Tramitaciones y firmas (CircuitExecution)
 *  - Citas asociadas (Appointment)
 *  - Calificaciones (ActivityRating)
 *  - Carpeta en biblioteca (folder_id)
 *  - Plantilla de circuito (circuit_template_id)
 *  - Especialidades (skills / skill_id / service_id)
 *  - Sincronización Google (google_calendar_id, google_task_id, etc.)
 *  - Referencias de conversión (converted_from_id, original_metadata)
 *  - Asignaciones (ActivityAssignments y legacy)
 *  - Adjuntos (ActivityAttachments y legacy TaskAttachments)
 *  - Notas privadas y públicas
 *  - Etiquetas (Tags)
 *
 * Uso:
 *   php artisan activities:repair-converted                (Simulación / Dry-run)
 *   php artisan activities:repair-converted --fix          (Aplica las reparaciones)
 *   php artisan activities:repair-converted --fix --team=1 (Filtra por equipo)
 */
class RepairConvertedActivities extends Command
{
    protected $signature = 'activities:repair-converted
                            {--fix : Aplica los cambios en la base de datos (por defecto es simulación dry-run)}
                            {--team= : Limitar la reparación a un equipo específico (team ID)}
                            {--activity= : Limitar a una actividad convertida concreta (target activity ID)}';

    protected $description = 'Sincroniza y repara información anexa (foros, timelogs, circuitos, adjuntos, notas, etc.) en actividades convertidas';

    private int $threadsFixed = 0;
    private int $timeLogsFixed = 0;
    private int $circuitsFixed = 0;
    private int $appointmentsFixed = 0;
    private int $ratingsFixed = 0;
    private int $attachmentsCopied = 0;
    private int $notesCopied = 0;
    private int $assignmentsCopied = 0;
    private int $tagsCopied = 0;
    private int $fieldsUpdated = 0;

    public function handle(): int
    {
        $isDryRun = !$this->option('fix');
        $teamId = $this->option('team');
        $activityId = $this->option('activity');

        $this->newLine();
        $this->info('=== Reparación de Actividades Convertidas ' . ($isDryRun ? '[MODO SIMULACIÓN - DRY RUN]' : '[APLICANDO CAMBIOS]') . ' ===');
        $this->newLine();

        $query = Activity::query()
            ->where(function ($q) {
                $q->whereNotNull('metadata->converted_from_uuid')
                  ->orWhereNotNull('converted_from_id');
            });

        if ($teamId) {
            $query->where('team_id', $teamId);
        }

        if ($activityId) {
            $query->where('id', $activityId);
        }

        $targets = $query->get();

        if ($targets->isEmpty()) {
            $this->info('No se encontraron actividades convertidas con los filtros proporcionados.');
            return Command::SUCCESS;
        }

        $this->line("Analizando {$targets->count()} actividades convertidas...");
        $this->newLine();

        $tableData = [];

        foreach ($targets as $target) {
            $source = $this->resolveSourceActivity($target);

            if (!$source) {
                $this->warn("Actividad #{$target->id} no tiene una actividad origen válida resoluble.");
                continue;
            }

            $legacyTask = $this->resolveLegacyTask($source);

            $actionsTaken = [];

            if (!$isDryRun) {
                DB::transaction(function () use ($source, $target, $legacyTask, &$actionsTaken) {
                    $this->processPair($source, $target, $legacyTask, false, $actionsTaken);
                });
            } else {
                $this->processPair($source, $target, $legacyTask, true, $actionsTaken);
            }

            if (!empty($actionsTaken)) {
                $tableData[] = [
                    'Target ID' => $target->id,
                    'Nuevo Tipo' => $target->type,
                    'Origen ID' => $source->id,
                    'Tipo Origen' => $source->type,
                    'Reparaciones' => implode(', ', $actionsTaken),
                ];
            }
        }

        if (!empty($tableData)) {
            $this->table(['Target ID', 'Nuevo Tipo', 'Origen ID', 'Tipo Origen', 'Reparaciones'], $tableData);
        } else {
            $this->info('Todas las actividades convertidas ya tienen su información y relaciones debidamente sincronizadas.');
        }

        $this->newLine();
        $this->info('--- Resumen global de reparaciones ---');
        $this->table(['Elemento', 'Total Reparados / Transferidos'], [
            ['Hilos de foro re-enlazados', $this->threadsFixed],
            ['Registros horarios (TimeLogs) transferidos', $this->timeLogsFixed],
            ['Tramitaciones de circuitos re-enlazadas', $this->circuitsFixed],
            ['Citas vinculadas', $this->appointmentsFixed],
            ['Calificaciones transferidas', $this->ratingsFixed],
            ['Adjuntos replicados', $this->attachmentsCopied],
            ['Notas replicadas', $this->notesCopied],
            ['Asignaciones replicadas', $this->assignmentsCopied],
            ['Etiquetas replicadas', $this->tagsCopied],
            ['Metadatos / atributos actualizados', $this->fieldsUpdated],
        ]);

        if ($isDryRun) {
            $this->newLine();
            $this->comment('Para aplicar estas reparaciones en la base de datos, ejecuta el comando con la opción --fix:');
            $this->comment('  php artisan activities:repair-converted --fix');
        } else {
            $this->newLine();
            $this->info('¡Reparaciones completadas y guardadas exitosamente en la base de datos!');
        }

        return Command::SUCCESS;
    }

    /**
     * Resuelve la actividad original origen a partir del clon destino.
     */
    private function resolveSourceActivity(Activity $target): ?Activity
    {
        if ($target->converted_from_id) {
            $source = Activity::withTrashed()->find($target->converted_from_id);
            if ($source) return $source;
        }

        $fromUuid = $target->metadata['converted_from_uuid'] ?? null;
        if ($fromUuid) {
            $source = Activity::withTrashed()->where('uuid', $fromUuid)->first();
            if ($source) return $source;
        }

        $source = Activity::withTrashed()->where('metadata->converted_to_id', $target->id)->first();
        if ($source) return $source;

        $source = Activity::withTrashed()->where('metadata->converted_to_uuid', $target->uuid)->first();
        return $source;
    }

    /**
     * Resuelve la tarea legacy vinculada a la actividad origen si procede.
     */
    private function resolveLegacyTask(Activity $source): ?Task
    {
        $mapping = DB::table('activity_task_mapping')->where('activity_id', $source->id)->first();
        if ($mapping) {
            return Task::find($mapping->task_id);
        }
        return null;
    }

    /**
     * Procesa y sincroniza las relaciones e información anexa de un par (source, target).
     */
    private function processPair(Activity $source, Activity $target, ?Task $legacyTask, bool $isDryRun, array &$actionsTaken): void
    {
        // 1. Campos directos en la actividad
        $fieldsToUpdate = [];

        if (!$target->converted_from_id || $target->converted_from_id !== $source->id) {
            $fieldsToUpdate['converted_from_id'] = $source->id;
        }

        if (empty($target->original_metadata) && !empty($source->metadata)) {
            $fieldsToUpdate['original_metadata'] = $source->metadata;
        }

        if ($source->folder_id && !$target->folder_id) {
            $fieldsToUpdate['folder_id'] = $source->folder_id;
        }

        if ($source->circuit_template_id && !$target->circuit_template_id) {
            $fieldsToUpdate['circuit_template_id'] = $source->circuit_template_id;
        }

        if ($source->skill_id && !$target->skill_id) {
            $fieldsToUpdate['skill_id'] = $source->skill_id;
        }

        if ($source->service_id && !$target->service_id) {
            $fieldsToUpdate['service_id'] = $source->service_id;
        }

        if ($source->google_calendar_event_id && !$target->google_calendar_event_id) {
            $fieldsToUpdate['google_calendar_event_id'] = $source->google_calendar_event_id;
            $fieldsToUpdate['google_calendar_id'] = $source->google_calendar_id;
        }

        if ($source->google_task_id && !$target->google_task_id) {
            $fieldsToUpdate['google_task_id'] = $source->google_task_id;
            $fieldsToUpdate['google_task_list_id'] = $source->google_task_list_id;
        }

        if ($source->google_synced_at && !$target->google_synced_at) {
            $fieldsToUpdate['google_synced_at'] = $source->google_synced_at;
        }

        if (!empty($fieldsToUpdate)) {
            $actionsTaken[] = 'Metadatos(' . count($fieldsToUpdate) . ')';
            $this->fieldsUpdated += count($fieldsToUpdate);
            if (!$isDryRun) {
                $target->update($fieldsToUpdate);
            }
        }

        // 2. Hilos de foro (ForumThread)
        $forumThreads = ForumThread::where('activity_id', $source->id)->get();
        if ($legacyTask) {
            $legacyThreads = ForumThread::where('task_id', $legacyTask->id)
                ->where(function ($q) use ($source) {
                    $q->whereNull('activity_id')->orWhere('activity_id', $source->id);
                })
                ->get();
            $forumThreads = $forumThreads->merge($legacyThreads)->unique('id');
        }

        if ($forumThreads->isNotEmpty()) {
            $actionsTaken[] = 'Foro(' . $forumThreads->count() . ')';
            $this->threadsFixed += $forumThreads->count();
            if (!$isDryRun) {
                foreach ($forumThreads as $ft) {
                    $ft->update(['activity_id' => $target->id]);
                }
            }
        }

        // 3. Registros de tiempo (TimeLogs)
        $timeLogs = TimeLog::where('task_id', $source->id)->get();
        if ($legacyTask) {
            $legacyLogs = TimeLog::where('task_id', $legacyTask->id)->get();
            $timeLogs = $timeLogs->merge($legacyLogs)->unique('id');
        }

        if ($timeLogs->isNotEmpty()) {
            $actionsTaken[] = 'TimeLogs(' . $timeLogs->count() . ')';
            $this->timeLogsFixed += $timeLogs->count();
            if (!$isDryRun) {
                foreach ($timeLogs as $tl) {
                    $tl->update(['task_id' => $target->id]);
                }
            }
        }

        // 4. Tramitaciones y firmas (CircuitExecution)
        $circuits = CircuitExecution::where(function ($q) use ($source) {
            $q->where('circuitable_type', Activity::class)
              ->orWhere('circuitable_type', 'App\\Models\\Activity')
              ->orWhere('circuitable_type', 'activity');
        })->where('circuitable_id', $source->id)->get();

        if ($legacyTask) {
            $legacyCircuits = CircuitExecution::where(function ($q) use ($legacyTask) {
                $q->where('circuitable_type', Task::class)
                  ->orWhere('circuitable_type', 'App\\Models\\Task')
                  ->orWhere('circuitable_type', 'task');
            })->where('circuitable_id', $legacyTask->id)->get();
            $circuits = $circuits->merge($legacyCircuits)->unique('id');
        }

        if ($circuits->isNotEmpty()) {
            $actionsTaken[] = 'Circuitos(' . $circuits->count() . ')';
            $this->circuitsFixed += $circuits->count();
            if (!$isDryRun) {
                foreach ($circuits as $c) {
                    $c->update([
                        'circuitable_type' => Activity::class,
                        'circuitable_id' => $target->id,
                    ]);
                }
            }
        }

        // 5. Citas (Appointment)
        $appointments = Appointment::where('activity_id', $source->id)->get();
        if ($legacyTask) {
            $legacyAppointments = Appointment::where('task_id', $legacyTask->id)->get();
            $appointments = $appointments->merge($legacyAppointments)->unique('id');
        }

        if ($appointments->isNotEmpty()) {
            $actionsTaken[] = 'Citas(' . $appointments->count() . ')';
            $this->appointmentsFixed += $appointments->count();
            if (!$isDryRun) {
                foreach ($appointments as $app) {
                    $app->update(['activity_id' => $target->id]);
                }
            }
        }

        // 6. Calificaciones (ActivityRating)
        $ratings = ActivityRating::where('activity_id', $source->id)->get();
        if ($ratings->isNotEmpty()) {
            $actionsTaken[] = 'Ratings(' . $ratings->count() . ')';
            $this->ratingsFixed += $ratings->count();
            if (!$isDryRun) {
                ActivityRating::where('activity_id', $source->id)->update(['activity_id' => $target->id]);
            }
        }

        // 7. Especialidades (Skills)
        if ($source->skills()->exists()) {
            $missingSkillIds = $source->skills->pluck('id')->diff($target->skills->pluck('id'));
            if ($missingSkillIds->isNotEmpty()) {
                $actionsTaken[] = 'Skills(' . $missingSkillIds->count() . ')';
                if (!$isDryRun) {
                    $target->skills()->syncWithoutDetaching($missingSkillIds->toArray());
                }
            }
        }

        // 8. Asignaciones (ActivityAssignments)
        $assignmentsCount = 0;
        foreach ($source->assignments as $a) {
            $exists = $target->assignments()
                ->where('user_id', $a->user_id)
                ->where('group_id', $a->group_id)
                ->exists();

            if (!$exists) {
                $assignmentsCount++;
                if (!$isDryRun) {
                    $target->assignments()->create([
                        'user_id' => $a->user_id,
                        'group_id' => $a->group_id,
                        'assigned_by_id' => $a->assigned_by_id,
                        'assigned_at' => $a->assigned_at,
                    ]);
                }
            }
        }

        if ($legacyTask && $target->assignments()->doesntExist()) {
            $legacyAssignments = DB::table('task_assignments')->where('task_id', $legacyTask->id)->get();
            foreach ($legacyAssignments as $la) {
                $assignmentsCount++;
                if (!$isDryRun) {
                    $target->assignments()->create([
                        'user_id' => $la->user_id,
                        'group_id' => $la->group_id,
                        'assigned_by_id' => $la->assigned_by_id,
                        'assigned_at' => $la->assigned_at,
                    ]);
                }
            }
        }

        if ($assignmentsCount > 0) {
            $actionsTaken[] = 'Asignaciones(' . $assignmentsCount . ')';
            $this->assignmentsCopied += $assignmentsCount;
        }

        // 9. Adjuntos (ActivityAttachments)
        $attachmentsCount = 0;
        foreach ($source->attachments as $att) {
            $exists = $target->attachments()->where('file_path', $att->file_path)->exists();
            if (!$exists) {
                $attachmentsCount++;
                if (!$isDryRun) {
                    $target->attachments()->create([
                        'uploaded_by_id' => $att->uploaded_by_id,
                        'file_name' => $att->file_name,
                        'file_path' => $att->file_path,
                        'mime_type' => $att->mime_type,
                        'disk' => $att->disk,
                        'file_size' => $att->file_size,
                        'label' => $att->label,
                    ]);
                }
            }
        }

        if ($legacyTask) {
            $taskAttachments = TaskAttachment::where('attachable_type', Task::class)
                ->where('attachable_id', $legacyTask->id)
                ->get();

            foreach ($taskAttachments as $ta) {
                $exists = $target->attachments()->where('file_path', $ta->file_path)->exists();
                if (!$exists) {
                    $attachmentsCount++;
                    if (!$isDryRun) {
                        $target->attachments()->create([
                            'uploaded_by_id' => $ta->user_id,
                            'file_name' => $ta->file_name,
                            'file_path' => $ta->file_path,
                            'mime_type' => $ta->mime_type,
                            'disk' => $ta->storage_provider ?: 'public',
                            'file_size' => $ta->file_size,
                        ]);
                    }
                }
            }
        }

        if ($attachmentsCount > 0) {
            $actionsTaken[] = 'Adjuntos(' . $attachmentsCount . ')';
            $this->attachmentsCopied += $attachmentsCount;
        }

        // 10. Notas (ActivityNotes)
        $notesCount = 0;
        foreach ($source->notes as $note) {
            $exists = $target->notes()
                ->where('content', $note->content)
                ->where('user_id', $note->user_id)
                ->exists();

            if (!$exists) {
                $notesCount++;
                if (!$isDryRun) {
                    $target->notes()->create([
                        'user_id' => $note->user_id,
                        'content' => $note->content,
                        'visibility' => $note->visibility ?? 'public',
                    ]);
                }
            }
        }

        if ($legacyTask) {
            $legacyNotes = TaskPrivateNote::where('task_id', $legacyTask->id)->get();
            foreach ($legacyNotes as $ln) {
                $exists = $target->notes()
                    ->where('content', $ln->content)
                    ->where('user_id', $ln->user_id)
                    ->exists();

                if (!$exists) {
                    $notesCount++;
                    if (!$isDryRun) {
                        $target->notes()->create([
                            'user_id' => $ln->user_id,
                            'content' => $ln->content,
                            'visibility' => 'private',
                        ]);
                    }
                }
            }
        }

        if ($notesCount > 0) {
            $actionsTaken[] = 'Notas(' . $notesCount . ')';
            $this->notesCopied += $notesCount;
        }

        // 11. Etiquetas (Tags)
        $tagsCount = 0;
        foreach ($source->tags as $tag) {
            $exists = $target->tags()->where('tag_id', $tag->tag_id)->exists();
            if (!$exists) {
                $tagsCount++;
                if (!$isDryRun) {
                    $target->tags()->create(['tag_id' => $tag->tag_id]);
                }
            }
        }

        if ($tagsCount > 0) {
            $actionsTaken[] = 'Etiquetas(' . $tagsCount . ')';
            $this->tagsCopied += $tagsCount;
        }
    }
}
