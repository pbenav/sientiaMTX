<?php

namespace App\Services;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Gmail;
use Google\Service\Tasks;
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

        $this->client->addScope(Calendar::CALENDAR);
        $this->client->addScope(Tasks::TASKS); // Read/Write
        $this->client->addScope('https://www.googleapis.com/auth/drive'); // Full Drive access
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
     * Set the access token for a user (optionally for a specific team) and refresh if necessary.
     */
    public function setTokenForUser(User $user, $teamId = null): bool
    {
        $token = null;
        $refreshToken = null;
        $source = null;

        // 1. Try to get team-specific token from pivot
        if ($teamId) {
            $membership = $user->teams()->where('team_id', $teamId)->first();
            if ($membership && $membership->pivot->google_token) {
                $token = $membership->pivot->google_token;
                $refreshToken = $membership->pivot->google_refresh_token;
                $source = $membership->pivot;
            }
        }

        // 2. Fallback to global user token if no team token or no team specified
        if (!$token && $user->google_token) {
            $token = $user->google_token;
            $refreshToken = $user->google_refresh_token;
            $source = $user;
        }

        if (!$token) {
            return false;
        }

        // Since we now use TeamUser pivot with casting, $token might already be an array
        $tokenData = is_array($token) ? $token : json_decode($token, true);
        
        $this->client->setAccessToken($tokenData);

        if ($this->client->isAccessTokenExpired()) {
            if ($refreshToken) {
                $newToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                
                if (isset($newToken['error'])) {
                    Log::error('Google API Error (Refresh Token) for user ' . $user->id . ' (Team: '.$teamId.'): ' . json_encode($newToken));
                    return false;
                }

                $source->google_token = $newToken;
                $source->save();
                
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
    
    /**
     * List tasks for the authenticated user from all task lists.
     */
    public function listTasks(int $maxResults = 30): array
    {
        $service = new Tasks($this->client);
        try {
            // First get all task lists
            $taskLists = $service->tasklists->listTasklists();
            $allTasks = [];
            
            foreach ($taskLists->getItems() as $list) {
                $tasks = $service->tasks->listTasks($list->getId(), [
                    'maxResults' => $maxResults,
                    'showCompleted' => false,
                    'dueMin' => date('c'), // Only future tasks
                ]);
                
                foreach ($tasks->getItems() as $task) {
                    // Add list title to task for context
                    $task->listTitle = $list->getTitle();
                    $allTasks[] = $task;
                }
            }
            
            return $allTasks;
        } catch (\Exception $e) {
            Log::error('Google Tasks API Exception: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            return [];
        }
    }

    /**
     * Create a task in a Google Task List.
     */
    public function createTask(array $data, string $taskListId = '@default'): ?string
    {
        $service = new Tasks($this->client);
        $task = new \Google\Service\Tasks\Task($data);
        $result = $service->tasks->insert($taskListId, $task);
        return $result->getId();
    }

    /**
     * Update an existing task in Google Tasks.
     */
    public function updateTask(string $taskListId, string $taskId, array $data): bool
    {
        $service = new Tasks($this->client);
        try {
            $task = $service->tasks->get($taskListId, $taskId);
            
            if (isset($data['title'])) $task->setTitle($data['title']);
            if (isset($data['notes'])) $task->setNotes($data['notes']);
            if (isset($data['due'])) $task->setDue($data['due']);
            if (isset($data['status'])) $task->setStatus($data['status']);
            
            $service->tasks->update($taskListId, $taskId, $task);
            return true;
        } catch (\Exception $e) {
            Log::error('Error updating Google Task: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get a specific task from Google Tasks.
     */
    public function getTask(string $taskListId, string $taskId): ?\Google\Service\Tasks\Task
    {
        $service = new Tasks($this->client);
        try {
            return $service->tasks->get($taskListId, $taskId);
        } catch (\Exception $e) {
            Log::error('Error getting Google Task: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete a task from Google Tasks.
     */
    public function deleteTask(string $taskListId, string $taskId): bool
    {
        $service = new Tasks($this->client);
        try {
            $service->tasks->delete($taskListId, $taskId);
            return true;
        } catch (\Exception $e) {
            Log::error('Error deleting Google Task: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a calendar event in Google Calendar.
     */
    public function createEvent(array $data, string $calendarId = 'primary'): ?string
    {
        $service = new Calendar($this->client);
        try {
            $event = new \Google\Service\Calendar\Event($data);
            $result = $service->events->insert($calendarId, $event);
            return $result->getId();
        } catch (\Exception $e) {
            Log::error('Error creating Google Calendar event: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete an event from Google Calendar.
     */
    public function deleteEvent(string $eventId, string $calendarId = 'primary'): bool
    {
        $service = new Calendar($this->client);
        try {
            $service->events->delete($calendarId, $eventId);
            return true;
        } catch (\Exception $e) {
            Log::error('Error deleting Google Calendar event: ' . $e->getMessage());
            return false;
        }
    }
}
