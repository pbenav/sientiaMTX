<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\TeamInvitation;
use App\Models\SecurityLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AdminUserController extends Controller
{
    /**
     * Display the users list.
     */
    public function index(Request $request)
    {
        $query = User::withCount('invitations')->with('sessions');

        // Sorting
        $sort = $request->get('sort', 'name');
        $direction = $request->get('direction', 'asc');
        $query->orderBy($sort, $direction);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->get('role') === 'admin') {
            $query->where('is_admin', true);
        } elseif ($request->get('role') === 'user') {
            $query->where('is_admin', false);
        }

        if ($request->filled('premium')) {
            if ($request->get('premium') === '1') {
                $query->where('notification_settings->whatsapp_personal_allowed', true);
            } elseif ($request->get('premium') === '0') {
                $query->where(function ($q) {
                    $q->whereNull('notification_settings')
                      ->orWhere('notification_settings->whatsapp_personal_allowed', false);
                });
            }
        }

        if ($request->filled('approved')) {
            if ($request->get('approved') === '1') {
                $query->where('is_approved', true);
            } elseif ($request->get('approved') === '0') {
                $query->where('is_approved', false);
            }
        }

        if ($request->filled('status')) {
            $threshold = now()->subMinutes(15)->getTimestamp();
            if ($request->get('status') === 'online') {
                $query->whereHas('sessions', function($q) use ($threshold) {
                    $q->where('last_activity', '>', $threshold);
                });
            } elseif ($request->get('status') === 'offline') {
                $query->whereDoesntHave('sessions', function($q) use ($threshold) {
                    $q->where('last_activity', '>', $threshold);
                });
            }
        }

        $perPage = $request->get('per_page', 25);
        if ($perPage === 'all') {
            $perPage = $query->count() ?: 1;
        }

        $users = $query->paginate($perPage)->withQueryString();

        return view('settings.users', [
            'users' => $users,
            'search' => $request->get('search', ''),
            'role' => $request->get('role', ''),
            'premium' => $request->get('premium', ''),
            'approved' => $request->get('approved', ''),
            'status' => $request->get('status', ''),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $timezones = \DateTimeZone::listIdentifiers();
        return view('settings.user-create', compact('timezones'));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'locale' => 'required|string|in:en,es',
            'timezone' => 'nullable|timezone',
        ]);

        $siteTimezone = \App\Models\Setting::get('site_timezone', 'Europe/Madrid', true);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'locale' => $validated['locale'],
            'timezone' => $validated['timezone'] ?? $siteTimezone,
            'disk_quota' => config('settings.default_disk_quota', 100) * 1024 * 1024,
        ]);

        return redirect()->route('settings.users')
            ->with('success', __('User created successfully.'));
    }

    /**
     * Toggle the admin status of a user.
     */
    public function toggleAdmin(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', __('You cannot remove your own administrator privileges.'));
        }

        $user->is_admin = ! $user->is_admin;
        $user->save();

        return back()->with('success', __('User roles updated successfully.'));
    }

    /**
     * Show the user edit form.
     */
    public function edit(User $user)
    {
        $user->load('teams');
        $invitations = TeamInvitation::where('email', $user->email)->with('team', 'role')->get();
        $timezones = \DateTimeZone::listIdentifiers();
        
        return view('settings.user-edit', compact('user', 'invitations', 'timezones'));
    }

    /**
     * Update user information.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'locale' => 'required|string|in:en,es',
            'password' => 'nullable|string|min:8|confirmed',
            'disk_quota' => 'required|numeric|min:1',
            'timezone' => 'nullable|timezone',
            'invitations_left' => 'nullable|integer|min:0',
            'work_start_time_1' => 'nullable|string|regex:/^[0-9]{2}:[0-9]{2}$/',
            'work_end_time_1' => 'nullable|string|regex:/^[0-9]{2}:[0-9]{2}$/',
            'work_start_time_2' => 'nullable|string|regex:/^[0-9]{2}:[0-9]{2}$/',
            'work_end_time_2' => 'nullable|string|regex:/^[0-9]{2}:[0-9]{2}$/',
            'work_days_1' => 'nullable|array',
            'work_days_2' => 'nullable|array',
        ]);

        $user_settings = $user->notification_settings ?? $user->defaultNotificationSettings();
        $user_settings['whatsapp_personal_allowed'] = $request->boolean('whatsapp_personal_allowed');
        $user->notification_settings = $user_settings;

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'locale' => $validated['locale'],
            'timezone' => $validated['timezone'] ?? $user->timezone,
            'disk_quota' => $validated['disk_quota'] * 1024 * 1024,
            'invitations_left' => $validated['invitations_left'] ?? 0,
            'work_start_time_1' => $validated['work_start_time_1'] ?? $user->work_start_time_1,
            'work_end_time_1' => $validated['work_end_time_1'] ?? $user->work_end_time_1,
            'work_start_time_2' => $validated['work_start_time_2'] ?? $user->work_start_time_2,
            'work_end_time_2' => $validated['work_end_time_2'] ?? $user->work_end_time_2,
            'work_days_1' => $request->has('work_days_1') ? $validated['work_days_1'] : ($request->has('schedule_provided') ? [] : $user->work_days_1),
            'work_days_2' => $request->has('work_days_2') ? $validated['work_days_2'] : ($request->has('schedule_provided') ? [] : $user->work_days_2),
        ]);

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('settings.users')
            ->with('success', __('User updated successfully.'));
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('error', __('You cannot delete your own account.'));
        }

        $user->delete();

        return redirect()->route('settings.users')->with('success', __('User deleted successfully.'));
    }

    /**
     * Forcefully log out ALL sessions for a specific user.
     */
    public function forceLogout(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', __('Use the standard logout mechanism for your own session.'));
        }

        $user->sessions()->delete();

        SecurityLog::create([
            'user_id' => auth()->id(),
            'event' => 'admin.user.force_logout',
            'description' => "Admin requested force logout of user ID: {$user->id} ({$user->email}). All sessions purged.",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return back()->with('success', __("Successfully terminated all active sessions for :name.", ['name' => $user->name]));
    }

    /**
     * Accept an invitation on behalf of a user.
     */
    public function acceptInvitation(User $user, TeamInvitation $invitation)
    {
        // Verify email match
        if ($user->email !== $invitation->email) {
            return back()->with('error', __('This invitation is for a different email address.'));
        }

        // Attach user to team if not already a member
        if (!$invitation->team->members()->where('user_id', $user->id)->exists()) {
            $invitation->team->members()->attach($user->id, ['role_id' => $invitation->role_id]);
        }

        $invitation->delete();

        return back()->with('success', __('Invitation accepted successfully on behalf of the user.'));
    }

    /**
     * Aprobar a un usuario de la lista de espera.
     */
    public function approve(User $user)
    {
        $user->is_approved = true;
        $user->save();

        return back()->with('success', __('Usuario aprobado y activado con éxito en Sientia.'));
    }
}
