<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamRole;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\InvitationNotification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;
use App\Traits\HandlesEisenhowerMatrix;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    use HandlesEisenhowerMatrix;
    /**
     * Display a listing of user's teams
     */
    public function index()
    {
        $teams = auth()->user()->teams()
            ->with(['members'])
            ->orderByPivot('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->paginate(15);

        return view('teams.index', compact('teams'));
    }

    /**
     * Display a listing of all teams for site administrators
     */
    public function indexAdmin(Request $request)
    {
        $this->authorize('admin'); // Ensure only global admins can access

        $query = Team::with(['creator'])->withCount(['members', 'tasks']);

        // Sorting
        $sort = $request->get('sort', 'name');
        $direction = $request->get('direction', 'asc');
        $query->orderBy($sort, $direction);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%");
            });
        }

        $perPage = $request->get('per_page', 25);
        if ($perPage === 'all') {
            $perPage = $query->count() ?: 1;
        }

        $teams = $query->paginate($perPage)->withQueryString();

        return view('settings.teams', [
            'teams' => $teams,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    /**
     * Show the form for creating a new team
     */
    public function create()
    {
        return view('teams.create');
    }

    /**
     * Store a newly created team in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:teams',
            'description' => 'nullable|string|max:1000',
        ]);

        $validated['slug'] = str($validated['name'])->slug();
        $validated['created_by_id'] = auth()->id();

        $team = Team::create($validated);

        // Add creator as coordinator
        $coordinatorRole = TeamRole::where('name', 'coordinator')->first();
        $team->members()->attach(auth()->id(), ['role_id' => $coordinatorRole->id]);

        return redirect()->route('teams.show', $team)
            ->with('success', __('teams.created'));
    }

    /**
     * Display the specified team
     */
    public function show(Team $team)
    {
        if (auth()->user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }

        $user = auth()->user();
        $isManager = $team->isManager($user);

        $team->load(['members', 'tasks' => function($query) use ($user, $isManager, $team) {
            $query->visibleTo($user, $isManager)
                  ->operationalFor($user, $team, true);
        }]);

        return view('teams.show', compact('team'));
    }

    /**
     * Show the form for editing the team
     */
    public function edit(Team $team)
    {
        $this->authorize('update', $team);
        
        $skills = $team->skills()->withCount('tasks')->orderBy('name')->get();
        $teams = collect([$team]); // Solo este equipo es relevante en este contexto

        return view('teams.edit', compact('team', 'skills', 'teams'));
    }

    /**
     * Update the team in storage
     */
    public function update(Request $request, Team $team)
    {
        $this->authorize('update', $team);

        $rules = [
            'name' => 'required|string|max:255|unique:teams,name,' . $team->id,
            'description' => 'nullable|string|max:1000',
            'telegram_chat_id' => 'nullable|string|max:255',
            'whatsapp_chat_id' => 'nullable|string|max:255',
            'settings' => 'nullable|array',
        ];

        if (auth()->user()->is_admin) {
            $rules['disk_quota_gb'] = 'required|numeric|min:0.1';
        }

        $validated = $request->validate($rules);

        if (isset($validated['telegram_chat_id'])) {
            $validated['telegram_chat_id'] = trim($validated['telegram_chat_id']);
        }
        if (isset($validated['whatsapp_chat_id'])) {
            $validated['whatsapp_chat_id'] = trim($validated['whatsapp_chat_id']);
        }

        if (auth()->user()->is_admin && $request->has('disk_quota_gb')) {
            $validated['disk_quota'] = (int)($request->disk_quota_gb * 1024 * 1024 * 1024);
        }

        // Proteger el estado Premium de WhatsApp para que solo un administrador global pueda modificarlo
        if (isset($validated['settings'])) {
            $validated['settings'] = array_merge($team->settings ?? [], $validated['settings']);
            
            if (!auth()->user()->is_admin) {
                $validated['settings']['has_whatsapp'] = $team->settings['has_whatsapp'] ?? false;
            } else {
                $validated['settings']['has_whatsapp'] = filter_var($validated['settings']['has_whatsapp'] ?? false, FILTER_VALIDATE_BOOLEAN);
            }
        }

        $validated['slug'] = str($validated['name'])->slug();

        $team->update($validated);

        // Si el usuario es administrador y no es miembro del equipo, volvemos a la gestión global
        if (auth()->user()->is_admin && !$team->members()->where('user_id', auth()->id())->exists()) {
            return redirect()->route('settings.teams')->with('success', __('teams.updated'));
        }

        return redirect()->route('teams.show', $team)
            ->with('success', __('teams.updated'));
    }

    /**
     * Remove the team from storage
     */
    public function destroy(Team $team)
    {
        $this->authorize('delete', $team);

        $team->forceDelete();

        $redirectRoute = auth()->user()->is_admin ? 'settings.teams' : 'teams.index';
        return redirect()->route($redirectRoute)
            ->with('success', __('teams.deleted'));
    }

    /**
     * Show team members and their roles
     */
    public function members(Request $request, Team $team)
    {
        if (auth()->user()->cannot('viewMembers', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }

        $query = $team->members()->with('sessions')
            ->withCount([
                'createdTasks' => fn($q) => $q->where('team_id', $team->id),
                'forumThreads' => fn($q) => $q->where('team_id', $team->id),
                'forumMessages' => fn($q) => $q->whereHas('thread', fn($sq) => $sq->where('team_id', $team->id)),
                'attachments' => fn($q) => $q->whereHasMorph('attachable', [\App\Models\Task::class], fn($sq) => $sq->where('team_id', $team->id)),
                'receivedKudos' => fn($q) => $q->where('team_id', $team->id),
                'assignedTasks' => fn($q) => $q->where('team_id', $team->id)->where('status', 'completed')
            ]);
        
        $query->with(['skills' => function($q) use ($team) {
            $q->where('team_id', $team->id)->orWhereNull('team_id');
        }]);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }

        // Role Filter
        if ($request->filled('role_id')) {
            $query->wherePivot('role_id', $request->role_id);
        }

        $members = $query->paginate(20, ['*'], 'members_page')->withQueryString();
        $allMembers = $team->members; // Complete list for group assignment dropdowns

        $groupsQuery = $team->groups()->with('users');
        if ($request->filled('search_group')) {
            $groupsQuery->where('name', 'like', '%' . $request->search_group . '%')
                        ->orWhere('description', 'like', '%' . $request->search_group . '%');
        }
        $groups = $groupsQuery->get();
        $roles = TeamRole::all();
        $invitations = $team->invitations()->with('role')->get();

        return view('teams.members', compact('team', 'members', 'allMembers', 'groups', 'roles', 'invitations'));
    }

    /**
     * Add a new member to the team
     */
    public function addMember(Request $request, Team $team)
    {
        $this->authorize('manageMembers', $team);

        $validated = $request->validate([
            'email' => 'required|email',
            'role_id' => 'required|exists:team_roles,id',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            // Check if already invited
            if ($team->invitations()->where('email', $validated['email'])->exists()) {
                return back()->withErrors(['email' => __('teams.already_invited')]);
            }

            // Create invitation
            $invitation = $team->invitations()->create([
                'email' => $validated['email'],
                'role_id' => $validated['role_id'],
                'token' => Str::random(40),
            ]);

            // Notify via email - Here we'd send to the email address directly
            Notification::route('mail', $validated['email'])
                ->notify(new InvitationNotification($invitation));

            return back()->with('success', __('teams.invitation_sent'));
        }

        if ($team->members()->where('user_id', $user->id)->exists()) {
            return back()->withErrors(['email' => 'El usuario ya es miembro del equipo']);
        }

        $team->members()->attach($user->id, ['role_id' => $validated['role_id']]);

        \App\Models\SecurityLog::log(
            'team.member_added',
            "Usuario {$user->email} añadido al equipo {$team->name} con rol_id {$validated['role_id']}"
        );

        return back()->with('success', __('teams.member_added'));
    }

    /**
     * Add multiple members to the team in bulk
     */
        public function bulkAddMembers(Request $request, Team $team)
    {
        $this->authorize('manageMembers', $team);

        // EXTRA SECURITY: Only global admins can use the bulk tool
        if (!auth()->user()->is_admin) {
            abort(403, 'Solo los administradores del sistema pueden realizar invitaciones masivas.');
        }

        $validated = $request->validate([
            'emails_block' => 'required|string',
            'role_id' => 'required|exists:team_roles,id',
        ]);

        // Extract emails using a robust regex
        preg_match_all('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}/i', $validated['emails_block'], $matches);
        $emails = array_unique(array_map('strtolower', $matches[0]));

        if (empty($emails)) {
            return back()->with('error', 'No se han encontrado direcciones de correo válidas en el texto proporcionado.');
        }

        $added = 0;
        $invited = 0;
        $alreadyMembers = 0;

        foreach ($emails as $email) {
            $user = User::where('email', $email)->first();

            if ($user) {
                // If user exists, check if already in team
                if (!$team->members()->where('user_id', $user->id)->exists()) {
                    $team->members()->attach($user->id, ['role_id' => $validated['role_id']]);
                    $added++;
                } else {
                    $alreadyMembers++;
                }
            } else {
                // If user doesn't exist, check if already invited
                if (!$team->invitations()->where('email', $email)->exists()) {
                    $invitation = $team->invitations()->create([
                        'email' => $email,
                        'role_id' => $validated['role_id'],
                        'token' => Str::random(40),
                    ]);

                    Notification::route('mail', $email)
                        ->notify(new InvitationNotification($invitation));
                    $invited++;
                }
            }
        }

        $message = "Procesado completado: ";
        if ($added > 0) $message .= "{$added} usuarios añadidos directamente. ";
        if ($invited > 0) $message .= "{$invited} invitaciones enviadas. ";
        if ($alreadyMembers > 0) $message .= "({$alreadyMembers} ya eran miembros).";
        if ($added === 0 && $invited === 0) $message = "No se han realizado cambios (los usuarios ya estaban en el equipo o invitados).";

        return back()->with('success', $message);
    }

    /**
     * Update member role
     */
    public function updateMemberRole(Request $request, Team $team, User $user)
    {
        $this->authorize('manageMembers', $team);

        $validated = $request->validate([
            'role_id' => 'required|exists:team_roles,id',
        ]);

        // PROTECT: Cannot change the role of the team owner/creator
        if ($user->id === $team->created_by_id) {
            return back()->with('error', __('teams.cannot_modify_owner'));
        }

        $team->members()->updateExistingPivot($user->id, ['role_id' => $validated['role_id']]);

        \App\Models\SecurityLog::log(
            'team.role_updated',
            "Rol del usuario {$user->email} actualizado en el equipo {$team->name} a rol_id {$validated['role_id']}"
        );

        return back()->with('success', __('teams.member_role_updated'));
    }

    /**
     * Update member's personal info (name/email)
     */
    public function updateMemberInfo(Request $request, Team $team, User $user)
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        $user->update($validated);

        return back()->with('success', __('teams.member_updated'));
    }

    /**
     * Remove a member from the team
     */
    public function removeMember(Team $team, User $user)
    {
        $this->authorize('manageMembers', $team);

        $team->members()->detach($user->id);

        \App\Models\SecurityLog::log(
            'team.member_removed',
            "Usuario {$user->email} eliminado del equipo {$team->name}"
        );

        return back()->with('success', __('teams.member_removed'));
    }

    /**
     * Remove a pending invitation
     */
    public function removeInvitation(Team $team, TeamInvitation $invitation)
    {
        $this->authorize('manageMembers', $team);

        $invitation->delete();

        return back()->with('success', __('teams.invitation_cancelled'));
    }

    /**
     * Show dashboard with matriz de Eisenhower
     */
    public function dashboard(Team $team)
    {
        if (auth()->user()->cannot('view', $team)) {
            return redirect()->back()->with('warning', __('teams.unauthorized_access'));
        }

        $user = auth()->user();
        $isManager = $team->isManager($user);

        $query = $team->tasks()
            ->with([
                'assignedTo', 'assignedGroups', 'tags', 'assignedUser', 'skills', 'parent', 'creator', 'service',
                'children' => function($q) use ($user, $isManager) {
                    $q->visibleTo($user, $isManager);
                },
                'children.assignedUser'
            ])
            ->visibleTo($user, $isManager)
            ->focusedFor($user, $team)
            ->when(request('skill_id'), function ($q, $skillId) {
                $q->where(function ($sq) use ($skillId) {
                    $sq->where('skill_id', $skillId)
                        ->orWhereHas('skills', fn($sk) => $sk->where('skills.id', $skillId));
                });
            });

        // Matrix-specific filter for managers (as requested: ensure owner visibility + backlog)
        // Note: Hierarchy (filtering children/instances) is now handled by scopeOperationalFor
        if ($isManager) {
            $query->where(function ($q) use ($user) {
                // Return tasks that have NO one specifically assigned yet (Backlog/Masters)
                // OR tasks explicitly created by the user (Ownership)
                // OR tasks assigned specifically to the user (Direct work)
                $q->where(function ($backlog) {
                    $backlog->whereNull('assigned_user_id')
                            ->whereDoesntHave('assignedTo');
                })
                ->orWhere('created_by_id', $user->id)
                ->orWhere('assigned_user_id', $user->id);
            });
        }

        $skills = \App\Models\Skill::forTeamOrGlobal($team->id)->get();


        $allTasks = $query->get();
        $tasks = $allTasks; // Stay compatible with view expecting $tasks

        // Group tasks by quadrant, excluding completed ones
        $quadrants = [
            1 => [],
            2 => [],
            3 => [],
            4 => [],
        ];

        $hideCompleted = session('hide_completed_tasks', true);
        $completedLimit = (int) config('settings.kanban_completed_limit', 10);

        foreach ($allTasks as $task) {
            $isCompleted = in_array($task->status, ['completed', 'cancelled']);
            if (!$hideCompleted || !$isCompleted) {
                if (!$isCompleted) {
                    $quadrant = $this->getQuadrant($task);
                    $quadrants[$quadrant][] = $task;
                }
            }
        }

        // Handle completed tasks separately with limit
        $completedTasks = $allTasks->where('status', 'completed')
            ->sortByDesc('updated_at')
            ->take($completedLimit);

        // Sort each quadrant by matrix_order (nulls last, preserving user-defined positions)
        foreach ($quadrants as &$qTasks) {
            usort($qTasks, function ($a, $b) {
                if ($a->matrix_order === null && $b->matrix_order === null) return 0;
                if ($a->matrix_order === null) return 1;
                if ($b->matrix_order === null) return -1;
                return $a->matrix_order <=> $b->matrix_order;
            });
        }
        unset($qTasks);

        $services = $team->services()->with(['reports' => function($q) {
            $q->latest()->limit(5);
        }])->get();

        return view('teams.dashboard', compact('team', 'quadrants', 'tasks', 'hideCompleted', 'skills', 'completedTasks', 'completedLimit', 'services'));
    }

    /**
     * Transfer team ownership to another user
     */
    public function transferOwnership(Request $request, Team $team)
    {
        $this->authorize('transferOwnership', $team);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $newOwner = User::findOrFail($validated['user_id']);

        // Check if user is member of the team
        if (!$team->members()->where('user_id', $newOwner->id)->exists()) {
            return back()->withErrors(['user_id' => 'El nuevo propietario debe ser miembro del equipo.']);
        }

        $oldOwnerId = auth()->id();

        // Transfer ownership
        $team->update(['created_by_id' => $newOwner->id]);

        // Ensure both new and old owner have coordinator role
        $coordinatorRole = TeamRole::where('name', 'coordinator')->first();
        if ($coordinatorRole) {
            $team->members()->updateExistingPivot($newOwner->id, ['role_id' => $coordinatorRole->id]);
            $team->members()->updateExistingPivot($oldOwnerId, ['role_id' => $coordinatorRole->id]);
        }

        // Si el usuario es administrador y no es miembro del equipo, volvemos a la gestión global
        if (auth()->user()->is_admin && !$team->members()->where('user_id', auth()->id())->exists()) {
            return redirect()->route('settings.teams')->with('success', __('teams.ownership_transferred'));
        }

        return redirect()->route('teams.show', $team)
            ->with('success', __('teams.ownership_transferred'));
    }

    /**
     * Update a quadrant color for the team.
     */
    public function updateQuadrantColor(Request $request, Team $team)
    {
        $this->authorize('update', $team);

        $validated = $request->validate([
            'quadrant' => 'required|integer|between:1,4',
            'color' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
        ]);

        // Normalize color to 6 digits hex
        $color = $validated['color'];
        if (strlen($color) === 4) {
            $color = '#' . $color[1] . $color[1] . $color[2] . $color[2] . $color[3] . $color[3];
        }

        $colors = $team->quadrant_colors ?? [];
        $colors[$validated['quadrant']] = $color;

        $team->update(['quadrant_colors' => $colors]);
        $team->save(); // Forzado de guardado

        \Log::emergency("CRITICAL DEBUG: Team {$team->id} color saved. Q: {$validated['quadrant']}, Color: {$color}. Total array: " . json_encode($colors));

        return response()->json(['success' => true]);
    }
    /**
     * Update the sort order of teams for the authenticated user.
     */
    public function updateOrder(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'required|integer|exists:teams,id',
        ]);

        $user = auth()->user();
        foreach ($validated['order'] as $index => $teamId) {
            $user->teams()->updateExistingPivot($teamId, ['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }
    /**
     * Get the active network member list partial for real-time updates.
     */
    public function activeNetwork(Team $team, \Illuminate\Http\Request $request)
    {
        $this->authorize('view', $team);

        $members = $team->getActiveMembers();

        if ($request->wantsJson() || $request->input('json')) {
            $heatmapData = $members->whereNotNull('location_lat')->map(function($u) {
                return [
                    'user_id' => $u->id,
                    'photo' => $u->profile_photo_url,
                    'lat' => (float)$u->location_lat,
                    'lng' => (float)$u->location_lng,
                    'count' => max(10, $u->experience_points / 2),
                    'name' => $u->name,
                    'area' => $u->working_area_name,
                    'radius' => (int)($u->impact_radius ?? 10) * 1000,
                    'is_working' => clone $u, // Hack to use isWorking correctly
                    'is_active' => $u->last_activity_at && $u->last_activity_at->gt(now()->subMinutes(15))
                ];
            })->map(function($data) {
                $u = $data['is_working'];
                $data['is_working'] = $u->last_login_at ? $u->isWorking() : false;
                return $data;
            })->values();

            $counts = ['working' => 0, 'online' => 0, 'sleeping' => 0, 'offline' => 0];
            foreach ($members as $m) {
                $status = $m->getStatusInfo()['status'];
                if (isset($counts[$status])) $counts[$status]++;
            }

            return response()->json([
                'html' => view('teams.partials.active-network-list', compact('members'))->render(),
                'mapData' => $heatmapData,
                'counts' => $counts
            ]);
        }

        return view('teams.partials.active-network-list', compact('members'));
    }

    /**
     * Get team members for mentions in JSON format.
     */
    public function mentionUsers(Team $team)
    {
        $this->authorize('view', $team);

        $members = $team->members()
            ->select('users.id', 'users.name')
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $user->profile_photo_url,
                ];
            });

        return response()->json($members);
    }

    public function toggleFavorite(Team $team)
    {
        $this->authorize('view', $team);
        $user = auth()->user();
        
        $isCurrentFavorite = $user->favorite_team_id === $team->id;
        
        $user->update([
            'favorite_team_id' => $isCurrentFavorite ? null : $team->id
        ]);

        return response()->json([
            'success' => true,
            'is_favorite' => !$isCurrentFavorite
        ]);
    }
}
