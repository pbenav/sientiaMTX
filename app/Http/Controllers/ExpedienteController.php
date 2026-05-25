<?php

namespace App\Http\Controllers;

use App\Models\Expediente;
use App\Models\Team;
use App\Models\Task;
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
            ->withCount('rootTasks as tasks_count');

        $user = auth()->user();

        // Privacy Filter logic
        if (!$user->is_admin && !$team->isOwner($user) && !$team->isCoordinator($user)) {
            $query->where(function ($q) use ($user) {
                $q->where('visibility', 'public')
                  ->orWhere('created_by_id', $user->id)
                  ->orWhereHas('tasks', function ($sub) use ($user) {
                      $sub->where('assigned_user_id', $user->id)
                          ->orWhereHas('assignedTo', fn($q) => $q->where('users.id', $user->id))
                          ->orWhereHas('assignedGroups', fn($q) => $q->whereHas('users', fn($u) => $u->where('users.id', $user->id)));
                  });
            });
        }

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
            abort(403);
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
            abort(403);
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

        // AUTO-PUBLIC LOGIC: If private but assigned to others, make it public.
        $autoPublic = false;
        if (($validated['visibility'] ?? 'private') === 'private') {
            $hasOtherAssignee = false;
            if ($request->filled('assigned_user_id') && (int)$request->assigned_user_id !== auth()->id()) {
                $hasOtherAssignee = true;
            }
            if ($request->filled('assigned_to') && collect($request->assigned_to)->reject(fn($id) => (int)$id === auth()->id())->isNotEmpty()) {
                $hasOtherAssignee = true;
            }
            if ($request->filled('assigned_groups') && count($request->assigned_groups) > 0) {
                $hasOtherAssignee = true;
            }
            if ($hasOtherAssignee) {
                $validated['visibility'] = 'public';
                $autoPublic = true;
            }
        }

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
            abort(403);
        }

        // Privacy Check for detailed view
        $user = auth()->user();
        if ($expediente->visibility === 'private' && !$user->is_admin && !$team->isOwner($user) && !$team->isCoordinator($user)) {
            // Check if user created it or is assigned to internal tasks
            $isCreator = $expediente->created_by_id === $user->id;
            $isAssigned = $expediente->tasks()->where(function ($q) use ($user) {
                $q->where('assigned_user_id', $user->id)
                  ->orWhereHas('assignedTo', fn($sub) => $sub->where('users.id', $user->id))
                  ->orWhereHas('assignedGroups', fn($sub) => $sub->whereHas('users', fn($u) => $u->where('users.id', $user->id)));
            })->exists();
            
            if (!$isCreator && !$isAssigned) {
                abort(403, 'Este expediente es privado y solo es accesible para sus responsables y asignados.');
            }
        }

        $expediente->load(['creator', 'rootTasks.assignedUser', 'rootTasks.creator', 'rootTasks.children.assignedUser', 'attachments.user', 'relatedExpedientes']);

        // Get all tasks from the team that aren't currently attached to this expediente.
        $availableTasks = $team->tasks()
            ->where(function ($query) use ($expediente) {
                $query->whereNull('expediente_id')
                      ->orWhere('expediente_id', '!=', $expediente->id);
            })
            ->where('parent_id', null) // avoid subtasks or similar if needed? Let's just load all standard tasks
            ->with('expediente') // so we can show which dossier it already has, if any
            ->orderBy('created_at', 'desc')
            ->get();

        $allTeamExpedientes = $team->expedientes()
            ->where('id', '!=', $expediente->id)
            ->orderBy('code', 'desc')
            ->get();

        return view('expedientes.show', compact('team', 'expediente', 'availableTasks', 'allTeamExpedientes'));
    }

    /**
     * Update related dossiers links directly from the show view.
     */
    public function linkRelated(Request $request, Team $team, Expediente $expediente)
    {
        if (auth()->user()->cannot('view', $team) || $expediente->team_id !== $team->id) {
            abort(403);
        }

        $validated = $request->validate([
            'related_ids' => 'nullable|array',
            'related_ids.*' => 'exists:expedientes,id',
        ]);

        $this->syncBidirectionalRelations($expediente, $validated['related_ids'] ?? []);

        return redirect()->back()->with('success', 'Los expedientes relacionados han sido actualizados correctamente.');
    }

    /**
     * Link existing tasks to the expediente.
     */
    public function linkTasks(Request $request, Team $team, Expediente $expediente)
    {
        if (auth()->user()->cannot('view', $team) || $expediente->team_id !== $team->id) {
            abort(403);
        }

        $validated = $request->validate([
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:tasks,id',
        ]);

        // Filter only tasks belonging to THIS team to prevent security bypasses
        $updatedCount = $team->tasks()
            ->whereIn('id', $validated['task_ids'])
            ->update(['expediente_id' => $expediente->id]);

        return redirect()->back()->with('success', "Se han vinculado $updatedCount tareas correctamente.");
    }

    /**
     * Unlink a specific task from the expediente.
     */
    public function unlinkTask(Team $team, Expediente $expediente, Task $task)
    {
        if (auth()->user()->cannot('view', $team) || $expediente->team_id !== $team->id || $task->team_id !== $team->id) {
            abort(403);
        }

        // Safety check: Instance restriction
        if ($task->parent_id && !$task->is_template) {
            return redirect()->back()->with('error', "Las instancias individuales heredan el expediente del Plan Maestro. Modifica el plan original para desvincularlas.");
        }

        $task->update(['expediente_id' => null]);

        // If it's a template, cascade the dissociation to all instances
        if ($task->is_template) {
            $task->instances()->update(['expediente_id' => null]);
        }

        return redirect()->back()->with('success', "La tarea '{$task->title}' ha sido desvinculada correctamente.");
    }

    /**
     * Show the form for editing the specified expediente.
     */
    public function edit(Team $team, Expediente $expediente)
    {
        if (auth()->user()->cannot('view', $team) || $expediente->team_id !== $team->id) {
            abort(403);
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
            abort(403);
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

        $autoPublic = false;
        $visibility = $validated['visibility'] ?? $expediente->visibility;
        if ($visibility === 'private') {
            $hasOtherAssignee = false;
            $targetAssignee = $request->has('assigned_user_id') ? $request->assigned_user_id : $expediente->assigned_user_id;
            if ($targetAssignee && (int)$targetAssignee !== auth()->id()) {
                $hasOtherAssignee = true;
            }
            if ($request->filled('assigned_to') && collect($request->assigned_to)->reject(fn($id) => (int)$id === auth()->id())->isNotEmpty()) {
                $hasOtherAssignee = true;
            }
            if ($request->filled('assigned_groups') && count($request->assigned_groups) > 0) {
                $hasOtherAssignee = true;
            }

            if ($hasOtherAssignee) {
                $visibility = 'public';
                $autoPublic = true;
            }
            $validated['visibility'] = $visibility;
        }

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
    public function destroy(Team $team, Expediente $expediente)
    {
        if (auth()->user()->cannot('view', $team) || $expediente->team_id !== $team->id) {
            abort(403);
        }

        $expediente->delete();

        return redirect()->route('teams.expedientes.index', $team)
            ->with('success', 'Expediente movido a la papelera.');
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
            abort(404);
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
