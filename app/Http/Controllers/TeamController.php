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
        $team->load(['members', 'tasks']);

        return view('teams.show', compact('team'));
    }

    /**
     * Show the form for editing the team
     */
    public function edit(Team $team)
    {
        $this->authorize('update', $team);

        return view('teams.edit', compact('team'));
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
        $isCoordinator = $team->isCoordinator($user);

        $query = $team->tasks()
            ->with(['assignedTo', 'assignedGroups', 'tags'])
            ->visibleTo($user, $isCoordinator)
            ->operationalFor($user, $team);

        // Matrix-specific filter for coordinators (as requested: hide templates and assigned tasks)
        if ($isCoordinator) {
            $query->where(function($q) use ($user) {
                // Return only unassigned non-templates OR tasks assigned specifically to ME
                $q->where(function($backlog) {
                    $backlog->where('is_template', false)
                            ->whereNull('assigned_user_id')
                            ->whereDoesntHave('assignedTo');
                })
                ->orWhere('assigned_user_id', $user->id);
            });
        }


        $allTasks = $query->get();
        $tasks = $allTasks; // Stay compatible with view expecting $tasks

        // Group tasks by quadrant, excluding completed ones
        $quadrants = [
            1 => [],
            2 => [],
            3 => [],
            4 => [],
        ];

        foreach ($allTasks as $task) {
            if ($task->status !== 'completed') {
                $quadrant = $this->getQuadrant($task);
                $quadrants[$quadrant][] = $task;
            }
        }

        return view('teams.dashboard', compact('team', 'quadrants', 'tasks'));
    }

}
