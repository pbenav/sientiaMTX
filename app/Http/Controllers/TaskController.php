<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Activity;
use App\Models\Team;
use App\Models\TaskAttachment;
use App\Models\AttachmentLog;
use App\Traits\HandlesEisenhowerMatrix;
use App\Traits\AwardsGamification;
use App\Traits\ManagesTaskDeletion;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskEventNotification;
use Illuminate\Http\Request;

use App\Traits\HandlesPersistentFilters;

/**
 * Controlador legacy de Tareas (migrado a Actividades).
 *
 * Este controlador actúa como capa de compatibilidad: la mayoría de sus rutas
 * redirigen al nuevo ActivityController. Solo index(), create(), search() y merge()
 * conservan lógica propia para tareas legacy.
 *
 * @deprecated Usar ActivityController para todas las operaciones de tareas.
 */
class TaskController extends Controller
{
    use HandlesEisenhowerMatrix, AwardsGamification, ManagesTaskDeletion, HandlesPersistentFilters;

    /**
     * Listado de tareas legacy con filtros persistentes, paginación y ordenamiento.
     *
     * Aplica filtros por estado, prioridad, asignado, habilidad, tipo (template/instance/plain),
     * búsqueda y por_page. Excluye tareas completadas por defecto (session preference).
     * Redirige a activities.index — la lógica real está en ActivityController.
     *
     * @param  Request  $request
     * @param  Team  $team
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function index(Request $request, Team $team)
    {
        return redirect()->route('teams.activities.index', $team);
        $user = auth()->user();
        $isManager = $team->isManager($user);

        $query = $team->tasks()
            ->visibleTo($user, $isManager)
            ->operationalFor($user, $team, true)
            ->with([
                'assignedUser',
                'tags',
                'creator',
                'parent',
                'expediente',
                'children' => function($q) use ($user, $isManager) {
                    $q->visibleTo($user, $isManager);
                }
            ]);

        // --- Filters ---
        $filters = $this->getPersistentFilters($request, 'tasks', [
            'status', 'priority', 'assigned_to', 'skill_id', 'type', 'search', 'per_page'
        ]);

        if ($filters['status'] ?? null) {
            if ($filters['status'] === 'trashed') {
                $query->onlyTrashed();
            } else {
                $query->where('status->value', $filters['status']);
            }
        }

        if ($filters['priority'] ?? null) {
            $query->where('priority', $filters['priority']);
        }

        if ($filters['assigned_to'] ?? null) {
            $query->where('assigned_user_id', $filters['assigned_to']);
        }

        if ($filters['skill_id'] ?? null) {
            $skillId = $filters['skill_id'];
            $query->where(function ($q) use ($skillId) {
                $q->where('skill_id', $skillId)
                  ->orWhereHas('skills', fn($sk) => $sk->where('skills.id', $skillId));
            });
        }

        if ($filters['type'] ?? null) {
            if ($filters['type'] === 'template') {
                $query->where('is_template', true);
            } elseif ($filters['type'] === 'instance') {
                $query->where('is_template', false)->whereNotNull('parent_id');
            } elseif ($filters['type'] === 'plain') {
                $query->where('is_template', false)->whereNull('parent_id');
            }
        }

        if ($filters['search'] ?? null) {
            $searchTerm = $filters['search'];
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('parent', function($pq) use ($searchTerm) {
                      $pq->where('title', 'like', '%' . $searchTerm . '%');
                  });
            });

            // Also filter the children relationship so only matched subtasks are shown in the nested view
            $query->with(['children' => function($q) use ($searchTerm, $user, $isManager) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                  ->visibleTo($user, $isManager);
            }]);
        }

        // --- Sorting ---
        $sort = $request->get('sort');
        $direction = $request->get('direction', 'asc');

        $allowedSorts = ['title', 'status', 'priority', 'due_date', 'created_at', 'progress_percentage'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction === 'desc' ? 'desc' : 'asc');
        } else {
            // Default sort: Priority (Critical -> Low), Status (Pending -> Others), and Progress (High -> Low)
            $query->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low') ASC")
                  ->orderByRaw("FIELD(json_unquote(json_extract(status, '$.value')), 'pending', 'blocked', 'in_progress', 'completed', 'cancelled') ASC")
                  ->orderBy('progress_percentage', 'desc');
        }

        // --- Hide completed filter (session-based preference) ---
        if (session('hide_completed_tasks', true) && !$filters['status']) {
            $query->whereNotIn('status->value', ['completed', 'cancelled']);
        }

        // --- Pagination ---
        $perPage = $filters['per_page'] ?? 10;
        if (!in_array($perPage, [10, 25, 50, 100, 'all'])) {
            $perPage = 10;
        }

        if ($perPage === 'all') {
            // Secure "all" fetch
            $tasks = $query->paginate($query->count())->withQueryString();
        } else {
            $tasks = $query->paginate($perPage)->withQueryString();
        }
        $members = $team->members;
        $skills = \App\Models\Skill::forTeamOrGlobal($team->id)->get();
        $hideCompleted = session('hide_completed_tasks', true);

        $services = $team->services()->with(['reports' => function($q) {
            $q->latest()->limit(5);
        }])->get();

        return view('tasks.index', compact('team', 'tasks', 'members', 'hideCompleted', 'skills', 'services', 'filters'));
    }

    /**
     * Muestra el formulario de creación de una nueva tarea.
     *
     * Carga miembros, grupos, habilidades, servicios, expedientes y tareas existentes
     * como referencias para el formulario. Guarda el referer en session para retorno posterior.
     *
     * @param  Team  $team
     * @return \Illuminate\View\View
     */
    public function create(Team $team)
    {
        if (auth()->user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }
        $allMembers = $team->members; // All members — for owner selector
        // Exclude the current user from assignee list: creator is implicit owner
        // Allow the current user to be assigned as well so they can generate instances for themselves if they wish
        $users = $team->members;
        $groups = $team->groups;
        $priorities = ['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'critical' => 'Crítica'];
        $tasks = $team->tasks()->with('assignedUser')->orderBy('title')->get();
        $skills = \App\Models\Skill::forTeamOrGlobal($team->id)->orderBy('name')->get();
        $services = $team->services()->orderBy('name')->get();

        $referer = request()->headers->get('referer');
        if ($referer && str_starts_with($referer, url('/'))) {
            if (!str_contains($referer, "/tasks/create")) {
                session()->put("back_url_task_create_{$team->id}", $referer);
            }
        }
        $backUrl = session("back_url_task_create_{$team->id}", route('teams.dashboard', $team));

        $expedientes = $team->expedientes()->orderBy('code', 'desc')->get();
        $selectedExpedienteId = request('expediente_id');

        return view('tasks.create', compact('team', 'users', 'allMembers', 'groups', 'priorities', 'tasks', 'backUrl', 'skills', 'services', 'expedientes', 'selectedExpedienteId'));
    }

    /**
     * Almacena una nueva tarea, verificando cuota de almacenamiento del equipo.
     *
     * Delegación a TaskService::createTask(). Si los archivos exceden la cuota disponible,
     * retorna con error.
     *
     * @param  \App\Http\Requests\StoreTaskRequest  $request
     * @param  Team  $team
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(\App\Http\Requests\StoreTaskRequest $request, Team $team)
    {
        $validated = $request->validated();

        // Quota check before hitting the service
        if ($request->hasFile('attachments')) {
            $totalUploadSize = collect($request->file('attachments'))->sum(fn($file) => $file->getSize());
            if (!$team->hasAvailableQuota($totalUploadSize)) {
                return back()->withInput()->withErrors(['attachments' => '⚠️ El equipo ha alcanzado su límite de almacenamiento. Libera espacio para subir más archivos.']);
            }
        }

        $taskService = app(\App\Services\TaskService::class);
        $task = $taskService->createTask(
            $team,
            $validated,
            $request->file('attachments'),
            $request->input('drive_attachments')
        );

        return redirect()->route('teams.tasks.show', [$team, $task])
            ->with('success', __('tasks.created'));
    }

    /**
     * Muestra el detalle de una tarea (redirige legacy al Activity show).
     *
     * Verifica que la tarea pertenece al equipo y que el usuario tiene permiso de visualización.
     * Redirige a teams.activities.show.
     *
     * @param  Team  $team
     * @param  Activity  $task
     * @return \Illuminate\Http\RedirectResponse
     */
    public function show(Team $team, Activity $task)
    {
        if ($task->team_id !== $team->id) {
            return redirect()->route('teams.dashboard', $team)->with('warning', __('tasks.not_found_in_team'));
        }

        if (auth()->user()->cannot('view', $team)) {
            return redirect()->route('dashboard')->with('warning', __('teams.unauthorized_access'));
        }

        // Redirect legacy task URLs to the new Activity system
        return redirect()->route('teams.activities.show', [$team, $task->id]);
    }

    /**
     * Muestra el formulario de edición de una tarea (redirige legacy al Activity edit).
     *
     * Verifica pertenencia al equipo y permisos. Redirige a teams.activities.edit.
     *
     * @param  Team  $team
     * @param  Activity  $task
     * @return \Illuminate\Http\RedirectResponse
     */
    public function edit(Team $team, Activity $task)
    {
        if ($task->team_id !== $team->id) {
            return redirect()->route('teams.dashboard', $team)->with('warning', __('tasks.not_found_in_team'));
        }

        if (auth()->user()->cannot('view', $team)) {
            return redirect()->route('dashboard')->with('warning', __('teams.unauthorized_access'));
        }

        // Redirect legacy task URLs to the new Activity system
        return redirect()->route('teams.activities.edit', [$team, $task->id]);
    }

    /**
     * Actualiza una tarea (redirige legacy al Activity update).
     *
     * Notifica al usuario que debe usar el nuevo sistema de Actividades.
     *
     * @param  \App\Http\Requests\UpdateTaskRequest  $request
     * @param  Team  $team
     * @param  Activity  $task
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(\App\Http\Requests\UpdateTaskRequest $request, Team $team, Activity $task)
    {
        // Redirect legacy task updates to the new Activity system
        return redirect()->route('teams.activities.update', [$team, $task->id])
            ->with('warning', 'Use the new Activity system for editing.');
    }

    /**
     * Elimina una tarea (redirige legacy al Activity destroy).
     *
     * @param  Team  $team
     * @param  Activity  $task
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Team $team, Activity $task)
    {
        // Redirect legacy task deletion to the new Activity system
        return redirect()->route('teams.activities.destroy', [$team, $task->id]);
    }

    /**
     * Búsqueda de tareas para autocompletado AJAX dentro del contexto del equipo.
     *
     * Busca en tareas legacy y, si no encuentra resultados, busca en Activities (subtipo task).
     * Soporta filtros por nivel jerárquico (top_level_only), exclusión de threads de foro
     * y exclusión por ID.
     *
     * @param  Request  $request
     * @param  Team  $team
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request, Team $team)
    {
        $queryTerm = $request->input('query');
        $excludeId = $request->input('exclude_id');

        if (auth()->user()->cannot('view', $team)) {
            return response()->json([]);
        }

        $tasks = $team->tasks()
            ->when($request->boolean('top_level_only'), fn($q) => $q->whereNull('parent_id'))
            ->when($request->boolean('exclude_forum_thread'), fn($q) => $q->whereDoesntHave('forumThread'))
            ->where('title', 'like', '%' . $queryTerm . '%')
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->visibleTo(auth()->user(), $team->isManager(auth()->user()))
            ->orderBy('updated_at', 'desc')
            ->limit(25)
            ->get(['id', 'title', 'status']);

        // Si no hay tareas legacy, intentar buscar en Activities (subtipo task)
        if ($tasks->isEmpty()) {
            $activities = $team->activities()
                ->when($request->boolean('top_level_only'), fn($q) => $q->whereNull('parent_id'))
                ->where('type', 'task')
                ->where('title', 'like', '%' . $queryTerm . '%')
                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                ->orderBy('updated_at', 'desc')
                ->limit(25)
                ->get(['id', 'title', 'status']);

            return response()->json($activities->map(fn($a) => [
                'id' => $a->id,
                'text' => $a->title . ' (' . strtoupper($a->status_value ?? ($a->status ?? '')) . ')',
            ]));
        }

        return response()->json($tasks->map(fn($t) => [
            'id' => $t->id,
            'text' => $t->title . ' (' . strtoupper($t->status) . ')',
        ]));
    }

    /**
     * Fusiona una tarea en otra tarea destino, centralizando todas las relaciones.
     *
     * Valida que la tarea destino pertenece al mismo equipo y que el usuario tiene permisos
     * de borrado sobre la origen y actualización sobre el destino. Delegación a TaskService::mergeTasks().
     *
     * @param  Request  $request
     * @param  Team  $team
     * @param  Activity  $task
     * @return \Illuminate\Http\RedirectResponse
     */
    public function merge(Request $request, Team $team, Activity $task)
    {
        $request->validate([
            'target_task_id' => 'required|exists:tasks,id',
        ]);

        $targetTask = Task::findOrFail($request->input('target_task_id'));

        if ($targetTask->id === $task->id) {
             return back()->with('warning', 'No puedes fusionar una tarea consigo misma.');
        }

        if ($targetTask->team_id !== $team->id) {
             return back()->with('warning', 'La tarea de destino debe pertenecer al mismo equipo.');
        }

        if (auth()->user()->cannot('delete', $task) || auth()->user()->cannot('update', $targetTask)) {
             return back()->with('warning', 'No tienes permisos suficientes para realizar esta operación.');
        }

        $taskService = app(\App\Services\TaskService::class);
        $taskService->mergeTasks($task, $targetTask);

        return redirect()->route('teams.tasks.show', [$team, $targetTask])
            ->with('success', 'La tarea ha sido fusionada y sus datos migrados correctamente.');
    }

    /**
     * Obtiene tareas por estado (endpoint API para AJAX).
     *
     * @param  Team  $team
     * @param  string  $status
     * @return \Illuminate\Http\JsonResponse
     */
    public function byStatus(Team $team, string $status)
    {
        $tasks = $team->tasks()
            ->visibleTo(auth()->user(), $team->isManager(auth()->user()))
            ->operationalFor(auth()->user(), $team)
            ->where('status->value', $status)
            ->with(['assignedTo', 'tags'])
            ->get();

        return response()->json($tasks);
    }

    /**
     * Obtiene tareas organizadas por cuadrante de la Matriz de Eisenhower.
     *
     * Calcula el cuadrante para cada tarea usando el trait HandlesEisenhowerMatrix::getQuadrant().
     * Aplica filtro de hide_completed_tasks de sesión.
     *
     * @param  Team  $team
     * @return \Illuminate\Http\JsonResponse
     */
    public function byQuadrant(Team $team)
    {
        $quadrants = [];

        foreach ([1, 2, 3, 4] as $q) {
            $quadrants[$q] = $team->tasks()
                ->visibleTo(auth()->user(), $team->isManager(auth()->user()))
                ->operationalFor(auth()->user(), $team)
                ->with(['assignedTo', 'tags'])
                ->when(session('hide_completed_tasks', true), fn($q) => $q->whereNotIn('status->value', ['completed', 'cancelled']))
                ->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low') ASC")
                ->orderByRaw("FIELD(json_unquote(json_extract(status, '$.value')), 'pending', 'blocked', 'in_progress', 'completed', 'cancelled') ASC")
                ->orderBy('progress_percentage', 'desc')
                ->get()
                ->filter(function ($task) use ($q) {
                    return $this->getQuadrant($task) === $q;
                })
                ->values();
        }

        return response()->json([
            'quadrants' => $quadrants,
            'hide_completed' => session('hide_completed_tasks', true),
        ]);
    }

    /**
     * Alterna la preferencia de ocultar tareas completadas (basada en sesión).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleHideCompleted()
    {
        $current = session('hide_completed_tasks', true);
        session(['hide_completed_tasks' => !$current]);

        return response()->json(['success' => true, 'hide_completed' => !$current]);
    }

    /**
     * Alterna la visibilidad de subtareas (basada en sesión).
     *
     * Si se proporciona el parámetro 'show', establece el valor absoluto.
     * De lo contrario, invierte el valor actual.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleSubtasksVisibility(Request $request)
    {
        // If 'show' is provided, we use it (absolute set). Otherwise, toggle.
        $current = session('show_all_subtasks', false);
        $show = $request->has('show') ? $request->boolean('show') : !$current;

        session(['show_all_subtasks' => $show]);

        return response()->json(['success' => true, 'show' => $show]);
    }




    /**
     * Sincronización manual: empuja cambios del template maestro a todas las instancias hijas.
     *
     * Actualiza título, descripción, fecha de vencimiento y prioridad de todas las instancias
     * generadas a partir de este template. Solo ejecutable por quien tenga permiso de actualización.
     *
     * @param  Request  $request
     * @param  Team  $team
     * @param  Activity  $task
     * @return \Illuminate\Http\RedirectResponse
     */
    public function syncToChildren(Request $request, Team $team, Activity $task)
    {
        $this->authorize('update', $task);

        if (!$task->is_template) {
            return redirect()->back()->with('error', 'Only templates can be synced.');
        }

        $task->instances()->update([
            'title' => $task->title,
            'description' => $task->description,
            'due_date' => $task->due_date,
            'priority' => $task->priority,
        ]);

        return redirect()->back()->with('success', __('tasks.synced_success'));
    }


    /**
     * Creación unificada de tareas a partir de datos estructurados.
     *
     * Usada por reproducción de datos e importación JSON.
     * Método reservado para futuras implementaciones.
     */
}
