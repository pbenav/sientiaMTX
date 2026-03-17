<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\TeamInvitation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class SettingsController extends Controller
{
    /**
     * Show the mail settings form.
     */
    public function mailSettings()
    {
        return view('settings.mail', [
            'config' => [
                'mailer' => env('MAIL_MAILER', 'smtp'),
                'host' => env('MAIL_HOST'),
                'port' => env('MAIL_PORT'),
                'username' => env('MAIL_USERNAME'),
                'password' => env('MAIL_PASSWORD'),
                'encryption' => env('MAIL_ENCRYPTION'),
                'from_address' => env('MAIL_FROM_ADDRESS'),
                'from_name' => env('MAIL_FROM_NAME'),
            ],
            'google' => [
                'client_id' => env('GOOGLE_CLIENT_ID'),
                'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                'redirect_uri' => env('GOOGLE_REDIRECT_URI', route('google.callback')),
            ],
            'limits' => [
                'default_disk_quota' => env('DEFAULT_DISK_QUOTA', 100), // In MB
                'session_lifetime' => env('SESSION_LIFETIME', 120), // In minutes
            ]
        ]);
    }

    /**
     * Display the users list.
     */
    public function users()
    {
        $users = User::withCount('invitations')->latest()->paginate(20);
        
        return view('settings.users', [
            'users' => $users
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function createUser()
    {
        return view('settings.user-create');
    }

    /**
     * Store a newly created user.
     */
    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'locale' => 'required|string|in:en,es',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'locale' => $validated['locale'],
            'disk_quota' => env('DEFAULT_DISK_QUOTA', 100) * 1024 * 1024,
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
    public function editUser(User $user)
    {
        $user->load('teams');
        $invitations = TeamInvitation::where('email', $user->email)->with('team', 'role')->get();
        
        return view('settings.user-edit', compact('user', 'invitations'));
    }

    /**
     * Update user information.
     */
    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'locale' => 'required|string|in:en,es',
            'password' => 'nullable|string|min:8|confirmed',
            'disk_quota' => 'required|numeric|min:1',
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'locale' => $validated['locale'],
            'disk_quota' => $validated['disk_quota'] * 1024 * 1024,
        ]);

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('settings.users')
            ->with('success', __('User updated successfully.'));
    }

    public function destroyUser(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('error', __('You cannot delete your own account.'));
        }

        $user->delete();

        return redirect()->route('settings.users')->with('success', __('User deleted successfully.'));
    }

    /**
     * Accept an invitation on behalf of a user.
     */
    public function acceptUserInvitation(User $user, TeamInvitation $invitation)
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
     * Update the mail settings in the .env file.
     */
    public function updateMailSettings(Request $request)
    {
        $data = $request->validate([
            'MAIL_MAILER' => 'sometimes|string',
            'MAIL_HOST' => 'sometimes|string',
            'MAIL_PORT' => 'sometimes|numeric',
            'MAIL_USERNAME' => 'sometimes|nullable|string',
            'MAIL_PASSWORD' => 'sometimes|nullable|string',
            'MAIL_ENCRYPTION' => 'sometimes|nullable|string',
            'MAIL_FROM_ADDRESS' => 'sometimes|email',
            'MAIL_FROM_NAME' => 'sometimes|string',
            'GOOGLE_CLIENT_ID' => 'sometimes|nullable|string',
            'GOOGLE_CLIENT_SECRET' => 'sometimes|nullable|string',
            'DEFAULT_DISK_QUOTA' => 'sometimes|nullable|numeric',
            'SESSION_LIFETIME' => 'sometimes|nullable|numeric|min:1',
            'update_existing_users' => 'sometimes|boolean',
        ]);

        try {
            foreach ($data as $key => $value) {
                $this->updateEnv($key, $value);
            }

            // Clear config cache to apply changes
            Artisan::call('config:clear');

            // If requested, update all existing users to the new quota
            if (isset($data['DEFAULT_DISK_QUOTA']) && $request->has('update_existing_users')) {
                $newQuotaBytes = $data['DEFAULT_DISK_QUOTA'] * 1024 * 1024;
                \App\Models\User::query()->update(['disk_quota' => $newQuotaBytes]);
            }

            return back()->with('success', __('Mail settings updated successfully.'));
        } catch (\Exception $e) {
            return back()->with('error', __('Error updating .env file: ') . $e->getMessage());
        }
    }

    /**
     * Send a test email.
     */
    public function testMail(Request $request)
    {
        $request->validate([
            'test_email' => 'required|email',
        ]);

        try {
            Mail::raw('This is a test email from sientiaMTX to verify your SMTP settings.', function ($message) use ($request) {
                $message->to($request->test_email)
                        ->subject('sientiaMTX - SMTP Test Email');
            });

            return back()->with('success', __('Test email sent successfully to :email', ['email' => $request->test_email]));
        } catch (\Exception $e) {
            return back()->with('error', __('Failed to send test email: ') . $e->getMessage());
        }
    }

    /**
     * Update a value in the .env file.
     */
    protected function updateEnv($key, $value)
    {
        $path = base_path('.env');

        if (File::exists($path)) {
            $content = File::get($path);
            
            // Check if key exists
            if (preg_match("/^{$key}=/m", $content)) {
                // Special handling for passwords or values with special chars: wrap in quotes if needed
                if (preg_match('/\s/m', $value) || str_contains($value, '#') || str_contains($value, '$')) {
                    $value = '"' . str_replace('"', '\"', $value) . '"';
                }
                
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
            } else {
                $content .= "\n{$key}={$value}";
            }

            File::put($path, $content);
        }
    }
}
