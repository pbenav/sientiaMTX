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
            session(['locale' => $request->input('locale')]);
            app()->setLocale($request->input('locale'));
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

    public function testTelegram(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'chat_id' => 'required|string',
        ]);

        $token = config('services.telegram.bot_token');

        if (!$token) {
            return response()->json([
                'success' => false, 
                'message' => __('notifications.telegram_bot_token_missing')
            ], 400);
        }

        try {
            $response = \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $request->chat_id,
                'text' => "🧪 *SientiaMTX - Prueba Personal*\n\nHola " . auth()->user()->name . ", ¡tu vinculación con Telegram funciona perfectamente! 🎉",
                'parse_mode' => 'Markdown',
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true, 
                    'message' => __('notifications.telegram_test_success')
                ]);
            }

            return response()->json([
                'success' => false, 
                'message' => $response->json('description') ?? 'Error desconocido de Telegram'
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the user's geographical action area.
     */
    public function updateZone(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'working_area_name' => 'required|string|max:255',
            'location_lat' => 'required|numeric|between:-90,90',
            'location_lng' => 'required|numeric|between:-180,180',
            'impact_radius' => 'required|integer|min:1|max:100',
        ]);

        $request->user()->update($validated);

        return back()->with('success', '¡Zona de acción actualizada! Tu impacto ahora es visible en el mapa.');
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
