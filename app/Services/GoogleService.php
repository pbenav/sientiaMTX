<?php

namespace App\Services;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Gmail;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class GoogleService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        
        $redirectUri = config('services.google.redirect_uri');
        if (empty($redirectUri) && !app()->runningInConsole()) {
            $redirectUri = route('google.callback');
        } elseif (empty($redirectUri)) {
            $redirectUri = 'http://localhost';
        }
        $this->client->setRedirectUri($redirectUri);

        $this->client->addScope(Calendar::CALENDAR_READONLY);
        $this->client->addScope(Gmail::GMAIL_READONLY);
        $this->client->addScope('profile');
        $this->client->addScope('email');
        
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
    }

    /**
     * Get the Google Client instance.
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Check if the Google service is properly configured with credentials.
     */
    public function isConfigured(): bool
    {
        return !empty(config('services.google.client_id')) && 
               !empty(config('services.google.client_secret'));
    }

    /**
     * Set the access token for a user and refresh if necessary.
     */
    public function setTokenForUser(User $user): bool
    {
        if (!$user->google_token) {
            return false;
        }

        $token = json_decode($user->google_token, true);
        $this->client->setAccessToken($token);

        if ($this->client->isAccessTokenExpired()) {
            if ($user->google_refresh_token) {
                $newToken = $this->client->fetchAccessTokenWithRefreshToken($user->google_refresh_token);
                
                if (isset($newToken['error'])) {
                    Log::error('Error refreshing Google token for user ' . $user->id . ': ' . $newToken['error']);
                    return false;
                }

                $user->google_token = json_encode($newToken);
                $user->save();
                
                $this->client->setAccessToken($newToken);
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * List calendar events for the authenticated user.
     */
    public function listEvents(int $maxResults = 10): array
    {
        $service = new Calendar($this->client);
        $calendarId = 'primary';
        $optParams = [
            'maxResults' => $maxResults,
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => date('c'),
        ];
        
        try {
            $results = $service->events->listEvents($calendarId, $optParams);
            return $results->getItems();
        } catch (\Exception $e) {
            Log::error('Error listing Google Calendar events: ' . $e->getMessage());
            return [];
        }
    }
}
