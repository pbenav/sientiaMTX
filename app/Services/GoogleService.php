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

        $this->client->addScope(Calendar::CALENDAR_READONLY);
        $this->client->addScope(Gmail::GMAIL_READONLY);
        $this->client->addScope(Tasks::TASKS); // Read/Write
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
                    Log::error('Google API Error (Refresh Token) for user ' . $user->id . ': ' . json_encode($newToken));
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
}
