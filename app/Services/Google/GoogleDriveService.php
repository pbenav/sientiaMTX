<?php

namespace App\Services\Google;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    protected string $baseUrl = 'https://www.googleapis.com/drive/v3';
    protected string $uploadUrl = 'https://www.googleapis.com/upload/drive/v3/files';

    /**
     * Get or Refresh valid access token for user
     */
    public function getValidToken(User $user): ?string
    {
        if (!$user->google_token) return null;

        // Check if token is expired (minimal logic, ideally check expiry time)
        // If expired or force refresh:
        if ($user->google_refresh_token) {
            return $this->refreshToken($user);
        }

        return $user->google_token;
    }

    /**
     * Refresh the access token using the refresh token
     */
    public function refreshToken(User $user): ?string
    {
        $response = Http::post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $user->google_refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $user->update([
                'google_token' => $data['access_token'],
            ]);
            return $data['access_token'];
        }

        Log::error('Failed to refresh Google token: ' . $response->body());
        return null;
    }

    /**
     * Upload a file to Google Drive from a local path
     */
    public function uploadFileFromPath(User $user, string $filePath, string $name, ?string $folderId = null): ?array
    {
        $token = $this->getValidToken($user);
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
    public function createFileFromText(User $user, string $name, string $content, ?string $folderId = null, bool $asGoogleDoc = false): ?array
    {
        $token = $this->getValidToken($user);
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
     * Upload a file to Google Drive (UploadedFile object)
     */
    public function uploadFile(User $user, $file, string $name, ?string $folderId = null): ?array
    {
        $token = $this->getValidToken($user);
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
    public function getOrCreateSientiaFolder(User $user): ?string
    {
        $token = $this->getValidToken($user);
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
