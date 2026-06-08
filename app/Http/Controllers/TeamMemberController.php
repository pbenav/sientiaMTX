<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamRole;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Models\SecurityLog;
use App\Notifications\InvitationNotification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;
use Illuminate\Http\Request;

class TeamMemberController extends Controller
{
    /**
     * Show team members and their roles
     */
    public function index(Request $request, Team $team)
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
    public function store(Request $request, Team $team)
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

        SecurityLog::log(
            'team.member_added',
            "Usuario {$user->email} añadido al equipo {$team->name} con rol_id {$validated['role_id']}"
        );

        return back()->with('success', __('teams.member_added'));
    }

    /**
     * Add multiple members to the team in bulk
     */
    public function bulkStore(Request $request, Team $team)
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
    public function updateRole(Request $request, Team $team, User $user)
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

        SecurityLog::log(
            'team.role_updated',
            "Rol del usuario {$user->email} actualizado en el equipo {$team->name} a rol_id {$validated['role_id']}"
        );

        return back()->with('success', __('teams.member_role_updated'));
    }

    /**
     * Update member's personal info (name/email)
     */
    public function updateInfo(Request $request, Team $team, User $user)
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
     * Update member's pre-appointment permissions
     */
    public function updateAppointments(Request $request, Team $team, User $user)
    {
        $this->authorize('admin');

        $team->members()->updateExistingPivot($user->id, [
            'allow_appointments' => $request->boolean('allow_appointments')
        ]);

        return back()->with('success', 'Permisos de Cita Previa actualizados para el miembro.');
    }

    /**
     * Update member's microsites permissions
     */
    public function updateMicrosites(Request $request, Team $team, User $user)
    {
        $this->authorize('admin');

        $team->members()->updateExistingPivot($user->id, [
            'allow_microsites' => $request->boolean('allow_microsites')
        ]);

        return back()->with('success', 'Permisos de creación de Micrositios actualizados para el miembro.');
    }

    /**
     * Habilitar o deshabilitar cita previa para TODOS los miembros del equipo masivamente
     */
    public function updateAllAppointments(Request $request, Team $team)
    {
        $this->authorize('admin');

        $allow = $request->boolean('allow');
        $memberIds = $team->members()->pluck('users.id')->toArray();

        if (!empty($memberIds)) {
            $team->members()->updateExistingPivot($memberIds, [
                'allow_appointments' => $allow
            ]);
        }

        $statusText = $allow ? 'habilitado' : 'deshabilitado';
        return back()->with('success', "Se ha {$statusText} el acceso al portal de citas previas para todos los miembros del equipo.");
    }

    /**
     * Habilitar o deshabilitar micrositios para TODOS los miembros del equipo masivamente
     */
    public function updateAllMicrosites(Request $request, Team $team)
    {
        $this->authorize('admin');

        $allow = $request->boolean('allow');
        $memberIds = $team->members()->pluck('users.id')->toArray();

        if (!empty($memberIds)) {
            $team->members()->updateExistingPivot($memberIds, [
                'allow_microsites' => $allow
            ]);
        }

        $statusText = $allow ? 'habilitado' : 'deshabilitado';
        return back()->with('success', "Se ha {$statusText} el acceso a los micrositios para todos los miembros del equipo.");
    }

    /**
     * Remove a member from the team
     */
    public function destroy(Team $team, User $user)
    {
        $this->authorize('manageMembers', $team);

        $team->members()->detach($user->id);

        SecurityLog::log(
            'team.member_removed',
            "Usuario {$user->email} eliminado del equipo {$team->name}"
        );

        return back()->with('success', __('teams.member_removed'));
    }

    /**
     * Remove a pending invitation
     */
    public function destroyInvitation(Team $team, TeamInvitation $invitation)
    {
        $this->authorize('manageMembers', $team);

        $invitation->delete();

        return back()->with('success', __('teams.invitation_cancelled'));
    }
}
