<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Task;
use App\Models\Activity;
use App\Factories\ActivityFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * Controlador para exportar, importar, clonar y copiar actividades entre equipos.
 *
 * Soporta clonación local, copiado entre equipos (usando ActivityFactory para
 * un puente 100% fiel), importación/exportación JSON, y mantenimiento de
 * todas las relaciones (skills, tags, assignments, attachments, history).
 */
class TaskExportController extends Controller
{
    protected ActivityFactory $activityFactory;

    public function __construct(ActivityFactory $activityFactory)
    {
        $this->activityFactory = $activityFactory;
    }

    /**
     * Copia una actividad completa a otro equipo usando la ActivityFactory.
     *
     * Exporta la actividad mediante exportToJson (esquema v2: Core + Specs),
     * fuerza is_template=false, e importa en el equipo destino con makeFromJson.
     * Reasigna asignaciones al usuario actual y registra historial de clonación.
     *
     * @param  \Illuminate\Http\Request  $request  Debe contener target_team_id (obligatorio, debe ser accesible)
     * @param  \App\Models\Team  $team  Equipo origen de la actividad
     * @param  int  $taskId  ID de la actividad a copiar
     * @return \Illuminate\Http\JsonResponse Respuesta con success, message, y URL de la actividad clonada
     */
    public function copyToTeam(Request $request, Team $team, $taskId)
    {
        $task = Activity::find($taskId) ?? Task::find($taskId);
        if (!$task) {
            return response()->json(['success' => false, 'message' => __('Tarea no encontrada.')], 404);
        }

        $request->validate([
            'target_team_id' => 'required|exists:teams,id'
        ]);

        $user = auth()->user();
        if ($user->cannot('view', $team) || $task->team_id !== $team->id) {
            return response()->json(['success' => false, 'message' => 'Acceso no autorizado.'], 403);
        }

        $targetTeam = Team::find($request->target_team_id);
        if ($user->cannot('view', $targetTeam)) {
            return response()->json(['success' => false, 'message' => 'No tienes acceso al equipo de destino.'], 403);
        }

        // Usar la factoría universal para un puente de exportación/importación 100% fiel
        try {
            $newTask = DB::transaction(function () use ($task, $targetTeam, $user) {
                // 1. Exportar mediante esquema v2 (recolecta Core + Specs)
                $exportedArray = $this->activityFactory->exportToJson($task);
                
                // Forzar que no sea plantilla al reproducir
                $exportedArray['core']['is_template'] = false;

                // 2. Importar en el equipo de destino mediante la factoría
                $jsonContent = json_encode($exportedArray);
                $cloned = $this->activityFactory->makeFromJson($targetTeam, $jsonContent);
                // 3. Ajustes específicos de la reproducción
                $cloned->assignedTo()->syncWithPivotValues([$user->id], [
                    'assigned_by_id' => $user->id,
                    'assigned_at' => now(),
                ]);

                // 4. Crear registro de historial
                $cloned->histories()->create([
                    'user_id' => $user->id,
                    'action'  => 'cloned',
                    'notes'   => 'Reproducida desde el equipo: ' . $task->team->name
                ]);

                return $cloned;
            });

            return response()->json([
                'success' => true,
                'message' => __('tasks.cloned_success', ['team' => $targetTeam->name]),
                'url'     => route('teams.activities.show', [$targetTeam, $newTask])
            ]);
        } catch (\Exception $e) {
            Log::error('Error en copyToTeam mediante ActivityFactory: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => __('Error al copiar la actividad: ') . $e->getMessage()], 500);
        }
    }

    /**
     * Clona una actividad dentro del mismo equipo (redirección con mensaje).
     *
     * Copia todos los campos, skills, tags, y asignaciones de usuario/grupo.
     * Sincroniza la columna Kanban y registra historial de clonación.
     *
     * @param  \Illuminate\Http\Request  $request  Debe estar autenticado
     * @param  \App\Models\Team  $team  Equipo de la actividad
     * @param  int  $taskId  ID de la actividad a clonar
     * @return \Illuminate\Http\RedirectResponse Redirección a la edición de la tarea clonada con mensaje de éxito
     */
    public function cloneTask(Request $request, Team $team, $taskId)
    {
        $task = Activity::find($taskId) ?? Task::find($taskId);
        if (!$task) {
            return redirect()->back()->with('error', __('Tarea no encontrada.'));
        }

        $user = auth()->user();
        if ($user->cannot('view', $team) || $task->team_id !== $team->id) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }

        if ($user->cannot('create', [Activity::class, $team]) && $user->cannot('create', [Task::class, $team])) {
            return redirect()->back()->with('warning', 'No tienes permisos para crear tareas.');
        }

        $clonedTask = DB::transaction(function () use ($task, $team, $user) {
            // 1. Crear la copia preservando de forma íntegra los metadatos (specs)
            $newTitle = '[Clon] ' . $task->title;
            if (mb_strlen($newTitle) > 255) {
                $newTitle = mb_substr($newTitle, 0, 252) . '...';
            }

            $new = $team->activities()->create([
                'title'                => $newTitle,
                'description'          => $task->description,
                'priority'             => $task->priority,
                'urgency'              => $task->urgency,
                'status'               => 'pending',
                'progress_percentage'  => 0,
                'scheduled_date'       => $task->scheduled_date,
                'due_date'             => $task->due_date,
                'original_due_date'    => $task->due_date,
                'created_by_id'        => $user->id,
                'parent_id'            => $task->parent_id,
                'is_template'          => $task->is_template,
                'visibility'           => $task->visibility,
                'is_autoprogrammable'  => $task->is_autoprogrammable,
                'autoprogram_settings' => $task->autoprogram_settings,
                'is_out_of_skill_tree' => $task->is_out_of_skill_tree,
                'cognitive_load'       => $task->cognitive_load,
                'is_backstage'         => $task->is_backstage,
                'service_id'           => $task->service_id,
                'expediente_id'        => $task->expediente_id,
                'is_timeline_locked'   => $task->is_timeline_locked,
                'metadata'             => $task->metadata, // Traspaso garantizado de specs
                'type'                 => $task->type ?? 'task',
            ]);

            // Sincronizar columna Kanban
            if (method_exists($new, 'syncKanbanColumn')) {
                $new->syncKanbanColumn();
            }

            // 2. Sincronizar Skills
            if ($task->skills->isNotEmpty()) {
                $new->skills()->sync($task->skills->pluck('id')->toArray());
            }

            // 3. Sincronizar Tags
            if ($task->tags && $task->tags->isNotEmpty()) {
                $new->tags()->sync($task->tags->pluck('id')->toArray());
            }

            // 4. Sincronizar Asignaciones de Usuarios y Grupos
            $assignments = [];
            $assignedBy = $user->id;
            $assignedAt = now();
            
            foreach ($task->assignedTo->pluck('id') as $uid) {
                $assignments[] = [
                    'activity_id' => $new->id,
                    'user_id' => $uid,
                    'group_id' => null,
                    'assigned_by_id' => $assignedBy,
                    'assigned_at' => $assignedAt,
                    'created_at' => $assignedAt,
                    'updated_at' => $assignedAt,
                ];
            }
            
            foreach ($task->assignedGroups->pluck('id') as $gid) {
                $assignments[] = [
                    'activity_id' => $new->id,
                    'user_id' => null,
                    'group_id' => $gid,
                    'assigned_by_id' => $assignedBy,
                    'assigned_at' => $assignedAt,
                    'created_at' => $assignedAt,
                    'updated_at' => $assignedAt,
                ];
            }
            
            if (!empty($assignments)) {
                \App\Models\ActivityAssignment::insert($assignments);
            }

            // Crear registro de historial
            $new->histories()->create([
                'user_id' => $user->id,
                'action'  => 'cloned',
                'notes'   => 'Clonado desde la tarea ID: ' . $task->id
            ]);

            return $new;
        });

        return redirect()->route('teams.activities.edit', [$team, $clonedTask])->with('success', 'Tarea clonada con éxito: "' . $clonedTask->title . '"');
    }

    /**
     * Importa una actividad desde un archivo JSON o contenido JSON directo.
     *
     * Usa ActivityFactory::makeFromJson para crear la actividad con todas sus
     * relaciones (specs, skills, tags, assignments, attachments).
     *
     * @param  \Illuminate\Http\Request  $request  Debe contener file (JSON, máx 2MB) o json_content
     * @param  \App\Models\Team  $team  Equipo destino de la importación
     * @return \Illuminate\Http\JsonResponse Respuesta con success, message, y URL de la actividad creada
     */
    public function importJson(Request $request, Team $team)
    {
        if (auth()->user()->cannot('create', [Activity::class, $team]) && auth()->user()->cannot('create', [Task::class, $team])) {
            return response()->json(['success' => false, 'message' => __('No tienes permisos para crear tareas en este equipo.')], 403);
        }
        $request->validate([
            'file'         => 'required_without:json_content|file|mimes:json|max:2048', // Max 2MB
            'json_content' => 'required_without:file|string|max:2000000|nullable'       // Max ~2MB en texto
        ]);

        if ($request->hasFile('file')) {
            $json = file_get_contents($request->file('file')->getRealPath());
        } else {
            $json = $request->json_content;
        }

        try {
            $task = $this->activityFactory->makeFromJson($team, $json);
            return response()->json([
                'success' => true, 
                'message' => __('Tarea importada correctamente.'), 
                'url'     => route('teams.activities.show', [$team, $task])
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error en importJson mediante ActivityFactory: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => __('Formato de datos JSON inválido o error interno.')], 500);
        }
    }

    /**
     * Exporta una actividad a formato JSON (API o descarga de archivo).
     *
     * Usa ActivityFactory::exportToJson para generar un esquema v2 completo
     * con Core + Specs de la actividad y todas sus relaciones.
     *
     * @param  \Illuminate\Http\Request  $request  Debe estar autenticado
     * @param  \App\Models\Team  $team  Equipo de la actividad
     * @param  int  $taskId  ID de la actividad a exportar
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse Respuesta JSON o descarga de archivo
     */
    public function exportJson(Request $request, Team $team, $taskId)
    {
        $task = Activity::find($taskId) ?? Task::find($taskId);
        if (!$task || $task->team_id !== $team->id) {
            abort(404);
        }
        if (auth()->user()->cannot('view', $task)) {
            abort(403);
        }

        $data = $this->activityFactory->exportToJson($task);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($data);
        }

        $filename = 'activity-' . Str::slug($task->title) . '-' . date('YmdHis') . '.json';

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, ['Content-Type' => 'application/json']);
    }
}

