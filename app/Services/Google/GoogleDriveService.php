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
        $expiresAt = is_array($tokenData) ? (($tokenData['created'] ?? 0) + ($tokenData['expires_in'] ?? 0)) : 0;

        // Check if expired (with a 30s buffer) or missing
        if (!$accessToken || ($expiresAt > 0 && time() > ($expiresAt - 30))) {
            if ($refreshToken) {
                return $this->refreshToken($user, $teamId);
            }
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
            $data['created'] = time(); // Add timestamp for expiration tracking

            if ($teamId) {
                $user->teams()->updateExistingPivot($teamId, [
                    'google_token' => $data,
                ]);
            } else {
                $user->update(['google_token' => $data]);
            }

            return $data['access_token'];
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
            ->get($this->baseUrl . '/files', [
                'q' => $query,
                'pageSize' => $pageSize,
                'fields' => 'files(id,name,mimeType,webViewLink,iconLink,size)',
                'supportsAllDrives' => 'true',
                'includeItemsFromAllDrives' => 'true'
            ]);

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
            'fields' => 'files(id)',
            'supportsAllDrives' => 'true',
            'includeItemsFromAllDrives' => 'true'
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

    /**
     * Get the content and mimeType of a file from Google Drive
     */
    public function getFileContent(User $user, string $fileId, ?int $teamId = null): ?array
    {
        $token = $this->getValidToken($user, $teamId);
        if (!$token) return null;

        try {
            // 1. Get file metadata to check mimeType and shortcuts
            $metaResponse = Http::withToken($token)->timeout(30)->get($this->baseUrl . "/files/{$fileId}", [
                'fields' => 'mimeType,size,name,shortcutDetails',
                'supportsAllDrives' => 'true'
            ]);
            
            if (!$metaResponse->successful()) {
                Log::error("Google Drive Meta Error for {$fileId}: " . $metaResponse->body());
                return null;
            }
            
            $mimeType = $metaResponse->json('mimeType');
            $size = $metaResponse->json('size', 0);
            $name = $metaResponse->json('name', 'unknown');

            // Handle Shortcuts
            if ($mimeType === 'application/vnd.google-apps.shortcut') {
                $targetId = $metaResponse->json('shortcutDetails.targetId');
                Log::info("Resolving shortcut '{$name}' -> target: {$targetId}");
                if ($targetId) {
                    return $this->getFileContent($user, $targetId, $teamId);
                }
                return null;
            }

            Log::info("Attempting to fetch Google Drive file: {$name} ({$mimeType}, {$size} bytes)");

            // Don't try to read huge files (max 10MB for Vision, but let's be safe at 5MB)
            if ($size > 5 * 1024 * 1024 && !str_starts_with($mimeType, 'application/vnd.google-apps.')) {
                Log::warning("File {$name} is too large for AI processing: {$size} bytes");
                return ['content' => "[Archivo demasiado grande para procesar directamente]", 'mimeType' => 'text/plain'];
            }
            
            // 2. Decide how to fetch the content
            if (str_starts_with($mimeType, 'application/vnd.google-apps.')) {
                // For Google Docs/Sheets, we MUST use /export
                $exportMimeType = 'text/plain';
                if (str_contains($mimeType, 'spreadsheet')) $exportMimeType = 'text/csv';
                
                $response = Http::withToken($token)->timeout(120)->get($this->baseUrl . "/files/{$fileId}/export", [
                    'mimeType' => $exportMimeType,
                    'supportsAllDrives' => 'true'
                ]);
                $mimeType = $exportMimeType; // The result is now plain text or CSV
            } else {
                // For other files, get the media content
                $response = Http::withToken($token)->timeout(120)->get($this->baseUrl . "/files/{$fileId}", [
                    'alt' => 'media',
                    'supportsAllDrives' => 'true'
                ]);
            }

            if ($response->successful()) {
                Log::info("Successfully fetched content for {$name}");
                return [
                    'content' => $response->body(),
                    'mimeType' => $mimeType
                ];
            }

            Log::error("Google Drive Content Fetch Error for {$name} ({$fileId}): " . $response->status() . " - " . $response->body());
        } catch (\Exception $e) {
            Log::error('Error fetching Google Drive file content (' . $fileId . '): ' . $e->getMessage());
        }

        return null;
    }
}
