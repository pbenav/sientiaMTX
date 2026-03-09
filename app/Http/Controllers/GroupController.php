<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    /**
     * Store a newly created group in storage.
     */
    public function store(Request $request, Team $team)
    {
        $this->authorize('manageMembers', $team);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $group = $team->groups()->create($validated);

        return back()->with('success', __('Groups created successfully.'))->with('tab', 'groups');
    }

    /**
     * Update the specified group in storage.
     */
    public function update(Request $request, Team $team, Group $group)
    {
        $this->authorize('manageMembers', $team);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $group->update($validated);

        return back()->with('success', __('Group updated successfully.'))->with('tab', 'groups');
    }

    /**
     * Remove the specified group from storage.
     */
    public function destroy(Team $team, Group $group)
    {
        $this->authorize('manageMembers', $team);

        $group->delete();

        return back()->with('success', __('Group deleted successfully.'))->with('tab', 'groups');
    }

    /**
     * Add a member to the group.
     */
    public function addMember(Request $request, Team $team, Group $group)
    {
        $this->authorize('manageMembers', $team);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        // Ensure user is member of the team
        if (!$team->members()->where('user_id', $validated['user_id'])->exists()) {
            return back()->withErrors(['user_id' => 'User is not a member of this team.']);
        }

        $group->users()->syncWithoutDetaching([$validated['user_id']]);

        return back()->with('success', __('Member added to group.'))->with('tab', 'groups');
    }

    /**
     * Remove a member from the group.
     */
    public function removeMember(Team $team, Group $group, User $user)
    {
        $this->authorize('manageMembers', $team);

        $group->users()->detach($user->id);

        return back()->with('success', __('Member removed from group.'))->with('tab', 'groups');
    }
}
