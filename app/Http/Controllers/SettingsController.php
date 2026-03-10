<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! \Illuminate\Support\Facades\Gate::allows('admin')) {
                abort(403);
            }
            return $next($request);
        });
    }

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
            ]
        ]);
    }

    /**
     * Update the mail settings in the .env file.
     */
    public function updateMailSettings(Request $request)
    {
        $data = $request->validate([
            'MAIL_MAILER' => 'required|string',
            'MAIL_HOST' => 'required|string',
            'MAIL_PORT' => 'required|numeric',
            'MAIL_USERNAME' => 'nullable|string',
            'MAIL_PASSWORD' => 'nullable|string',
            'MAIL_ENCRYPTION' => 'nullable|string',
            'MAIL_FROM_ADDRESS' => 'required|email',
            'MAIL_FROM_NAME' => 'required|string',
        ]);

        try {
            foreach ($data as $key => $value) {
                $this->updateEnv($key, $value);
            }

            // Clear config cache to apply changes
            Artisan::call('config:clear');

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
