<?php

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
            'settings' => 'nullable|array',
        ];

        if (auth()->user()->is_admin) {
            $rules['disk_quota_gb'] = 'required|numeric|min:0.1';
        }

        $validated = $request->validate($rules);

        if (isset($validated['telegram_chat_id'])) {
            $validated['telegram_chat_id'] = trim($validated['telegram_chat_id']);
        }

        if (auth()->user()->is_admin && $request->has('disk_quota_gb')) {
            $validated['disk_quota'] = (int)($request->disk_quota_gb * 1024 * 1024 * 1024);
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

        $team->delete();

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

        $query = $team->members();

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

        $groups = $team->groups()->with('users')->get();
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

        return back()->with('success', __('teams.member_added'));
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

        // PROTECT: Cannot promote yourself to Coordinator if you aren't already one.
        // Actually, the Policy already restricts this to Coordinators/Owners.
        // But let's be double sure: If you are NOT the owner and NOT a coordinator, abort.
        if (!$team->isOwner(auth()->user()) && !$team->isCoordinator(auth()->user())) {
             abort(403, 'Unauthorized action.');
        }

        // Even if you ARE a coordinator, you shouldn't be able to remove your own coordinator status
        // unless there's at least one other coordinator (though usually the owner is the safety net).
        
        $team->members()->updateExistingPivot($user->id, ['role_id' => $validated['role_id']]);

        return back()->with('success', __('teams.member_role_updated'));
    }

    /**
     * Update member's personal info (name/email)
     */
    public function updateMemberInfo(Request $request, Team $team, User $user)
    {
        $this->authorize('manageMembers', $team);

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
                'assignedTo', 'assignedGroups', 'tags', 'assignedUser', 'skills',
                'children' => function($q) use ($user, $isManager) {
                    $q->visibleTo($user, $isManager);
                }
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
        $completedLimit = (int) env('KANBAN_COMPLETED_LIMIT', 10);

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
    public function activeNetwork(Team $team)
    {
        $this->authorize('view', $team);

        // Obtenemos miembros que tienen ubicación 
        // O que han tenido actividad reciente (sesión o logs)
        $members = $team->members()
            ->where(function($query) {
                $query->whereNotNull('location_lat')
                      ->orWhereExists(function ($q) {
                          $q->select(\DB::raw(1))
                            ->from('sessions')
                            ->whereColumn('sessions.user_id', 'users.id')
                            ->where('last_activity', '>', now()->subMinutes(15)->getTimestamp());
                      })
                      ->orWhereExists(function ($q) {
                          $q->select(\DB::raw(1))
                            ->from('time_logs')
                            ->whereColumn('time_logs.user_id', 'users.id')
                            ->whereIn('type', ['workday', 'task'])
                            ->whereNull('end_at');
                      });
            })
            ->orderBy('name')
            ->get();

        return view('teams.partials.active-network-list', compact('members'));
    }
}
