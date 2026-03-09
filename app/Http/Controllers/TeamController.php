<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamRole;
use App\Models\User;
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

        return view('teams.members', compact('team', 'members', 'groups', 'roles'));
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
            return back()->withErrors(['email' => 'Usuario no encontrado']);
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
     * Show dashboard with matriz de Eisenhower
     */
    public function dashboard(Team $team)
    {
        $this->authorize('view', $team);

        $tasks = $team->tasks()->with(['assignedTo', 'assignedGroups', 'tags'])->get();

        // Group tasks by quadrant
        $quadrants = [
            1 => [],
            2 => [],
            3 => [],
            4 => [],
        ];

        foreach ($tasks as $task) {
            $quadrant = $this->getQuadrant($task);
            $quadrants[$quadrant][] = $task;
        }

        return view('teams.dashboard', compact('team', 'quadrants', 'tasks'));
    }

}
