<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\TeamInvitation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

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
                'default_disk_quota' => env('DEFAULT_DISK_QUOTA', 100),
                'session_lifetime' => env('SESSION_LIFETIME', 120),
                'kanban_completed_limit' => env('KANBAN_COMPLETED_LIMIT', 10),
            ],
            'telegram' => [
                'bot_token' => config('services.telegram.bot_token'),
                'webhook_info' => $this->getTelegramWebhookInfo(),
            ],
            'site_timezone' => \App\Models\Setting::get('site_timezone', 'Europe/Madrid', true),
            'timezones' => \DateTimeZone::listIdentifiers(),
        ]);
    }

    /**
     * Get the current webhook status from Telegram.
     */
    protected function getTelegramWebhookInfo()
    {
        $token = config('services.telegram.bot_token');
        if (!$token) return null;

        try {
            $response = \Illuminate\Support\Facades\Http::get("https://api.telegram.org/bot{$token}/getWebhookInfo");
            return $response->json('result');
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
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
            'KANBAN_COMPLETED_LIMIT' => 'sometimes|nullable|numeric|min:1|max:100',
            'TELEGRAM_BOT_TOKEN' => 'sometimes|nullable|string',
            'update_existing_users' => 'sometimes|boolean',
            'site_timezone' => 'sometimes|nullable|timezone',
        ]);

        try {
            // site_timezone va a la tabla settings (no al .env)
            if (isset($data['site_timezone'])) {
                \App\Models\Setting::set('site_timezone', $data['site_timezone']);
                unset($data['site_timezone']);
            }

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

            return back()->with('success', __('notifications.config_saved'));
        } catch (\Exception $e) {
            return back()->with('error', __('Error updating .env file: ') . $e->getMessage());
        }
    }

    /**
     * Show the legal settings form.
     */
    public function legalSettings()
    {
        $privacy = Setting::get('legal_privacy');
        $terms = Setting::get('legal_terms');
        $cookies = Setting::get('legal_cookies');

        // Pre-load defaults if empty
        if (empty($privacy)) {
            $privacy = '<h1>Política de Privacidad</h1><p>En <strong>' . config('app.name', 'Sientia') . '</strong>, nos tomamos muy en serio la privacidad de tus datos. Esta Política de Privacidad describe cómo recopilamos, utilizamos y protegemos tu información personal.</p><h2>1. Identificación del Responsable</h2><p>El responsable del tratamiento de tus datos es <strong>[Nombre del Responsable / Empresa]</strong>.</p><h2>2. Datos que Recopilamos</h2><p>Recopilamos información de registro (nombre, email), datos de uso de la plataforma y preferencias de usuario.</p><h2>3. Finalidad del Tratamiento</h2><p>Tus datos se utilizan para gestionar tu acceso, sincronizar servicios autorizados y mejorar la experiencia de usuario.</p><h2>4. Tus Derechos</h2><p>Puedes ejercer tus derechos de acceso, rectificación, supresión y portabilidad en cualquier momento a través de tu perfil.</p><div class="bg-blue-50 p-4 rounded-xl">Última actualización: ' . now()->format('d/m/Y') . '</div>';
        }

        if (empty($terms)) {
            $terms = '<h1>Términos de Servicio</h1><p>Bienvenido a <strong>' . config('app.name', 'Sientia') . '</strong>. Al utilizar nuestra plataforma, aceptas estos términos y condiciones.</p><h2>1. Registro</h2><p>Debes proporcionar información veraz y mantener la confidencialidad de tu cuenta.</p><h2>2. Normas de Uso</h2><p>No se permite el uso de la plataforma para actividades ilícitas o que interfieran con el servicio.</p><h2>3. Propiedad Intelectual</h2><p>El software y contenidos son propiedad de <strong>[Nombre del Responsable / Empresa]</strong>.</p><h2>4. Limitación de Responsabilidad</h2><p>SientiaMTX se ofrece "tal cual", sin garantías explícitas sobre la disponibilidad ininterrumpida.</p><div class="bg-blue-50 p-4 rounded-xl">Última actualización: ' . now()->format('d/m/Y') . '</div>';
        }

        if (empty($cookies)) {
            $cookies = '<h1>Política de Cookies</h1><p>Utilizamos cookies propias y de terceros para mejorar tu experiencia.</p><h2>1. ¿Qué son las Cookies?</h2><p>Son pequeños archivos que se guardan en tu navegador para recordar tus preferencias.</p><h2>2. Cookies que usamos</h2><ul><li><strong>Técnicas:</strong> Necesarias para el funcionamiento.</li><li><strong>Personalización:</strong> Para recordar tus ajustes de tema e idioma.</li><li><strong>Terceros:</strong> Gestionadas por servicios como Google OAuth.</li></ul><h2>3. Gestión</h2><p>Puedes bloquear las cookies desde los ajustes de tu navegador.</p><div class="bg-blue-50 p-4 rounded-xl">Última actualización: ' . now()->format('d/m/Y') . '</div>';
        }

        return view('settings.legal', [
            'privacy' => $privacy,
            'terms' => $terms,
            'cookies' => $cookies,
        ]);
    }

    /**
     * Update the legal settings in the database.
     */
    public function updateLegalSettings(Request $request)
    {
        $validated = $request->validate([
            'legal_privacy' => 'nullable|string',
            'legal_terms' => 'nullable|string',
            'legal_cookies' => 'nullable|string',
            'notify_changes' => 'nullable|boolean',
        ]);

        foreach (['legal_privacy', 'legal_terms', 'legal_cookies'] as $key) {
            if (isset($validated[$key])) {
                Setting::set($key, $validated[$key]);
            }
        }

        if ($request->has('notify_changes')) {
            Setting::set('legal_updated_at', now()->toDateTimeString());
        }

        return back()->with('success', __('notifications.config_saved'));
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
     * Send a test Telegram message.
     */
    public function testTelegram(Request $request)
    {
        $request->validate([
            'test_chat_id' => 'required|string',
        ]);

        $token = config('services.telegram.bot_token');

        if (!$token) {
            return back()->with('error', __('notifications.telegram_bot_token_missing'));
        }

        try {
            $response = \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $request->test_chat_id,
                'text' => "🧪 *SientiaMTX - Mensaje de Prueba*\n\n¡La conexión con tu bot de Telegram funciona correctamente! 🎉",
                'parse_mode' => 'Markdown',
            ]);

            if ($response->successful()) {
                return back()->with('success', __('notifications.telegram_test_success'));
            }

            return back()->with('error', __('notifications.telegram_test_error', ['error' => $response->body()]));
        } catch (\Exception $e) {
            return back()->with('error', __('notifications.telegram_test_error', ['error' => $e->getMessage()]));
        }
    }
    /**
     * Register the Telegram Webhook URL.
     */
    public function registerTelegramWebhook()
    {
        $token = config('services.telegram.bot_token');

        if (!$token) {
            return back()->with('error', __('notifications.telegram_bot_token_missing'));
        }

        $webhookUrl = route('telegram.webhook');

        try {
            $response = Http::post("https://api.telegram.org/bot{$token}/setWebhook", [
                'url' => $webhookUrl,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return back()->with('success', __('notifications.webhook_registered_success', ['url' => $webhookUrl]) . ' Detail: ' . json_encode($result));
            }

            return back()->with('error', __('notifications.webhook_registered_error', ['error' => $response->body()]));
        } catch (\Exception $e) {
            return back()->with('error', __('notifications.webhook_registered_error', ['error' => $e->getMessage()]));
        }
    }

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
