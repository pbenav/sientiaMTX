<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Team;
use App\Models\TaskAttachment;
use App\Services\Google\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class GoogleDriveController extends Controller
{
    protected $driveService;

    public function __construct(GoogleDriveService $driveService)
    {
        $this->driveService = $driveService;
    }

    /**
     * Upload an existing local attachment to Google Drive
     */
    public function uploadToDrive(Team $team, TaskAttachment $attachment)
    {
        $user = auth()->user();

        if (!$user->google_token) {
            return back()->with('error', 'Debes conectar tu cuenta de Google Drive primero.');
        }

        if ($attachment->storage_provider === 'google') {
            return back()->with('info', 'Este archivo ya está en Google Drive.');
        }

        // 1. Get local file path
        $localPath = Storage::disk('public')->path($attachment->file_path);
        
        if (!file_exists($localPath)) {
            Log::error('Local file not found for Drive migration: ' . $localPath);
            return back()->with('error', 'El archivo local no existe o no se puede encontrar.');
        }

        try {
            // 2. Get/Create Sientia Folder in Drive
            $folderId = $this->driveService->getOrCreateSientiaFolder($user);

            // 3. Upload to Drive
            $result = $this->driveService->uploadFileFromPath($user, $localPath, $attachment->file_name, $folderId);

            if ($result) {
                // 4. Update Attachment record
                $attachment->update([
                    'storage_provider' => 'google',
                    'provider_file_id' => $result['id'],
                    'web_view_link' => $result['webViewLink'],
                ]);

                // 5. Delete local file (User asked to "move")
                Storage::disk('public')->delete($attachment->file_path);

                return back()->with('success', 'Archivo movido a Google Drive correctamente.');
            }

            return back()->with('error', 'Hubo un error al subir el archivo a Google Drive.');

        } catch (\Exception $e) {
            Log::error('Drive Migration Error: ' . $e->getMessage());
            return back()->with('error', 'Error en la migración: ' . $e->getMessage());
        }
    }

    /**
     * Save AI Response to Google Drive
     */
    public function saveAiResponse(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $user = $request->user();
        if (!$user->google_token) {
            return response()->json(['success' => false, 'message' => 'No conectado a Google Drive']);
        }

        try {
            $folderId = $this->driveService->getOrCreateSientiaFolder($user);
            $filename = 'Respuesta Ax.ia - ' . date('Y-m-d H:i:s') . '.docx';
            
            $result = $this->driveService->createFileFromText($user, $filename, $request->content, $folderId, true);

            if ($result) {
                return response()->json([
                    'success' => true, 
                    'message' => 'Guardado en Google Drive!',
                    'link' => $result['webViewLink']
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Fallo al crear el archivo.']);

        } catch (\Exception $e) {
            Log::error('Save Response to Drive Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Redirect to Google OAuth for Drive access
     */
    public function redirect()
    {
        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => route('google.drive.callback'),
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/drive.file https://www.googleapis.com/auth/userinfo.email',
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);

        return redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    /**
     * Handle Google OAuth Callback
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return Redirect::route('profile.edit', ['tab' => 'integrations'])->with('error', 'Error al conectar con Google: ' . $request->error);
        }

        $code = $request->code;

        $response = Http::post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => route('google.drive.callback'),
            'grant_type' => 'authorization_code',
            'code' => $code,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            
            $request->user()->update([
                'google_token' => $data['access_token'],
                'google_refresh_token' => $data['refresh_token'] ?? $request->user()->google_refresh_token,
            ]);

            return Redirect::route('profile.edit', ['tab' => 'integrations'])->with('status', 'google-connected');
        }

        return Redirect::route('profile.edit', ['tab' => 'integrations'])->with('error', 'Fallo al obtener el token de Google.');
    }

    /**
     * Disconnect Google Drive
     */
    public function disconnect(Request $request)
    {
        $request->user()->update([
            'google_token' => null,
            'google_refresh_token' => null,
        ]);

        return Redirect::route('profile.edit', ['tab' => 'integrations'])->with('status', 'google-disconnected');
    }
}
