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
        $dir  = $request->get('dir', 'asc');

        $perPage = (int) ($filters['per_page'] ?? 15);
        if (!in_array($perPage, [10, 15, 20, 30, 50, 100])) {
            $perPage = 15;
        }

        $activities = $this->activityService->paginate($team, $filters, $perPage);

        $members = $team->members()->orderBy('name')->get();
        $skills = \App\Models\Skill::forTeamOrGlobal($team->id)->orderBy('name')->get();
        $expedientes = $team->expedientes()->orderBy('title')->get();

        return view('teams.activities.index', compact('team', 'activities', 'filters', 'sort', 'dir', 'members', 'skills', 'expedientes'));
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

        // Cargar datos necesarios para el formulario
        $members = $team->members()->orderBy('name')->get();
        $groups  = $team->groups()->orderBy('name')->get();
        $expedientes = $team->expedientes()->orderBy('title')->get();
        
        // Actividades padre disponibles para jerarquía (no circulares)
        $parentActivities = Activity::byTeam($team->id)->active()->where('is_template', false)->orderBy('title')->get();

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
            ->with('success', 'Actividad creada con éxito.');
    }

    /**
     * Muestra el detalle de una actividad.
     */
    public function show(Team $team, Activity $activity)
    {
        if ($activity->team_id !== $team->id) {
            return redirect()->route('teams.dashboard', $team)->with('warning', 'Actividad no encontrada en este equipo.');
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
            return redirect()->route('teams.dashboard', $team)->with('warning', 'Actividad no encontrada.');
        }

        if (auth()->user()->cannot('update', $activity)) {
            abort(403, 'No tienes permiso para modificar esta actividad.');
        }

        $activity = $activity->asSubtype();
        $members = $team->members()->orderBy('name')->get();
        $groups  = $team->groups()->orderBy('name')->get();
        $expedientes = $team->expedientes()->orderBy('title')->get();
        
        $parentActivities = Activity::byTeam($team->id)
            ->active()
            ->where('id', '!=', $activity->id)
            ->orderBy('title')
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
            return redirect()->route('teams.dashboard', $team)->with('warning', 'Actividad no encontrada.');
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
            ->with('success', 'Actividad actualizada con éxito.');
    }

    /**
     * Elimina (soft-delete) una actividad.
     */
    public function destroy(Team $team, Activity $activity)
    {
        if ($activity->team_id !== $team->id) {
            return redirect()->route('teams.dashboard', $team)->with('warning', 'Actividad no encontrada.');
        }

        if (auth()->user()->cannot('delete', $activity)) {
            abort(403, 'No tienes permiso para eliminar esta actividad.');
        }

        $this->activityService->delete($activity);

        return redirect()->route('teams.activities.index', $team)
            ->with('success', 'Actividad eliminada con éxito.');
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

        return back()->with('success', 'Actividad archivada.');
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

        return back()->with('success', 'Actividad desarchivada.');
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

        return back()->with('success', 'Estado de la actividad actualizado.');
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

        return redirect()->route('teams.activities.show', [$team, $activity])->withFragment('notes')->with('success', 'Nota añadida.');
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

        return redirect()->route('teams.activities.show', [$team, $activity])->withFragment('notes')->with('success', 'Nota actualizada.');
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

        return redirect()->route('teams.activities.show', [$team, $activity])->withFragment('notes')->with('success', 'Nota eliminada.');
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

        return back()->with('success', 'Archivos subidos correctamente.');
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

        return back()->with('success', 'Archivo adjunto eliminado.');
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

        if (!\Illuminate\Support\Facades\Storage::disk($attachment->disk)->exists($attachment->file_path)) {
            return back()->with('error', 'El archivo no se encuentra en el servidor.');
        }

        return \Illuminate\Support\Facades\Storage::disk($attachment->disk)->download($attachment->file_path, $attachment->file_name);
    }

    /**
     * Convierte una actividad a un nuevo tipo.
     */
    public function convert(Request $request, Team $team, Activity $activity, \App\Actions\Activities\ConvertActivityAction $action)
    {
        if ($activity->team_id !== $team->id) {
            return redirect()->route('teams.dashboard', $team)->with('warning', 'Actividad no encontrada.');
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
            return back()->with('warning', 'Esta actividad no puede ser restaurada.');
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
            ->with('success', 'Actividad restaurada correctamente a estado pendiente.');
    }

    /**
     * Clona una actividad deprecada para crear un registro independiente limpio.
     */
    public function cloneDeprecated(Team $team, Activity $activity)
    {
        if ($activity->team_id !== $team->id || !$activity->isDeprecatedByConversion()) {
            return back()->with('warning', 'Esta actividad no puede ser clonada.');
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
            ->with('success', 'Actividad clonada correctamente.');
    }

    /**
     * Fusiona (Merge) notas y archivos de una actividad deprecada hacia otra actividad activa.
     */
    public function mergeDeprecated(Request $request, Team $team, Activity $activity)
    {
        if ($activity->team_id !== $team->id || !$activity->isDeprecatedByConversion()) {
            return back()->with('warning', 'Esta actividad no es válida para fusión.');
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
            ->with('success', 'Contenido fusionado correctamente en la actividad destino.');
    }

    /**
     * Añade un nuevo capítulo a una actividad de tipo documento.
     */
    public function addChapter(Request $request, Team $team, Activity $activity)
    {
        if ($activity->team_id !== $team->id || $activity->type !== 'document') {
            return back()->with('warning', 'Solo las actividades de tipo Documento admiten capítulos.');
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

        return back()->withFragment('chapters-section')->with('success', 'Capítulo añadido correctamente al documento.');
    }

    /**
     * Actualiza un capítulo existente en un documento.
     */
    public function updateChapter(Request $request, Team $team, Activity $activity, $chapterId)
    {
        if ($activity->team_id !== $team->id || $activity->type !== 'document') {
            return back()->with('warning', 'Operación no válida.');
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
            return back()->with('error', 'Capítulo no encontrado.');
        }

        $metadata['chapters'] = $chapters;
        $activity->metadata = $metadata;
        $activity->save();

        $activity->histories()->create([
            'user_id' => auth()->id(),
            'action' => "Actualizado capítulo ID #{$chapterId}",
        ]);

        return back()->withFragment('chapters-section')->with('success', 'Capítulo actualizado correctamente.');
    }

    /**
     * Elimina un capítulo de un documento.
     */
    public function deleteChapter(Request $request, Team $team, Activity $activity, $chapterId)
    {
        if ($activity->team_id !== $team->id || $activity->type !== 'document') {
            return back()->with('warning', 'Operación no válida.');
        }

        $metadata = $activity->metadata ?? [];
        $chapters = $metadata['chapters'] ?? [];

        $filtered = array_filter($chapters, function($chapter) use ($chapterId) {
            return $chapter['id'] !== $chapterId;
        });

        if (count($filtered) === count($chapters)) {
            return back()->with('error', 'Capítulo no encontrado.');
        }

        $metadata['chapters'] = array_values($filtered);
        $activity->metadata = $metadata;
        $activity->save();

        $activity->histories()->create([
            'user_id' => auth()->id(),
            'action' => "Eliminado capítulo ID #{$chapterId}",
        ]);

        return back()->withFragment('chapters-section')->with('success', 'Capítulo eliminado correctamente.');
    }
}

