<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


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
                'mailer' => config('mail.default', 'smtp'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'username' => config('mail.mailers.smtp.username'),
                'password' => config('mail.mailers.smtp.password'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
            ],
            'google' => [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => config('services.google.redirect_uri') ?? route('google.callback'),
            ],
            'limits' => [
                'default_disk_quota' => config('settings.default_disk_quota', 100),
                'session_lifetime' => config('session.lifetime', 120),
                'kanban_completed_limit' => config('settings.kanban_completed_limit', 10),
                'quick_notes_audio_max_duration' => \App\Models\Setting::get('quick_notes_audio_max_duration', 60),
                'mfa_enabled' => \App\Models\Setting::get('mfa_enabled', false),
                'require_approval' => \App\Models\Setting::get('require_approval', true),
                'purge_inactive_enabled' => \App\Models\Setting::get('purge_inactive_enabled', false),
                'purge_inactive_days' => \App\Models\Setting::get('purge_inactive_days', 30),
                'purge_warning_days' => \App\Models\Setting::get('purge_warning_days', 5),
            ],
            'telegram' => [
                'bot_token' => config('services.telegram.bot_token'),
                'webhook_info' => $this->getTelegramWebhookInfo(),
            ],
            'site_timezone' => \App\Models\Setting::get('site_timezone', 'Europe/Madrid', true),
            'timezones' => \DateTimeZone::listIdentifiers(),
            'demo_mode' => config('settings.demo_mode', 'off'),
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
            'quick_notes_audio_max_duration' => 'sometimes|numeric|min:5|max:300',
            'mfa_enabled' => 'sometimes|boolean',
            'require_approval' => 'sometimes|boolean',
            'purge_inactive_enabled' => 'sometimes|boolean',
            'purge_inactive_days' => 'sometimes|numeric|min:1',
            'purge_warning_days' => 'sometimes|numeric|min:1',
            'demo_mode' => 'sometimes|in:on,off',
        ]);

        try {
            // site_timezone va a la tabla settings (no al .env)
            if (isset($data['site_timezone'])) {
                \App\Models\Setting::set('site_timezone', $data['site_timezone']);
                unset($data['site_timezone']);
            }

            if (isset($data['quick_notes_audio_max_duration'])) {
                \App\Models\Setting::set('quick_notes_audio_max_duration', $data['quick_notes_audio_max_duration']);
                unset($data['quick_notes_audio_max_duration']);
            }

            // mfa_enabled va a la tabla settings
            \App\Models\Setting::set('mfa_enabled', $request->has('mfa_enabled'));
            if (isset($data['mfa_enabled'])) {
                unset($data['mfa_enabled']);
            }

            // require_approval va a la tabla settings
            \App\Models\Setting::set('require_approval', $request->has('require_approval'));
            if (isset($data['require_approval'])) {
                unset($data['require_approval']);
            }

            // Ajustes de Purga de Cuentas inactivas
            \App\Models\Setting::set('purge_inactive_enabled', $request->has('purge_inactive_enabled'));
            if (isset($data['purge_inactive_enabled'])) unset($data['purge_inactive_enabled']);

            if (isset($data['purge_inactive_days'])) {
                \App\Models\Setting::set('purge_inactive_days', $data['purge_inactive_days']);
                unset($data['purge_inactive_days']);
            }

            if (isset($data['purge_warning_days'])) {
                \App\Models\Setting::set('purge_warning_days', $data['purge_warning_days']);
                unset($data['purge_warning_days']);
            }



            $updateExistingUsers = isset($data['update_existing_users']) || $request->has('update_existing_users');
            unset($data['update_existing_users']);

            // Update .env atomically in a single file-write operation
            $this->updateEnvMultiple($data);

            // Clear config cache to apply changes only if it is currently cached (prevents connection resets in development)
            if (app()->configurationIsCached()) {
                Artisan::call('config:clear');
            }

            // If requested, update all existing users (and teams if applicable) to the new values
            if ($updateExistingUsers) {
                $updateData = [];

                if (isset($data['DEFAULT_DISK_QUOTA'])) {
                    $updateData['disk_quota'] = $data['DEFAULT_DISK_QUOTA'] * 1024 * 1024;
                }

                if ($request->has('site_timezone') || isset($request->site_timezone)) {
                    $updateData['timezone'] = $request->site_timezone;
                }

                if (!empty($updateData)) {
                    \App\Models\User::query()->update($updateData);
                }
            }

            return back()->with('success', __('notifications.config_saved'));
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
        $secret = config('services.telegram.webhook_secret');

        try {
            $params = ['url' => $webhookUrl];
            if ($secret) {
                $params['secret_token'] = $secret;
            }

            $response = Http::post("https://api.telegram.org/bot{$token}/setWebhook", $params);

            if ($response->successful()) {
                return back()->with('success', __('notifications.webhook_registered_success', ['url' => $webhookUrl]));
            }

            return back()->with('error', __('notifications.webhook_registered_error', ['error' => $response->body()]));
        } catch (\Exception $e) {
            return back()->with('error', __('notifications.webhook_registered_error', ['error' => $e->getMessage()]));
        }
    }






    public function integrations()
    {
        return view('settings.integrations', [
            'cth' => [
                'url' => config('services.cth.url'),
                'secret' => config('services.cth.secret'),
            ]
        ]);
    }

    public function updateIntegrations(Request $request)
    {
        $data = $request->validate([
            'CTH_API_URL' => 'sometimes|nullable|url',
            'CTH_S2S_SECRET' => 'sometimes|nullable|string',
        ]);

        try {
            $this->updateEnvMultiple($data);

            if (app()->configurationIsCached()) {
                Artisan::call('config:clear');
            }

            return back()->with('success', __('notifications.config_saved'));
        } catch (\Exception $e) {
            return back()->with('error', __('Error updating .env file: ') . $e->getMessage());
        }
    }

    protected function updateEnvMultiple(array $data)
    {
        $path = base_path('.env');

        if (File::exists($path)) {
            $content = File::get($path);

            foreach ($data as $key => $value) {
                if (is_null($value)) {
                    $value = 'null';
                }

                // Wrap in quotes if special characters exist
                if (preg_match('/\s/m', $value) || str_contains($value, '#') || str_contains($value, '$')) {
                    $value = '"' . str_replace('"', '\"', $value) . '"';
                }

                // Check if key exists
                if (preg_match("/^{$key}=/m", $content)) {
                    // Use callback to bypass backreference issues in preg_replace
                    $content = preg_replace_callback("/^{$key}=.*/m", function() use ($key, $value) {
                        return "{$key}={$value}";
                    }, $content);
                } else {
                    $content .= "\n{$key}={$value}";
                }
            }

            File::put($path, $content);
        }
    }

    protected function updateEnv($key, $value)
    {
        $this->updateEnvMultiple([$key => $value]);
    }
}
