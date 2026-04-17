<?php

namespace App\Services\Google;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service to interact with Google Drive API
 * Strictly Team-based context for Sientia MTX
 */
class GoogleDriveService
{
    protected string $baseUrl = 'https://www.googleapis.com/drive/v3';
    protected string $uploadUrl = 'https://www.googleapis.com/upload/drive/v3/files';

    /**
     * Get or Refresh valid access token for user in a specific team context
     */
    public function getValidToken(User $user, ?int $teamId = null): ?string
    {
        $tokenData = null;
        $refreshToken = null;

        if ($teamId) {
            $team = $user->teams()->find($teamId);
            if ($team && !empty($team->pivot->google_token)) {
                $tokenData = $team->pivot->google_token;
                $refreshToken = $team->pivot->google_refresh_token;
            }
        } else {
            // Fallback to global user token (legacy or future personal use)
            $tokenData = $user->google_token;
            $refreshToken = $user->google_refresh_token;
        }

        if (empty($tokenData)) return null;

        // Ensure tokenData is an array (handle legacy cases or if casting failed)
        if (is_string($tokenData) && str_starts_with($tokenData, '{')) {
            $tokenData = json_decode($tokenData, true);
        }

        $accessToken = is_array($tokenData) ? ($tokenData['access_token'] ?? null) : $tokenData;

        // Check if expired and refresh if possible
        if (!$accessToken && $refreshToken) {
            return $this->refreshToken($user, $teamId);
        }

        return $accessToken;
    }

    /**
     * Refresh the access token using the refresh token
     */
    public function refreshToken(User $user, ?int $teamId = null): ?string
    {
        $refreshToken = null;

        if ($teamId) {
            $team = $user->teams()->find($teamId);
            $refreshToken = $team?->pivot?->google_refresh_token;
        } else {
            $refreshToken = $user->google_refresh_token;
        }

        if (!$refreshToken) return null;

        $response = Http::post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $newToken = $data['access_token'];

            if ($teamId) {
                $user->teams()->updateExistingPivot($teamId, [
                    'google_token' => $newToken,
                ]);
            } else {
                $user->update(['google_token' => $newToken]);
            }

            return $newToken;
        }

        Log::error('Failed to refresh Google token for ' . ($teamId ? "team $teamId" : "global") . ': ' . $response->body());
        return null;
    }

    /**
     * Upload a file to Google Drive from a local path
     */
    public function uploadFileFromPath(User $user, string $filePath, string $name, ?string $folderId = null, ?int $teamId = null): ?array
    {
        $token = $this->getValidToken($user, $teamId);
        if (!$token) return null;

        $metadata = [
            'name' => $name,
            'parents' => $folderId ? [$folderId] : []
        ];

        $response = Http::withToken($token)
            ->attach('metadata', json_encode($metadata), 'metadata.json', ['Content-Type' => 'application/json'])
            ->attach('file', fopen($filePath, 'r'), $name)
            ->post($this->uploadUrl . '?uploadType=multipart&fields=id,name,webViewLink,size,mimeType');

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Google Drive Upload Path Error: ' . $response->body());
        return null;
    }

    /**
     * Create a simple text file or Google Doc in Drive from text content
     */
    public function createFileFromText(User $user, string $name, string $content, ?string $folderId = null, bool $asGoogleDoc = false, ?int $teamId = null): ?array
    {
        $token = $this->getValidToken($user, $teamId);
        if (!$token) return null;

        $metadata = [
            'name' => $name,
            'parents' => $folderId ? [$folderId] : []
        ];

        if ($asGoogleDoc) {
            $metadata['mimeType'] = 'application/vnd.google-apps.document';
        }

        $response = Http::withToken($token)
            ->attach('metadata', json_encode($metadata), 'metadata.json', ['Content-Type' => 'application/json'])
            ->attach('file', $content, 'content.txt', ['Content-Type' => 'text/plain'])
            ->post($this->uploadUrl . '?uploadType=multipart&fields=id,name,webViewLink');

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Google Drive Create Text File Error: ' . $response->body());
        return null;
    }

    /**
     * List files from Google Drive
     */
    public function listFiles(User $user, string $query = "trashed = false", int $pageSize = 20, ?int $teamId = null): ?array
    {
        $token = $this->getValidToken($user, $teamId);
        if (!$token) return null;

        $response = Http::withToken($token)
            ->get($this->baseUrl . '/files?q=' . urlencode($query) . '&pageSize=' . $pageSize . '&fields=files(id,name,mimeType,webViewLink,iconLink,size)');

        if ($response->successful()) {
            return $response->json()['files'];
        }

        Log::error('Google Drive List Error: ' . $response->body());
        return null;
    }

    /**
     * Upload a file to Google Drive (UploadedFile object)
     */
    public function uploadFile(User $user, $file, string $name, ?string $folderId = null, ?int $teamId = null): ?array
    {
        $token = $this->getValidToken($user, $teamId);
        if (!$token) return null;

        // Multi-part upload for file + metadata
        $metadata = [
            'name' => $name,
            'parents' => $folderId ? [$folderId] : []
        ];

        $response = Http::withToken($token)
            ->attach('metadata', json_encode($metadata), 'metadata.json', ['Content-Type' => 'application/json'])
            ->attach('file', fopen($file->getRealPath(), 'r'), $name)
            ->post($this->uploadUrl . '?uploadType=multipart&fields=id,name,webViewLink,size,mimeType');

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Google Drive Upload Error: ' . $response->body());
        return null;
    }

    /**
     * Create Sientia Folder in Drive if not exists
     */
    public function getOrCreateSientiaFolder(User $user, ?int $teamId = null): ?string
    {
        $token = $this->getValidToken($user, $teamId);
        if (!$token) return null;

        // Search for folder
        $search = Http::withToken($token)->get($this->baseUrl . '/files', [
            'q' => "name = 'SientiaMTX' and mimeType = 'application/vnd.google-apps.folder' and trashed = false",
            'fields' => 'files(id)'
        ]);

        if ($search->successful() && count($search->json('files')) > 0) {
            return $search->json('files')[0]['id'];
        }

        // Create it
        $create = Http::withToken($token)->post($this->baseUrl . '/files', [
            'name' => 'SientiaMTX',
            'mimeType' => 'application/vnd.google-apps.folder'
        ]);

        return $create->json('id');
    }
}
