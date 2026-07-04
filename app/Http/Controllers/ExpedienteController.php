<?php

namespace App\Http\Controllers;

use App\Models\Expediente;
use App\Models\Team;
use App\Models\Task;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\AttachmentLog;

class ExpedienteController extends Controller
{
    /**
     * Display a listing of the expedientes for a team.
     */
    public function index(Request $request, Team $team)
    {
        if (auth()->user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }

        $query = $team->expedientes()
            ->with(['creator'])
            ->withCount('rootActivities as activities_count');

        $user = auth()->user();

        // Privacy Filter logic - Strict Privacy: Applied to ALL users regardless of role (Admin, Owner, Coordinator)
        $query->where(function ($q) use ($user) {
            $q->where('visibility', 'public')
              ->orWhere('created_by_id', $user->id)
              ->orWhere('assigned_user_id', $user->id)
              ->orWhereHas('assignedTo', fn($q) => $q->where('users.id', $user->id))
              ->orWhereHas('assignedGroups', fn($q) => $q->whereHas('users', fn($u) => $u->where('users.id', $user->id)))
              ->orWhereHas('activities', function ($sub) use ($user) {
                  $sub->whereHas('assignedTo', fn($q) => $q->where('users.id', $user->id))
                      ->orWhereHas('assignedGroups', fn($q) => $q->whereHas('users', fn($u) => $u->where('users.id', $user->id)));
              });
        });

        // Simple search if provided
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $expedientes = $query->latest()->paginate(15);

        return view('expedientes.index', compact('team', 'expedientes'));
    }

    /**
     * Show the form for creating a new expediente.
     */
    public function create(Team $team)
    {
        if (auth()->user()->cannot('view', $team)) {
            return redirect()->route('teams.dashboard', $team)->with('warning', __('teams.unauthorized_access'));
        }

        $allExpedientes = $team->expedientes()->orderBy('title')->get();
        $users = $team->members;
        $groups = $team->groups;
        
        return view('expedientes.create', compact('team', 'allExpedientes', 'users', 'groups'));
    }

    /**
     * Store a newly created expediente.
     */
    public function store(Request $request, Team $team)
    {
        if (auth()->user()->cannot('view', $team)) {
            return redirect()->route('teams.dashboard', $team)->with('warning', __('teams.unauthorized_access'));
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,critical',
            'visibility' => 'required|in:public,private',
            'status' => 'required|in:open,active,on_hold,closed,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'related_ids' => 'nullable|array',
            'related_ids.*' => 'exists:expedientes,id',
            'assigned_user_id' => 'nullable|exists:users,id',
            'assigned_to' => 'nullable|array',
            'assigned_to.*' => 'exists:users,id',
            'assigned_groups' => 'nullable|array',
            'assigned_groups.*' => 'exists:groups,id',
        ]);

        // No auto-public logic to allow private dossiers with assignments.

        $expediente = $team->expedientes()->create([
            'created_by_id' => Auth::id(),
            'code' => Expediente::generateUniqueCode(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'priority' => $validated['priority'],
            'visibility' => $validated['visibility'],
            'status' => $validated['status'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'assigned_user_id' => $validated['assigned_user_id'] ?? null,
        ]);

        // Create Role-Based Assignments
        if (!empty($validated['assigned_to'])) {
            foreach ($validated['assigned_to'] as $userId) {
                $expediente->assignments()->create([
                    'user_id' => $userId,
                    'assigned_by_id' => auth()->id(),
                ]);
            }
        }
        
        if (!empty($validated['assigned_groups'])) {
            foreach ($validated['assigned_groups'] as $groupId) {
                $expediente->assignments()->create([
                    'group_id' => $groupId,
                    'assigned_by_id' => auth()->id(),
                ]);
            }
        }

        if (!empty($validated['related_ids'])) {
            $this->syncBidirectionalRelations($expediente, $validated['related_ids']);
        }

        return redirect()->route('teams.expedientes.show', [$team, $expediente])
            ->with('success', 'Expediente creado correctamente.');
    }

    /**
     * Display the specified expediente.
     */
    public function show(Team $team, Expediente $expediente)
    {
        // Ensure the expediente belongs to the team and user can view team
        if (auth()->user()->cannot('view', $team) || $expediente->team_id !== $team->id) {
            return redirect()->route('teams.dashboard', $team)->with('warning', __('teams.unauthorized_access'));
        }

        // Privacy Check for detailed view - Strict Privacy applied to ALL users
        $user = auth()->user();
        if ($expediente->visibility === 'private') {
            // Check if user created it or is assigned directly to it or to its tasks
            $isCreator = $expediente->created_by_id === $user->id;
            
            $isDirectlyAssigned = $expediente->assigned_user_id === $user->id 
                || $expediente->assignedTo()->where('users.id', $user->id)->exists()
                || $expediente->assignedGroups()->whereHas('users', fn($u) => $u->where('users.id', $user->id))->exists();

            $isActivityAssigned = $expediente->activities()->where(function ($q) use ($user) {
                $q->whereHas('assignedTo', fn($sub) => $sub->where('users.id', $user->id))
                  ->orWhereHas('assignedGroups', fn($sub) => $sub->whereHas('users', fn($u) => $u->where('users.id', $user->id)));
            })->exists();
            
            if (!$isCreator && !$isDirectlyAssigned && !$isActivityAssigned) {
                return redirect()->route('teams.expedientes.index', $team)
                    ->with('warning', 'Este expediente es privado y solo es accesible de forma estricta para sus responsables y miembros asignados.');
            }
        }

        $expediente->load(['creator', 'rootActivities.assignedTo', 'rootActivities.creator', 'rootActivities.children.assignedTo', 'attachments.user', 'relatedExpedientes']);

        // Get all root activities from the team that aren't currently attached to this expediente.
        $availableActivities = $team->activities()
            ->where(function ($query) use ($expediente) {
                $query->whereNull('expediente_id')
                      ->orWhere('expediente_id', '!=', $expediente->id);
            })
            ->whereNull('parent_id') // avoid subactivities
            ->with('expediente') // so we can show which dossier it already has, if any
            ->orderBy('created_at', 'desc')
            ->get();

        // Get expedientes that can be linked (excluding the current one and already linked ones)
        $linkedExpedienteIds = $expediente->relatedExpedientes->pluck('id')->toArray();
        $availableRelatedExpedientes = $team->expedientes()
            ->where('id', '!=', $expediente->id)
            ->whereNotIn('id', $linkedExpedienteIds)
            ->orderBy('code', 'desc')
            ->get();

        $members = $team->members()->select('users.id', 'users.name', 'users.email')->orderBy('users.name')->get();

        return view('expedientes.show', compact('team', 'expediente', 'availableActivities', 'availableRelatedExpedientes', 'members'));
    }

    /**
     * Update related dossiers links directly from the show view.
     */
    public function linkRelated(Request $request, Team $team, Expediente $expediente)
    {
        if (auth()->user()->cannot('view', $team) || $expediente->team_id !== $team->id) {
            return redirect()->route('teams.dashboard', $team)->with('warning', __('teams.unauthorized_access'));
        }

        $validated = $request->validate([
            'related_ids' => 'required|array',
            'related_ids.*' => 'exists:expedientes,id',
        ]);

        $currentIds = $expediente->relatedExpedientes()->pluck('expedientes.id')->toArray();
        $newIds = array_unique(array_merge($currentIds, $validated['related_ids']));

        $this->syncBidirectionalRelations($expediente, $newIds);

        return redirect()->back()->with('success', 'Los expedientes relacionados han sido vinculados correctamente.');
    }

    /**
     * Unlink a specific related expediente.
     */
    public function unlinkRelated(Team $team, Expediente $expediente, $related_id)
    {
        if (auth()->user()->cannot('view', $team) || $expediente->team_id !== $team->id) {
            return redirect()->route('teams.dashboard', $team)->with('warning', __('teams.unauthorized_access'));
        }

        $currentIds = $expediente->relatedExpedientes()->pluck('expedientes.id')->toArray();
        $newIds = array_diff($currentIds, [$related_id]);

        $this->syncBidirectionalRelations($expediente, $newIds);

        return redirect()->back()->with('success', 'Expediente desvinculado correctamente.');
    }

    /**
     * Link existing tasks to the expediente.
     */
    public function linkTasks(Request $request, Team $team, Expediente $expediente)
    {
        if (auth()->user()->cannot('view', $team) || $expediente->team_id !== $team->id) {
            return redirect()->route('teams.dashboard', $team)->with('warning', __('teams.unauthorized_access'));
        }

        $validated = $request->validate([
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:activities,id',
        ]);

        // Filter only activities belonging to THIS team to prevent security bypasses
        $updatedCount = $team->activities()
            ->whereIn('id', $validated['task_ids'])
            ->update(['expediente_id' => $expediente->id]);

        return redirect()->back()->with('success', "Se han vinculado $updatedCount actividades correctamente.");
    }

    /**
     * Create a new activity directly from an expediente (quick create).
     */
    public function storeFromExpediente(Request $request, Team $team, Expediente $expediente)
    {
        if (auth()->user()->cannot('view', $team) || $expediente->team_id !== $team->id) {
            return redirect()->route('teams.dashboard', $team)->with('warning', __('teams.unauthorized_access'));
        }

        $validated = $request->validate([
            'type' => 'required|string|in:task,document,note,link,decision,meeting,reminder',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,critical',
            'visibility' => 'required|in:public,private,semi-private',
            'due_date' => 'nullable|date',
            'assigned_to' => 'nullable|array',
            'assigned_to.*' => 'integer|exists:users,id',
        ]);

        $activity = $team->activities()->create([
            'type' => $validated['type'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'priority' => $validated['priority'],
            'visibility' => $validated['visibility'],
            'status' => json_encode(['value' => 'pending']),
            'expediente_id' => $expediente->id,
            'created_by_id' => auth()->id(),
            'due_date' => $validated['due_date'] ?? null,
        ]);

        // Assign users if provided
        if (!empty($validated['assigned_to'])) {
            foreach ($validated['assigned_to'] as $userId) {
                $activity->assignments()->create([
                    'user_id' => $userId,
                    'assigned_by_id' => auth()->id(),
                ]);
            }
        }

        return redirect()->route('teams.expedientes.show', [$team, $expediente])
            ->with('success', "Actividad '{$activity->title}' creada y vinculada al expediente correctamente.");
    }

    /**
     * Unlink a specific activity from the expediente.
     */
    public function unlinkTask(Team $team, Expediente $expediente, Activity $task)
    {
        if (auth()->user()->cannot('view', $team) || $expediente->team_id !== $team->id || $task->team_id !== $team->id) {
            return redirect()->route('teams.dashboard', $team)->with('warning', __('teams.unauthorized_access'));
        }

        // Safety check: Instance restriction
        if ($task->parent_id && !$task->is_template) {
            return redirect()->back()->with('error', "Las instancias individuales heredan el expediente del Plan Maestro. Modifica el plan original para desvincularlas.");
        }

        $task->update(['expediente_id' => null]);

        // If it's a template, cascade the dissociation to all instances
        if ($task->is_template) {
            $task->children()->update(['expediente_id' => null]);
        }

        return redirect()->back()->with('success', "La actividad '{$task->title}' ha sido desvinculada correctamente.");
    }

    /**
     * Show the form for editing the specified expediente.
     */
    public function edit(Team $team, Expediente $expediente)
    {
        if (auth()->user()->cannot('view', $team) || $expediente->team_id !== $team->id) {
            return redirect()->route('teams.dashboard', $team)->with('warning', __('teams.unauthorized_access'));
        }

        $allExpedientes = $team->expedientes()
            ->where('id', '!=', $expediente->id)
            ->orderBy('title')
            ->get();
            
        $users = $team->members;
        $groups = $team->groups;

        return view('expedientes.edit', compact('team', 'expediente', 'allExpedientes', 'users', 'groups'));
    }

    /**
     * Update the specified expediente.
     */
    public function update(Request $request, Team $team, Expediente $expediente)
    {
        if (auth()->user()->cannot('view', $team) || $expediente->team_id !== $team->id) {
            return redirect()->route('teams.dashboard', $team)->with('warning', __('teams.unauthorized_access'));
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,critical',
            'visibility' => 'required|in:public,private',
            'status' => 'required|in:open,active,on_hold,closed,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'related_ids' => 'nullable|array',
            'related_ids.*' => 'exists:expedientes,id',
            'assigned_user_id' => 'nullable|exists:users,id',
            'assigned_to' => 'nullable|array',
            'assigned_to.*' => 'exists:users,id',
            'assigned_groups' => 'nullable|array',
            'assigned_groups.*' => 'exists:groups,id',
        ]);

        // No auto-public logic to allow private dossiers with assignments.

        $expediente->update($validated);

        // Update assignments
        $expediente->assignments()->delete();
        
        if (!empty($validated['assigned_to'])) {
            foreach ($validated['assigned_to'] as $userId) {
                $expediente->assignments()->create([
                    'user_id' => $userId,
                    'assigned_by_id' => auth()->id(),
                ]);
            }
        }
        
        if (!empty($validated['assigned_groups'])) {
            foreach ($validated['assigned_groups'] as $groupId) {
                $expediente->assignments()->create([
                    'group_id' => $groupId,
                    'assigned_by_id' => auth()->id(),
                ]);
            }
        }
        
        $this->syncBidirectionalRelations($expediente, $validated['related_ids'] ?? []);

        return redirect()->route('teams.expedientes.show', [$team, $expediente])
            ->with('success', 'Expediente actualizado correctamente.');
    }

    /**
     * Remove the specified expediente.
     */
    public function destroy(\Illuminate\Http\Request $request, Team $team, Expediente $expediente)
    {
        if (auth()->user()->cannot('view', $team) || $expediente->team_id !== $team->id) {
            return redirect()->route('teams.dashboard', $team)->with('warning', __('teams.unauthorized_access'));
        }
        
        if (auth()->user()->cannot('delete', $expediente)) {
            abort(403, 'No tienes permiso para eliminar este expediente.');
        }

        if ($request->input('cascade_delete') === '1') {
            // Delete activities physically
            $expediente->activities()->each(function ($activity) {
                // Limpiar adjuntos físicos de la actividad si los hay
                $activity->attachments()->each(function ($attachment) {
                    if ($attachment->file_path && \Illuminate\Support\Facades\Storage::disk($attachment->disk ?? 'public')->exists($attachment->file_path)) {
                        \Illuminate\Support\Facades\Storage::disk($attachment->disk ?? 'public')->delete($attachment->file_path);
                    }
                    $attachment->delete();
                });
                $activity->forceDelete();
            });

            // For backwards compatibility, also delete legacy tasks if any
            $expediente->tasks()->each(function ($task) {
                $task->forceDelete();
            });

            // Delete attachments physically
            $expediente->attachments()->each(function ($attachment) {
                if ($attachment->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($attachment->file_path)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->file_path);
                }
                $attachment->delete();
            });

            // Delete notes physically
            $expediente->notes()->forceDelete();

            // Delete associations
            $expediente->assignments()->delete();
            \Illuminate\Support\Facades\DB::table('expediente_related')->where('expediente_id', $expediente->id)->orWhere('related_id', $expediente->id)->delete();

            // Physically delete expediente
            $expediente->forceDelete();

            return redirect()->route('teams.expedientes.index', $team)
                ->with('success', 'Expediente y todo su contenido asociado eliminados de forma permanente.');
        }

        // Modo 1: Eliminar el expediente y DESVINCULAR las actividades
        // Simplemente dejan de estar vinculadas a ese expediente
        $expediente->activities()->update(['expediente_id' => null]);
        $expediente->tasks()->update(['expediente_id' => null]);

        $expediente->delete();

        return redirect()->route('teams.expedientes.index', $team)
            ->with('success', 'Expediente movido a la papelera. Las actividades se han conservado desvinculadas.');
    }

    /**
     * Upload an attachment to the expediente.
     */
    public function uploadAttachment(\Illuminate\Http\Request $request, Team $team, Expediente $expediente)
    {
        if (auth()->user()->cannot('view', $team)) {
            return back()->with('error', __('teams.unauthorized_access'));
        }

        if ($expediente->team_id !== $team->id) {
            return redirect()->route('teams.dashboard', $team)->with('warning', __('teams.unauthorized_access'));
        }

        $maxSizeKB = (int)ini_get('upload_max_filesize') * 1024;
        $request->validate([
            'file' => "required|file|max:$maxSizeKB",
        ]);

        $user = auth()->user();
        $file = $request->file('file');
        $size = $file->getSize();

        // Check user quota
        if (!$user->hasAvailableQuota($size)) {
            return back()->with('error', 'Has excedido tu cuota de espacio en disco.');
        }

        // Check TEAM quota
        if (!$team->hasAvailableQuota($size)) {
            return back()->with('error', '⚠️ El equipo ha alcanzado su límite de almacenamiento. Un coordinador debe liberar espacio antes de poder subir más archivos.');
        }

        $path = $file->store("attachments/expediente_{$expediente->id}", 'public');

        $originalName = $file->getClientOriginalName();
        $datePrefix = date('Y-m-d-');
        $fileName = str_starts_with($originalName, $datePrefix) ? $originalName : $datePrefix . $originalName;

        $attachment = $expediente->attachments()->create([
            'user_id' => $user->id,
            'file_name' => $fileName,
            'file_path' => $path,
            'file_size' => $size,
            'mime_type' => $file->getMimeType(),
        ]);

        AttachmentLog::create([
            'attachment_id' => $attachment->id,
            'user_id' => $user->id,
            'action' => 'upload',
            'metadata' => [
                'original_name' => $originalName,
                'size' => $size
            ],
            'ip_address' => request()->ip()
        ]);

        // Update user disk usage
        $user->increment('disk_used', $size);

        return back()->with('success', 'Archivo adjuntado correctamente.');
    }

    /**
     * Syncs related dossiers bidirectionally to ensure reflection on both sides.
     */
    protected function syncBidirectionalRelations(Expediente $expediente, array $relatedIds)
    {
        // 1. Sync directly (Forward link)
        $expediente->relatedExpedientes()->sync($relatedIds);

        // 2. Handle backward links reflection (Mirror)
        // First, delete any existing mirrors that pointed to THIS dossier
        \Illuminate\Support\Facades\DB::table('expediente_related')->where('related_id', $expediente->id)->delete();

        // Re-create current mirrors
        foreach ($relatedIds as $rid) {
            if ($rid != $expediente->id) {
                \Illuminate\Support\Facades\DB::table('expediente_related')->updateOrInsert([
                    'expediente_id' => $rid,
                    'related_id' => $expediente->id
                ]);
            }
        }
    }
}
