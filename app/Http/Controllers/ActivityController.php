<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Http\Controllers;

use App\Http\Requests\StoreActivityRequest;
use App\Models\Activity;
use App\Models\ActivityAttachment;
use App\Models\ActivityNote;
use App\Models\Team;
use App\Services\ActivityService;
use App\Traits\HandlesPersistentFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Controlador principal de Actividades.
 *
 * Maneja el ciclo de vida completo de actividades:
 *   - Listado con filtros persistentes y paginación
 *   - Creación, edición, eliminación
 *   - Cambio de estado rápido
 *   - Archivo/Desarchivo
 *   - Conversión entre tipos (task→document→note, etc.)
 *   - Fusión de actividades deprecadas
 *   - Restauración de metadatos de versiones convertidas
 *
 * Delegación: toda la lógica de negocio va a ActivityService.
 */
class ActivityController extends Controller
{
    use HandlesPersistentFilters;

    /**
     * Servicio de actividades para delegar lógica de negocio.
     */
    protected ActivityService $activityService;

    /**
     * Inyecta el servicio de actividades.
     */
    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    /**
     * Listado unificado de actividades con filtros persistentes, paginación y datos para formularios.
     *
     * @param  Request  $request
     * @param  Team  $team
     * @return \Illuminate\View\View
     */
    public function index(Request $request, Team $team)
    {
        if (auth()->user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }

        // Filtros persistentes
        $filters = $this->getPersistentFilters($request, 'activities', [
            'type', 'status', 'priority', 'search', 'expediente_id', 'tag', 'archived',
            'assigned_to', 'skill_id', 'template_type', 'per_page'
        ]);

        $sort = $request->get('sort', 'due_date');
        $dir  = $request->get('direction', 'asc');

        $perPage = (int) ($filters['per_page'] ?? 15);
        if (!in_array($perPage, [10, 15, 20, 30, 50, 100])) {
            $perPage = 15;
        }

        $activities = $this->activityService->paginate($team, $filters, $perPage, $sort, $dir);

        $members = $team->members()->orderBy('name')->get();
        $skills = \App\Models\Skill::forTeamOrGlobal($team->id)->orderBy('name')->get();
        $expedientes = $team->expedientes()->orderBy('title')->get();

        return view('teams.activities.index', compact('team', 'activities', 'filters', 'sort', 'dir', 'members', 'skills', 'expedientes'));
    }

    /**
     * Búsqueda de actividades de tipo 'task' para autocompletado AJAX.
     *
     * Devuelve JSON con id, texto descriptivo y estado.
     * Soporta filtrado por nivel jerárquico, exclusión de threads de foro y término de búsqueda.
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

        $q = $team->activities()
            ->whereIn('type', array_keys(Activity::SUBTYPES))
            ->where('is_archived', false)
            ->where('is_template', false)
            ->when($request->boolean('top_level_only'), fn($q) => $q->whereNull('parent_id'))
            ->when($request->boolean('exclude_forum_thread'), fn($q) => $q->whereDoesntHave('forumThread'))
            ->when($queryTerm, fn($q) => $q->where('title', 'like', '%' . $queryTerm . '%'))
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->orderByRaw($queryTerm ? "CASE WHEN title LIKE ? THEN 0 ELSE 1 END, updated_at DESC" : "updated_at DESC",
                $queryTerm ? ['%' . $queryTerm . '%'] : [])
            ->limit(20);

        $activities = $q->get(['id', 'title', 'status', 'metadata', 'type']);

        $typeLabels = [
            'task'     => 'Tarea',
            'document' => 'Doc',
            'note'     => 'Nota',
            'link'     => 'Enlace',
            'agreement' => 'Acuerdo',
            'meeting'  => 'Reunión',
            'reminder' => 'Aviso',
        ];

        return response()->json($activities->map(fn($a) => [
            'id'   => $a->id,
            'text' => '[' . ($a->type ? ($typeLabels[$a->type] ?? $a->type) : 'Actividad') . '] ' . $a->title . ' — ' . strtoupper($a->status_value ?? ($a->status ?? '')),
        ]));
    }

    /**
     * Muestra el selector de tipos de actividad o el formulario de creación según el tipo seleccionado.
     *
     * Si no se especifica tipo, muestra la vista de selección. Si se especifica, carga todos los
     * datos necesarios para el formulario: miembros, grupos, expedientes, actividades padre,
     * habilidades, servicios y opciones de prioridad.
     *
     * @param  Request  $request
     * @param  Team  $team
     * @return \Illuminate\View\View
     */
    public function create(Request $request, Team $team)
    {
        if (auth()->user()->cannot('view', $team) || auth()->user()->cannot('create', [Activity::class, $team])) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }

        $type = $request->get('type');

        // Si no se especifica tipo, mostramos la pantalla de selección
        if (!$type || !array_key_exists($type, Activity::SUBTYPES)) {
            return view('teams.activities.select_type', compact('team'));
        }

        // Temporal escape hatch for heavy views with multiple large dropdowns
        ini_set('memory_limit', '256M');

        // Cargar datos necesarios para el formulario
        $members = $team->members()->select('users.id', 'users.name', 'users.email')->orderBy('users.name')->get();
        $groups  = $team->groups()->with('users:id')->select('groups.id', 'groups.name')->orderBy('groups.name')->get();
        $expedientes = $team->expedientes()->select('expedientes.id', 'expedientes.code', 'expedientes.title')->orderBy('expedientes.title')->get();
        
        // Actividades padre disponibles para jerarquía (no circulares)
        $parentActivities = Activity::with('creator:id,name')
            ->byTeam($team->id)
            ->active()
            ->where('is_template', false)
            ->select('id', 'title', 'created_by_id', 'created_at')
            ->latest()
            ->limit(50)
            ->get();

        $skills = \App\Models\Skill::forTeamOrGlobal($team->id)->orderBy('name')->get();
        $services = $team->services()->orderBy('name')->get();
        $priorities = ['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'critical' => 'Crítica'];

        return view('teams.activities.create', compact('team', 'type', 'members', 'groups', 'expedientes', 'parentActivities', 'skills', 'services', 'priorities'));
    }

    /**
     * Almacena una nueva actividad, verificando cuota de almacenamiento del equipo.
     *
     * @param  StoreActivityRequest  $request
     * @param  Team  $team
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreActivityRequest $request, Team $team)
    {
        $validated = $request->validated();
        $type = $validated['type'];

        // Quota check de archivos
        if ($request->hasFile('attachments')) {
            $totalUploadSize = collect($request->file('attachments'))->sum(fn($file) => $file->getSize());
            if (!$team->hasAvailableQuota($totalUploadSize)) {
                return back()->withInput()->withErrors(['attachments' => '⚠️ El equipo ha alcanzado su límite de almacenamiento. Libera espacio para subir más archivos.']);
            }
        }

        $activity = $this->activityService->create(
            $team,
            $type,
            $validated,
            $request->file('attachments') ?? []
        );

        return redirect()->route('teams.activities.show', [$team, $activity])
            ->with('success', __('activities.created_success'));
    }

    /**
     * Muestra el detalle completo de una actividad con notas e historial de cambios.
     *
     * @param  Team  $team
     * @param  Activity  $activity
     * @return \Illuminate\View\View
     */
    public function show(Team $team, Activity $activity)
    {
        if ($activity->team_id !== $team->id) {
            return redirect()->route('teams.dashboard', $team)->with('warning', __('activities.not_found_in_team'));
        }

        if (auth()->user()->cannot('view', $activity)) {
            abort(403, 'No tienes permiso para ver esta actividad.');
        }

        $activity = $activity->asSubtype();
        $notes = $activity->notes()->with('user')->get();
        $histories = $activity->histories()->with('user')->get();

        return view('teams.activities.show', compact('team', 'activity', 'notes', 'histories'));
    }

    /**
     * Muestra el formulario de edición de una actividad con todos los datos para dropdowns y plantillas.
     *
     * Carga la plantilla correspondiente al tipo de actividad para determinar estados permitidos.
     *
     * @param  Team  $team
     * @param  Activity  $activity
     * @return \Illuminate\View\View
     */
    public function edit(Team $team, Activity $activity)
    {
        if ($activity->team_id !== $team->id) {
            return redirect()->route('teams.dashboard', $team)->with('warning', __('activities.not_found'));
        }

        if (auth()->user()->cannot('update', $activity)) {
            abort(403, 'No tienes permiso para modificar esta actividad.');
        }

        // Temporal escape hatch for heavy views with multiple large dropdowns
        ini_set('memory_limit', '256M');

        $activity = $activity->asSubtype();
        $members = $team->members()->select('users.id', 'users.name', 'users.email')->orderBy('users.name')->get();
        $groups  = $team->groups()->with('users:id')->select('groups.id', 'groups.name')->orderBy('groups.name')->get();
        $expedientes = $team->expedientes()->select('expedientes.id', 'expedientes.code', 'expedientes.title')->orderBy('expedientes.title')->get();

        
        $parentActivities = Activity::with('creator:id,name')
            ->byTeam($team->id)
            ->active()
            ->where('id', '!=', $activity->id)
            ->where('is_template', false)
            ->select('id', 'title', 'created_by_id', 'created_at')
            ->latest()
            ->limit(50)
            ->get();

        $skills = \App\Models\Skill::forTeamOrGlobal($team->id)->orderBy('name')->get();
        $services = $team->services()->orderBy('name')->get();
        $priorities = ['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'critical' => 'Crítica'];
        $allStatuses = [
            'pending'     => 'Pendiente',
            'in_progress' => 'En Progreso',
            'completed'   => 'Completada',
            'cancelled'   => 'Cancelada',
            'blocked'     => 'Bloqueada',
            'draft'       => 'Borrador',
            'active'      => 'Activo',
            'proposed'    => 'Propuesto',
            'scheduled'   => 'Programado',
            'uploaded'    => 'Subido',
            'editing'     => 'En Edición',
            'reviewed'    => 'Revisado',
            'archived'    => 'Archivado',
            'reviewing'   => 'En Revisión',
            'approved'    => 'Aprobado',
            'rejected'    => 'Rechazado',
            'broken'      => 'Roto',
            'published'   => 'Publicado',
            'triggered'   => 'Disparado',
            'dismissed'   => 'Descartado',
            'deprecated'  => 'Deprecado'
        ];
        $templateLoader = app(\App\Services\TemplateLoader::class);
        $template = $templateLoader->getTemplate($activity->type);
        $allowedStates = array_keys($template['states'] ?? []);

        $statuses = [];
        if (!empty($allowedStates)) {
            foreach ($allowedStates as $state) {
                $statuses[$state] = $allStatuses[$state] ?? ucfirst($state);
            }
        } else {
            $statuses = [
                'pending'     => 'Pendiente',
                'in_progress' => 'En Progreso',
                'completed'   => 'Completada',
                'cancelled'   => 'Cancelada',
                'blocked'     => 'Bloqueada',
            ];
        }

        return view('teams.activities.edit', compact('team', 'activity', 'members', 'groups', 'expedientes', 'parentActivities', 'skills', 'services', 'priorities', 'statuses'));
    }

    /**
     * Actualiza una actividad, verificando cuota de almacenamiento y protegiendo integridad de acuerdos firmados.
     *
     * Si la actividad es un acuerdo con firmas vigentes, descarta el campo 'terms' del payload
     * para evitar modificar términos ya firmados.
     *
     * @param  StoreActivityRequest  $request
     * @param  Team  $team
     * @param  Activity  $activity
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(StoreActivityRequest $request, Team $team, Activity $activity)
    {
        if ($activity->team_id !== $team->id) {
            return redirect()->route('teams.dashboard', $team)->with('warning', __('activities.not_found'));
        }

        if (auth()->user()->cannot('update', $activity)) {
            abort(403, 'No tienes permiso para modificar esta actividad.');
        }

        $validated = $request->validated();

        if ($request->hasFile('attachments')) {
            $totalUploadSize = collect($request->file('attachments'))->sum(fn($file) => $file->getSize());
            if (!$team->hasAvailableQuota($totalUploadSize)) {
                return back()->withInput()->withErrors(['attachments' => '⚠️ El equipo ha alcanzado su límite de almacenamiento.']);
            }
        }

        // Protección de integridad: si el acuerdo ya tiene firmas,
        // ignorar cualquier intento de modificar los términos del documento.
        if ($activity->type === 'agreement') {
            $meta = $activity->metadata ?? [];
            $hasMemberSig = collect($meta['member_signatures'] ?? [])->contains(fn($s) => !empty($s['signed_at']));
            $hasGuestSig  = collect($meta['guests'] ?? [])->contains(fn($g) => !empty($g['signed_at']));

            if ($hasMemberSig || $hasGuestSig) {
                // Descartar el campo terms del payload para que no se sobreescriba
                if (isset($validated['metadata']['terms'])) {
                    unset($validated['metadata']['terms']);
                }
            }
        }

        $this->activityService->update($activity, $validated, $request->file('attachments') ?? []);

        return redirect()->route('teams.activities.show', [$team, $activity])
            ->with('success', __('activities.updated_success'));
    }

    /**
     * Elimina (soft-delete) una actividad.
     *
     * @param  Team  $team
     * @param  Activity  $activity
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Team $team, Activity $activity)
    {
        if ($activity->team_id !== $team->id) {
            return redirect()->route('teams.dashboard', $team)->with('warning', __('activities.not_found'));
        }

        if (auth()->user()->cannot('delete', $activity)) {
            abort(403, 'No tienes permiso para eliminar esta actividad.');
        }

        $this->activityService->delete($activity);

        return redirect()->route('teams.activities.index', $team)
            ->with('success', __('activities.deleted_success'));
    }

    /**
     * Archiva una actividad.
     *
     * @param  Team  $team
     * @param  Activity  $activity
     * @return \Illuminate\Http\RedirectResponse
     */
    public function archive(Team $team, Activity $activity)
    {
        if (auth()->user()->cannot('archive', $activity)) {
            abort(403);
        }

        $this->activityService->archive($activity);

        return back()->with('success', __('activities.archived_success'));
    }

    /**
     * Desarchiva una actividad.
     *
     * @param  Team  $team
     * @param  Activity  $activity
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unarchive(Team $team, Activity $activity)
    {
        if (auth()->user()->cannot('archive', $activity)) {
            abort(403);
        }

        $this->activityService->unarchive($activity);

        return back()->with('success', __('activities.unarchived_success'));
    }

    /**
     * Modifica el estado de una actividad de forma rápida.
     *
     * @param  Request  $request
     * @param  Team  $team
     * @param  Activity  $activity
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changeStatus(Request $request, Team $team, Activity $activity)
    {
        if (auth()->user()->cannot('changeStatus', $activity)) {
            abort(403);
        }

        $request->validate(['status' => 'required|string']);
        $this->activityService->changeStatus($activity, $request->get('status'));

        return back()->with('success', __('activities.status_updated'));
    }

    /**
     * Convierte una actividad a otro tipo (task→document→note, etc.), deprecando la versión anterior.
     *
     * @param  Request  $request
     * @param  Team  $team
     * @param  Activity  $activity
     * @param  \App\Actions\Activities\ConvertActivityAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function convert(Request $request, Team $team, Activity $activity, \App\Actions\Activities\ConvertActivityAction $action)
    {
        if ($activity->team_id !== $team->id) {
            return redirect()->route('teams.dashboard', $team)->with('warning', __('activities.not_found'));
        }

        $request->validate([
            'type' => 'required|string|in:' . implode(',', array_keys(Activity::SUBTYPES)),
        ]);

        $newType = $request->get('type');
        $result = $action->execute($activity, $newType, auth()->user());

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return redirect()->route('teams.activities.show', [$team, $result['activity']])
            ->with('success', $result['message']);
    }

    /**
     * Restaura una actividad que fue deprecada por conversión a otro tipo.
     *
     * Restablece el estado a 'pending', limpia metadatos de conversión y registra
     * el evento en el historial.
     *
     * @param  Team  $team
     * @param  Activity  $activity
     * @return \Illuminate\Http\RedirectResponse
     */
    public function restoreDeprecated(Team $team, Activity $activity)
    {
        if ($activity->team_id !== $team->id || !$activity->isDeprecatedByConversion()) {
            return back()->with('warning', __('activities.cannot_restore'));
        }

        if (auth()->user()->cannot('update', $activity)) {
            abort(403, 'No tienes permiso para restaurar esta actividad.');
        }

        $activity->is_archived = false;
        $status = $activity->status ?? [];
        $status['value'] = 'pending';
        unset($status['reason'], $status['converted_to_uuid'], $status['converted_at']);
        $activity->status = $status;

        $metadata = $activity->metadata ?? [];
        unset($metadata['converted_to_uuid'], $metadata['converted_to_id'], $metadata['is_deprecated']);
        $activity->metadata = $metadata;

        $activity->save();

        $activity->histories()->create([
            'user_id' => auth()->id(),
            'action' => 'restored_from_deprecation',
            'details' => json_encode(['note' => 'Actividad restaurada de estado deprecado por conversión'])
        ]);

        return redirect()->route('teams.activities.show', [$team, $activity])
            ->with('success', __('activities.restored_success'));
    }

    /**
     * Clona una actividad deprecada para crear un registro independiente limpio.
     *
     * @param  Team  $team
     * @param  Activity  $activity
     * @param  \App\Actions\Activities\CloneActivityAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cloneDeprecated(Team $team, Activity $activity, \App\Actions\Activities\CloneActivityAction $action)
    {
        if ($activity->team_id !== $team->id || !$activity->isDeprecatedByConversion()) {
            return back()->with('warning', __('activities.cannot_clone'));
        }

        if (auth()->user()->cannot('create', [Activity::class, $team])) {
            abort(403, 'No tienes permiso para crear actividades en este equipo.');
        }

        $cloned = $action->execute($activity, auth()->id());

        return redirect()->route('teams.activities.show', [$team, $cloned])
            ->with('success', __('activities.cloned_success'));
    }

    /**
     * Fusiona (Merge) notas y archivos de una actividad deprecada hacia otra actividad activa.
     *
     * @param  Request  $request
     * @param  Team  $team
     * @param  Activity  $activity
     * @param  \App\Actions\Activities\MergeActivityAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function mergeDeprecated(Request $request, Team $team, Activity $activity, \App\Actions\Activities\MergeActivityAction $action)
    {
        if ($activity->team_id !== $team->id || !$activity->isDeprecatedByConversion()) {
            return back()->with('warning', __('activities.cannot_merge'));
        }

        $request->validate([
            'target_activity_id' => 'required|exists:activities,id',
        ]);

        $target = Activity::byTeam($team->id)->active()->where('id', $request->get('target_activity_id'))->firstOrFail();

        if (auth()->user()->cannot('update', $target)) {
            abort(403, 'No tienes permiso para modificar la actividad destino.');
        }

        $action->execute($activity, $target, auth()->id());

        return redirect()->route('teams.activities.show', [$team, $target])
            ->with('success', __('activities.merged_success'));
    }

    /**
     * Restaura metadatos y configuraciones de la versión original de una actividad convertida.
     *
     * Recupera campos genéricos y metadatos del ancestro, conservando los enlaces de conversión
     * para mantener la trazabilidad de "vidas pasadas". Registra el evento en el historial.
     *
     * @param  Request  $request
     * @param  Team  $team
     * @param  Activity  $activity
     * @return \Illuminate\Http\RedirectResponse
     */
    public function restoreMetadata(Request $request, Team $team, Activity $activity)
    {
        if ($activity->team_id !== $team->id) {
            abort(404);
        }

        if (auth()->user()->cannot('update', $activity)) {
            abort(403, 'No tienes permiso para modificar esta actividad.');
        }

        $ancestor = $activity->convertedFromActivity;

        if (!$ancestor) {
            return back()->with('error', 'No se encontró el historial de conversión original.');
        }

        // Recuperar campos genéricos
        $activity->type = $ancestor->type;
        $activity->description = $ancestor->description;
        $activity->due_date = $ancestor->due_date;
        $activity->scheduled_date = $ancestor->scheduled_date;
        $activity->original_due_date = $ancestor->original_due_date;
        $activity->priority = $ancestor->priority;
        $activity->auto_priority = $ancestor->auto_priority;

        // Recuperar la estructura de metadatos del ancestro, pero mantener la trazabilidad
        $currentMetadata = $activity->metadata ?? [];
        $ancestorMetadata = $ancestor->metadata ?? [];
        
        // Mantener las claves de conversión de la actividad actual para no perder el enlace "vidas pasadas"
        $internalKeys = ['converted_from_uuid', 'converted_from_id'];
        $conversionLinks = [];
        foreach ($internalKeys as $k) {
            if (isset($currentMetadata[$k])) {
                $conversionLinks[$k] = $currentMetadata[$k];
            }
        }

        // Limpiar claves del ancestro que marcan que está deprecado
        unset($ancestorMetadata['converted_to_uuid'], $ancestorMetadata['converted_to_id'], $ancestorMetadata['is_deprecated']);

        // Metadatos finales: los del ancestro más los enlaces de conversión
        $finalMetadata = array_merge($ancestorMetadata, $conversionLinks);
        
        $activity->metadata = $finalMetadata;

        $activity->saveQuietly();

        $activity->histories()->create([
            'user_id' => auth()->id(),
            'action' => 'restored_metadata',
            'details' => json_encode(['from_uuid' => $ancestor->uuid])
        ]);

        return back()->with('success', 'Metadatos y configuraciones de la versión original restaurados correctamente.');
    }
}

