<?php

// SPDX-License-Identifier: AGPL-3.0-or-larger
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Http\Controllers;

use App\Models\KanbanColumn;
use App\Models\Activity;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HandlesPersistentFilters;
use App\Actions\Kanban\BuildKanbanDataAction;
use App\Actions\Kanban\SyncKanbanColumnAction;
use App\Actions\Kanban\StoreKanbanColumnAction;
use App\Actions\Kanban\UpdateKanbanColumnAction;
use App\Actions\Kanban\DestroyKanbanColumnAction;
use App\Actions\Kanban\UpdateTasksOrderAction;
use App\Actions\Kanban\UpdateColumnOrderAction;

/**
 * Controlador para la gestión del tablero Kanban de actividades de un equipo.
 *
 * Maneja la visualización de columnas Kanban, actualización de tareas mediante drag-and-drop,
 * sincronización automática de estado/progreso según la columna destino, gestión de columnas
 * (crear, actualizar, ordenar, eliminar), y gamificación al completar tareas.
 */
class KanbanController extends Controller
{
    use HandlesPersistentFilters;

    /**
     * Muestra el tablero Kanban del equipo con sus columnas y actividades filtradas.
     *
     * Sincroniza las columnas Kanban de actividades con su progreso real (0%, 1-99%, 100%),
     * aplica filtros persistentes (status, priority, assigned_to, skill_id, type, search, expediente_id),
     * y carga las 50 actividades completadas más recientes.
     *
     * @param  \Illuminate\Http\Request  $request  Filtros opcionales en query string
     * @param  \App\Models\Team  $team  Equipo a visualizar
     * @return \Illuminate\View\View Vista 'tasks.kanban' con datos del tablero
     */
    public function index(Request $request, Team $team)
    {
        if (auth()->user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }

        $this->ensureDefaultColumnsExist($team);

        $user = auth()->user();
        $isManager = $team->isManager($user);

        // Sync activities to ensure their column matches their actual progress (0%, 1-99%, 100%)
        $team->activities()
            ->forKanban()
            ->operationalForKanban($user, $team)
            ->notEphemeral()
            ->where('is_archived', false)
            ->get()->each(function ($activity) {
                $activity->syncKanbanColumn();
            });

        $buildAction = app(BuildKanbanDataAction::class);
        $data = $buildAction->execute($team, $request, $user, $isManager);

        $hideCompleted = session('hide_completed_tasks', true);

        return view('tasks.kanban', array_merge($data, [
            'team' => $team,
            'hideCompleted' => $hideCompleted,
        ]));
    }

    /**
     * Actualiza la posición de una actividad en el tablero Kanban (API JSON).
     *
     * Al mover una tarjeta, sincroniza bidireccionalmente el estado y progreso:
     * - 'todo' → 0% + estado pending/draft/scheduled/proposed
     * - 'done' → 100% + estado completed/approved/finished/accepted
     * - 'in_progress'/'custom' → 10% o 50% según configuración
     *
     * Propaga el progreso hacia arriba en la jerarquía de padres, registra historial,
     * y otorga puntos de gamificación si la tarea se completa al moverla.
     *
     * @param  \Illuminate\Http\Request  $request  Debe contener kanban_column_id, kanban_order (opcional)
     * @param  \App\Models\Team  $team  Equipo del tablero
     * @param  \App\Models\Activity  $task  Actividad a mover
     * @return \Illuminate\Http\JsonResponse Respuesta con success, status, progress
     */
    public function update(Request $request, Team $team, Activity $task)
    {
        if (auth()->user()->cannot('view', $team)) {
            return response()->json(['success' => false, 'message' => __('teams.unauthorized_access')], 403);
        }

        $this->authorize('update', $task);

        $validated = $request->validate([
            'kanban_column_id' => 'required|exists:kanban_columns,id',
            'kanban_order' => 'nullable|integer',
        ]);

        $column = KanbanColumn::findOrFail($validated['kanban_column_id']);

        if ($column->team_id !== $team->id) {
            abort(403);
        }

        $syncAction = app(SyncKanbanColumnAction::class);
        $result = $syncAction->execute($task, $column, true);

        return response()->json([
            'success' => true,
            'status' => $result['status'],
            'progress' => $result['progress'],
        ]);
    }

    /**
     * Actualiza el título o color de una columna Kanban.
     *
     * @param  \Illuminate\Http\Request  $request  Debe contener title (opcional) y/o color (opcional)
     * @param  \App\Models\Team  $team  Equipo de la columna
     * @param  \App\Models\KanbanColumn  $column  Columna a actualizar
     * @return \Illuminate\Http\JsonResponse Respuesta con success=true
     */
    public function updateColumn(Request $request, Team $team, KanbanColumn $column)
    {
        $action = app(UpdateKanbanColumnAction::class);
        $result = $action->execute($team, $column, $request);

        return response()->json($result);
    }

    /**
     * Crea una nueva columna Kanban personalizada para el equipo.
     *
     * La columna se crea con type='custom' y se posiciona al final según order_index.
     *
     * @param  \Illuminate\Http\Request  $request  Debe contener title (obligatorio, máx 255), color (opcional)
     * @param  \App\Models\Team  $team  Equipo al que pertenece la columna
     * @return \Illuminate\Http\JsonResponse Respuesta con success=true y la columna creada
     */
    public function storeColumn(Request $request, Team $team)
    {
        $action = app(StoreKanbanColumnAction::class);
        $result = $action->execute($team, $request);

        return response()->json($result);
    }

    /**
     * Actualiza el orden de múltiples tareas dentro de una columna Kanban.
     *
     * Si la tarea actual es la que fue movida (moved_task_id), también actualiza
     * el estado, progreso y registra historial. Otorga puntos de gamificación
     * si la tarea se completa al moverla a la columna 'done'.
     *
     * @param  \Illuminate\Http\Request  $request  Debe contener column_id, tasks (array de {id, kanban_order}), moved_task_id (opcional)
     * @param  \App\Models\Team  $team  Equipo del tablero
     * @return \Illuminate\Http\JsonResponse Respuesta con success=true y progress de la tarea movida (opcional)
     */
    public function updateTasksOrder(Request $request, Team $team)
    {
        $action = app(UpdateTasksOrderAction::class);
        $result = $action->execute($team, $request);

        return response()->json($result);
    }

    /**
     * Actualiza el orden de las columnas del tablero Kanban.
     *
     * @param  \Illuminate\Http\Request  $request  Debe contener columns (array de {id, order_index})
     * @param  \App\Models\Team  $team  Equipo del tablero
     * @return \Illuminate\Http\JsonResponse Respuesta con success=true
     */
    public function updateColumnOrder(Request $request, Team $team)
    {
        $action = app(UpdateColumnOrderAction::class);
        $result = $action->execute($team, $request);

        return response()->json($result);
    }

    /**
     * Elimina una columna Kanban personalizada.
     *
     * No permite eliminar columnas base del sistema (todo, in_progress, done).
     * Antes de eliminar, reasigna todas las actividades de la columna a la columna 'todo'.
     *
     * @param  \Illuminate\Http\Request  $request  Debe estar autenticado
     * @param  \App\Models\Team  $team  Equipo de la columna
     * @param  \App\Models\KanbanColumn  $column  Columna a eliminar
     * @return \Illuminate\Http\JsonResponse Respuesta con success=true, o error 422 si es columna base
     */
    public function destroyColumn(Request $request, Team $team, KanbanColumn $column)
    {
        $action = app(DestroyKanbanColumnAction::class);
        $result = $action->execute($team, $column);

        if (isset($result['message'])) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    /**
     * Crea las columnas Kanban por defecto si el equipo no tiene ninguna.
     *
     * Las columnas por defecto son: Todo (0%), En Progreso (50%), Hecho (100%).
     *
     * @param  \App\Models\Team  $team  Equipo a inicializar
     */
    protected function ensureDefaultColumnsExist(Team $team)
    {
        if ($team->kanbanColumns()->count() === 0) {
            $defaults = [
                ['title' => __('tasks.statuses.pending'), 'type' => 'todo', 'order_index' => 1, 'default_progress' => 0, 'color' => '#fee2e2'],
                ['title' => __('tasks.statuses.in_progress'), 'type' => 'in_progress', 'order_index' => 2, 'default_progress' => 50, 'color' => '#dbeafe'],
                ['title' => __('tasks.statuses.completed'), 'type' => 'done', 'order_index' => 3, 'default_progress' => 100, 'color' => '#dcfce7'],
            ];

            foreach ($defaults as $default) {
                $team->kanbanColumns()->create($default);
            }
        }
    }
}
