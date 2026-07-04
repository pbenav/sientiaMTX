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

class ActivityController extends Controller
{
    use HandlesPersistentFilters;

    protected ActivityService $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    /**
     * Listado unificado de actividades.
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
     * Search activities of subtype 'task' for autocomplete (AJAX).
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

        $activities = $q->get(['id', 'title', 'status', 'metadata']);

        $typeLabels = [
            'task'     => 'Tarea',
            'document' => 'Doc',
            'note'     => 'Nota',
            'link'     => 'Enlace',
            'decision' => 'Decisión',
            'meeting'  => 'Reunión',
            'reminder' => 'Aviso',
        ];

        return response()->json($activities->map(fn($a) => [
            'id'   => $a->id,
            'text' => '[' . ($typeLabels[$a->type] ?? $a->type) . '] ' . $a->title . ' — ' . strtoupper($a->status_value ?? ($a->status ?? '')),
        ]));
    }

    /**
     * Muestra el selector de tipos o el formulario de creación.
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
     * Almacena una nueva actividad.
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
     * Muestra el detalle de una actividad.
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
     * Muestra el formulario de edición de una actividad.
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
     * Actualiza la actividad.
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

        $this->activityService->update($activity, $validated, $request->file('attachments') ?? []);

        return redirect()->route('teams.activities.show', [$team, $activity])
            ->with('success', __('activities.updated_success'));
    }

    /**
     * Elimina (soft-delete) una actividad.
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
     * Modifica el estado rápidamente.
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
     * Añade una nota/comentario.
     */
    public function addNote(Request $request, Team $team, Activity $activity)
    {
        if (auth()->user()->cannot('addNote', $activity)) {
            abort(403);
        }

        $validated = $request->validate([
            'content' => 'required|string',
            'visibility' => 'required|in:private,internal',
        ]);

        ActivityNote::create([
            'activity_id' => $activity->id,
            'user_id' => auth()->id(),
            'content' => $validated['content'],
            'visibility' => $validated['visibility'],
        ]);

        return redirect()->route('teams.activities.show', [$team, $activity])->withFragment('notes')->with('success', __('activities.note_added'));
    }

    /**
     * Actualiza una nota/comentario.
     */
    public function updateNote(Request $request, Team $team, Activity $activity, ActivityNote $note)
    {
        if ($note->user_id !== auth()->id() && auth()->user()->cannot('delete', $activity)) {
            abort(403);
        }

        $validated = $request->validate([
            'content' => 'required|string',
            'visibility' => 'required|in:private,internal',
        ]);

        $note->update([
            'content' => $validated['content'],
            'visibility' => $validated['visibility'],
        ]);

        return redirect()->route('teams.activities.show', [$team, $activity])->withFragment('notes')->with('success', __('activities.note_updated'));
    }

    /**
     * Elimina una nota/comentario.
     */
    public function deleteNote(Team $team, Activity $activity, ActivityNote $note)
    {
        if ($note->user_id !== auth()->id() && auth()->user()->cannot('delete', $activity)) {
            abort(403);
        }

        $note->delete();

        return redirect()->route('teams.activities.show', [$team, $activity])->withFragment('notes')->with('success', __('activities.note_deleted'));
    }

    /**
     * Sube adjuntos.
     */
    public function uploadAttachment(Request $request, Team $team, Activity $activity)
    {
        if (auth()->user()->cannot('attach', $activity)) {
            abort(403);
        }

        $request->validate([
            'attachments' => 'required|array',
            'attachments.*' => 'file|max:' . (\Illuminate\Http\UploadedFile::getMaxFilesize() / 1024),
        ]);

        $totalUploadSize = collect($request->file('attachments'))->sum(fn($file) => $file->getSize());
        if (!$team->hasAvailableQuota($totalUploadSize)) {
            return back()->withErrors(['attachments' => '⚠️ El equipo ha alcanzado su límite de almacenamiento.']);
        }

        $this->activityService->handleAttachments($activity, $request->file('attachments'));

        return back()->with('success', __('activities.files_uploaded'));
    }

    /**
     * Elimina un adjunto.
     */
    public function deleteAttachment(Team $team, Activity $activity, ActivityAttachment $attachment)
    {
        if ($attachment->activity_id !== $activity->id || auth()->user()->cannot('update', $activity)) {
            abort(403);
        }

        $this->activityService->deleteAttachment($attachment);

        return back()->with('success', __('activities.file_deleted'));
    }

    /**
     * Descarga un adjunto.
     */
    public function downloadAttachment(Team $team, Activity $activity, ActivityAttachment $attachment)
    {
        if ($attachment->activity_id !== $activity->id) {
            abort(404);
        }

        if (auth()->user()->cannot('view', $activity)) {
            abort(403, 'No tienes permiso para acceder a este archivo.');
        }

        if ($attachment->disk === 'google_drive') {
            if ($attachment->file_path && filter_var($attachment->file_path, FILTER_VALIDATE_URL)) {
                return redirect()->away($attachment->file_path);
            }
            abort(404, 'Enlace de Google Drive no válido.');
        }

        if (!\Illuminate\Support\Str::startsWith($attachment->file_path, "activities/{$activity->id}/")) {
            abort(403, 'Ruta de archivo no válida.');
        }

        if (!\Illuminate\Support\Facades\Storage::disk($attachment->disk)->exists($attachment->file_path)) {
            return back()->with('error', __('activities.file_not_found'));
        }

        return \Illuminate\Support\Facades\Storage::disk($attachment->disk)->download($attachment->file_path, $attachment->file_name);
    }

    /**
     * Convierte una actividad a un nuevo tipo.
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
     * Restaura una actividad que fue deprecada por conversión.
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
     */
    public function cloneDeprecated(Team $team, Activity $activity)
    {
        if ($activity->team_id !== $team->id || !$activity->isDeprecatedByConversion()) {
            return back()->with('warning', __('activities.cannot_clone'));
        }

        if (auth()->user()->cannot('create', [Activity::class, $team])) {
            abort(403, 'No tienes permiso para crear actividades en este equipo.');
        }

        $newUuid = \Illuminate\Support\Str::uuid()->toString();
        $metadata = $activity->metadata ?? [];
        unset($metadata['converted_to_uuid'], $metadata['converted_to_id'], $metadata['is_deprecated']);
        $metadata['cloned_from_uuid'] = $activity->uuid;

        $cloned = Activity::create([
            'uuid' => $newUuid,
            'team_id' => $activity->team_id,
            'created_by_id' => auth()->id(),
            'parent_id' => $activity->parent_id,
            'expediente_id' => $activity->expediente_id,
            'type' => $activity->type,
            'title' => $activity->title . ' (Clon)',
            'description' => $activity->description,
            'status' => ['value' => 'pending'],
            'metadata' => $metadata,
            'visibility' => $activity->visibility,
            'due_date' => $activity->due_date,
            'scheduled_date' => $activity->scheduled_date,
            'priority' => $activity->priority,
            'is_archived' => false,
            'is_template' => $activity->is_template,
        ]);

        foreach ($activity->assignments as $assignment) {
            $cloned->assignments()->create([
                'user_id' => $assignment->user_id,
                'group_id' => $assignment->group_id,
                'assigned_by_id' => auth()->id(),
                'assigned_at' => now(),
            ]);
        }

        foreach ($activity->tags as $tag) {
            $cloned->tags()->create(['tag_id' => $tag->tag_id]);
        }

        $cloned->histories()->create([
            'user_id' => auth()->id(),
            'action' => 'cloned_from_deprecated',
            'details' => json_encode(['from_activity_id' => $activity->id, 'from_uuid' => $activity->uuid])
        ]);

        return redirect()->route('teams.activities.show', [$team, $cloned])
            ->with('success', __('activities.cloned_success'));
    }

    /**
     * Fusiona (Merge) notas y archivos de una actividad deprecada hacia otra actividad activa.
     */
    public function mergeDeprecated(Request $request, Team $team, Activity $activity)
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

        // Fusionar notas
        foreach ($activity->notes as $note) {
            $target->notes()->create([
                'user_id' => $note->user_id,
                'content' => $note->content . "\n[Heredado por fusión de la actividad #{$activity->id}]",
                'visibility' => $note->visibility ?? 'public',
            ]);
        }

        // Fusionar adjuntos
        foreach ($activity->attachments as $attachment) {
            $target->attachments()->create([
                'user_id' => $attachment->user_id,
                'name' => $attachment->name,
                'file_path' => $attachment->file_path,
                'file_type' => $attachment->file_type,
                'file_size' => $attachment->file_size,
            ]);
        }

        $target->histories()->create([
            'user_id' => auth()->id(),
            'action' => 'merged_from_deprecated',
            'details' => json_encode(['from_activity_id' => $activity->id, 'from_uuid' => $activity->uuid])
        ]);

        return redirect()->route('teams.activities.show', [$team, $target])
            ->with('success', __('activities.merged_success'));
    }

    /**
     * Añade un nuevo capítulo a una actividad de tipo documento.
     */
    public function addChapter(Request $request, Team $team, Activity $activity)
    {
        if ($activity->team_id !== $team->id || $activity->type !== 'document') {
            return back()->with('warning', __('activities.chapters_only_documents'));
        }

        $request->validate([
            'chapter_title' => 'required|string|max:255',
            'chapter_content' => 'required|string',
        ]);

        $metadata = $activity->metadata ?? [];
        $chapters = $metadata['chapters'] ?? [];

        $newChapter = [
            'id' => uniqid('chap_'),
            'title' => $request->get('chapter_title'),
            'content' => $request->get('chapter_content'),
            'author_id' => auth()->id(),
            'author_name' => auth()->user()->name,
            'created_at' => now()->format('Y-m-d H:i:s'),
            'updated_at' => now()->format('Y-m-d H:i:s'),
        ];

        $chapters[] = $newChapter;
        $metadata['chapters'] = $chapters;

        // Auto-incrementar versión si existe
        if (isset($metadata['version'])) {
            $parts = explode('.', $metadata['version']);
            if (count($parts) === 3) {
                $parts[1] = ((int)$parts[1]) + 1;
                $metadata['version'] = implode('.', $parts);
            }
        } else {
            $metadata['version'] = '1.1.0';
        }

        $activity->metadata = $metadata;
        $activity->save();

        $activity->histories()->create([
            'user_id' => auth()->id(),
            'action' => \Illuminate\Support\Str::limit("Añadido capítulo: {$newChapter['title']}", 100),
        ]);

        return back()->withFragment('chapters-section')->with('success', __('activities.chapter_added'));
    }

    /**
     * Actualiza un capítulo existente en un documento.
     */
    public function updateChapter(Request $request, Team $team, Activity $activity, $chapterId)
    {
        if ($activity->team_id !== $team->id || $activity->type !== 'document') {
            return back()->with('warning', __('activities.invalid_operation'));
        }

        $request->validate([
            'chapter_title' => 'required|string|max:255',
            'chapter_content' => 'required|string',
        ]);

        $metadata = $activity->metadata ?? [];
        $chapters = $metadata['chapters'] ?? [];

        $found = false;
        foreach ($chapters as &$chapter) {
            if ($chapter['id'] === $chapterId) {
                $chapter['title'] = $request->get('chapter_title');
                $chapter['content'] = $request->get('chapter_content');
                $chapter['updated_at'] = now()->format('Y-m-d H:i:s');
                $chapter['updated_by_name'] = auth()->user()->name;
                $found = true;
                break;
            }
        }

        if (!$found) {
            return back()->with('error', __('activities.chapter_not_found'));
        }

        $metadata['chapters'] = $chapters;
        $activity->metadata = $metadata;
        $activity->save();

        $activity->histories()->create([
            'user_id' => auth()->id(),
            'action' => "Actualizado capítulo ID #{$chapterId}",
        ]);

        return back()->withFragment('chapters-section')->with('success', __('activities.chapter_updated'));
    }

    /**
     * Elimina un capítulo de un documento.
     */
    public function deleteChapter(Request $request, Team $team, Activity $activity, $chapterId)
    {
        if ($activity->team_id !== $team->id || $activity->type !== 'document') {
            return back()->with('warning', __('activities.invalid_operation'));
        }

        $metadata = $activity->metadata ?? [];
        $chapters = $metadata['chapters'] ?? [];

        $filtered = array_filter($chapters, function($chapter) use ($chapterId) {
            return $chapter['id'] !== $chapterId;
        });

        if (count($filtered) === count($chapters)) {
            return back()->with('error', __('activities.chapter_not_found'));
        }

        $metadata['chapters'] = array_values($filtered);
        $activity->metadata = $metadata;
        $activity->save();

        $activity->histories()->create([
            'user_id' => auth()->id(),
            'action' => "Eliminado capítulo ID #{$chapterId}",
        ]);

        return back()->withFragment('chapters-section')->with('success', __('activities.chapter_deleted'));
    }
}

