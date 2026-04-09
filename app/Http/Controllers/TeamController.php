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
        $teams = auth()->user()->teams()->with(['members'])->paginate(15);

        return view('teams.index', compact('teams'));
    }

    /**
     * Display a listing of all teams for site administrators
     */
    public function indexAdmin(Request $request)
    {
        $this->authorize('admin'); // Ensure only global admins can access

        $query = Team::with(['members', 'creator']);

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

        $teams = $query->paginate(20)->withQueryString();

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
        $user = auth()->user();
        $isManager = $team->isManager($user);

        $team->load(['members', 'tasks' => function($query) use ($user, $isManager, $team) {
            $query->visibleTo($user, $isManager)
                  ->operationalFor($user, $team);
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

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:teams,name,' . $team->id,
            'description' => 'nullable|string|max:1000',
            'telegram_chat_id' => 'nullable|string|max:255',
        ]);

        $validated['slug'] = str($validated['name'])->slug();

        $team->update($validated);

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

        return redirect()->route('teams.index')
            ->with('success', __('teams.deleted'));
    }

    /**
     * Show team members and their roles
     */
    public function members(Team $team)
    {
        $this->authorize('viewMembers', $team);

        $members = $team->members()
            ->paginate(20, ['*'], 'members_page');

        $groups = $team->groups()->with('users')->get();
        $roles = TeamRole::all();
        $invitations = $team->invitations()->with('role')->get();

        return view('teams.members', compact('team', 'members', 'groups', 'roles', 'invitations'));
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
        $this->authorize('view', $team);

        $user = auth()->user();
        $isManager = $team->isManager($user);

        $query = $team->tasks()
            ->with(['assignedTo', 'assignedGroups', 'tags', 'children', 'assignedUser', 'skills'])
            ->visibleTo($user, $isManager)
            ->operationalFor($user, $team, true)
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

        foreach ($allTasks as $task) {
            $isCompleted = in_array($task->status, ['completed', 'cancelled']);
            if (!$hideCompleted || !$isCompleted) {
                $quadrant = $this->getQuadrant($task);
                $quadrants[$quadrant][] = $task;
            }
        }

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

        return view('teams.dashboard', compact('team', 'quadrants', 'tasks', 'hideCompleted', 'skills'));
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
}
