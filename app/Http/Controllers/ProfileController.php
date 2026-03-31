<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        if ($request->has('locale')) {
            session(['locale' => $request->locale]);
            app()->setLocale($request->locale);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update the user's notification settings.
     */
    public function updateNotifications(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mail' => 'boolean',
            'web_push' => 'boolean',
            'telegram' => 'boolean',
            'whatsapp' => 'boolean',
            'quiet_hours_enabled' => 'boolean',
            'quiet_hours_start' => 'string|nullable|regex:/^[0-9]{2}:[0-9]{2}$/',
            'quiet_hours_end' => 'string|nullable|regex:/^[0-9]{2}:[0-9]{2}$/',
            'notify_before_hours' => 'integer|min:0|max:168',
            'telegram_chat_id' => 'string|nullable|max:255',
        ]);

        $user = $request->user();
        
        // Merge with existing or default
        $current = $user->notification_settings ?? $user->defaultNotificationSettings();
        
        $newSettings = array_merge($current, [
            'mail' => $request->boolean('mail'),
            'web_push' => $request->boolean('web_push'),
            'telegram' => $request->boolean('telegram'),
            'whatsapp' => $request->boolean('whatsapp'),
            'quiet_hours_enabled' => $request->boolean('quiet_hours_enabled'),
            'quiet_hours_start' => $validated['quiet_hours_start'] ?? '22:00',
            'quiet_hours_end' => $validated['quiet_hours_end'] ?? '08:00',
            'notify_before_hours' => (int) ($validated['notify_before_hours'] ?? 2),
        ]);

        $user->notification_settings = $newSettings;
        
        if ($request->has('telegram_chat_id')) {
            $user->telegram_chat_id = $validated['telegram_chat_id'];
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'notifications-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
