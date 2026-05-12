<?php

namespace App\Http\Controllers;

use App\Models\TaskAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Firebase\JWT\JWT;
use Illuminate\Support\Str;

class OnlyOfficeController extends Controller
{
    /**
     * Display the document editor.
     */
    public function edit(TaskAttachment $attachment)
    {
        // Ensure the file exists locally
        if ($attachment->storage_provider === 'google') {
            return back()->with('error', 'La edición en OnlyOffice no soporta archivos en Google Drive.');
        }

        // Ensure the user can access it
        // For absolute security, check team membership
        $team = $attachment->getTeam();
        if (!$team || !$attachment->canBeAccessedBy(auth()->user(), $team)) {
            abort(403, 'No autorizado.');
        }

        // Define supported extensions and resolve document type
        $ext = strtolower(pathinfo($attachment->file_name, PATHINFO_EXTENSION));
        $docType = $this->getDocumentType($ext);

        if (!$docType) {
            return back()->with('error', 'Este tipo de archivo no es compatible con el editor online.');
        }

        $configUrl = config('onlyoffice.url');
        $apiUrl = rtrim($configUrl, '/') . '/web-apps/apps/api/documents/api.js';

        // Generate a persistent Key for tracking collaborative revisions
        // Using a combination of attachment ID and its updated_at makes sure 
        // if the file changes externally, a new session starts.
        $key = md5($attachment->id . '_' . $attachment->updated_at->timestamp);

        // OPTIMIZACIÓN PARA RED INTERNA:
        // Si configuramos una IP interna, forzamos que Laravel genere y FIRME las rutas con esa IP base.
        $internalAppUrl = config('onlyoffice.internal_app_url'); // Ej: http://192.168.10.151
        if (!empty($internalAppUrl)) {
            URL::forceRootUrl($internalAppUrl);
        }

        // Generar las rutas firmadas (incluye la IP interna en la firma si está activada)
        $downloadUrl = URL::temporarySignedRoute('onlyoffice.download', now()->addHours(12), ['attachment' => $attachment->id]);
        $callbackUrl = route('onlyoffice.callback', ['attachment' => $attachment->id]);

        // Restaurar inmediatamente la URL original para no romper nada más
        if (!empty($internalAppUrl)) {
            URL::forceRootUrl(config('app.url'));
        }

        $config = [
            'document' => [
                'fileType' => $ext,
                'key' => $key,
                'title' => $attachment->file_name,
                'url' => $downloadUrl,
                'permissions' => [
                    'comment' => true,
                    'download' => true,
                    'edit' => true,
                    'print' => true,
                    'review' => true,
                ],
            ],
            'documentType' => $docType,
            'editorConfig' => [
                'callbackUrl' => $callbackUrl,
                'lang' => 'es',
                'mode' => 'edit',
                'user' => [
                    'id' => (string)auth()->id(),
                    'name' => auth()->user()->name,
                ],
                'customization' => [
                    'autosave' => true,
                    'chat' => true,
                    'comments' => true,
                    'forcesave' => false, // set to true if you want explicit save button triggered sync
                    'logo' => [
                        'image' => asset('img/logo.png'), // optional
                        'url' => config('app.url'),
                    ],
                ]
            ],
        ];

        // Sign with JWT if Secret exists (RECOMMENDED)
        $secret = config('onlyoffice.secret');
        $token = null;
        if (!empty($secret)) {
            $token = JWT::encode($config, $secret, 'HS256');
            // In newer versions, the token MUST also be inside the object if explicitly passed
            $config['token'] = $token;
        }

        return view('onlyoffice.editor', compact('apiUrl', 'config', 'attachment', 'token'));
    }

    /**
     * Secured endpoint for OnlyOffice Server to download the raw file.
     */
    public function downloadFile(Request $request, TaskAttachment $attachment)
    {
        // VALIDACIÓN: Aceptar descarga desde la IP de OnlyOffice o con firma válida
        $onlyOfficeIp = parse_url(config('onlyoffice.internal_server_url', ''), PHP_URL_HOST);
        $clientIp = $request->ip();
        $hasValidSig = $request->hasValidSignature();

        \Log::info("[OnlyOffice-Debug] Intento de descarga detectado.", [
            'ip_cliente' => $clientIp,
            'onlyoffice_ip' => $onlyOfficeIp,
            'has_valid_signature' => $hasValidSig ? 'SI' : 'NO',
        ]);

        // Aceptar si: la firma es válida, O si viene de la IP de OnlyOffice
        $isAuthorized = $hasValidSig || ($onlyOfficeIp && $clientIp === $onlyOfficeIp);

        if (!$isAuthorized) {
            \Log::error("[OnlyOffice-Debug] Acceso denegado a la descarga.", [
                'ip' => $clientIp, 'onlyoffice_ip_esperada' => $onlyOfficeIp
            ]);
            abort(403, 'No autorizado.');
        }

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            \Log::error("[OnlyOffice-Debug] El archivo físico no existe en storage/public.", [
                'path' => $attachment->file_path
            ]);
            abort(404, 'Archivo no encontrado en disco.');
        }

        \Log::info("[OnlyOffice-Debug] Descarga validada y autorizada. Enviando archivo...");
        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }

    /**
     * Callback receiver for OnlyOffice saving cycles.
     */
    public function callback(Request $request, TaskAttachment $attachment)
    {
        // LOG DIAGNÓSTICO INICIAL:
        \Log::info("[OnlyOffice-Debug] Petición de CALLBACK recibida en el controlador.", [
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'method' => $request->method()
        ]);

        // Read request payload (Sent as application/json body)
        $body = $request->json()->all();

        // Check JWT Signature if configured (Crucial for security!)
        $secret = config('onlyoffice.secret');
        if (!empty($secret)) {
            // OnlyOffice envía el token en el Body 'token', en la cabecera configurada (Authorization) o en 'X-CDES-JWT'
            $token = $body['token'] ?? $request->header('Authorization') ?? $request->header('X-CDES-JWT');
            
            if (!$token) {
                Log::warning("[OnlyOffice] Invalid callback received: No token provided.");
                return response()->json(['error' => 1, 'message' => 'Authentication required']);
            }

            try {
                // Decode and strip "Bearer " if sent in header
                $jwtStr = Str::startsWith($token, 'Bearer ') ? substr($token, 7) : $token;
                $decoded = (array) JWT::decode($jwtStr, new \Firebase\JWT\Key($secret, 'HS256'));
                // The actual body might be nested in $decoded['payload'] depending on OnlyOffice settings
                // but standard behavior overrides body if correct.
            } catch (\Exception $e) {
                Log::error("[OnlyOffice] JWT Decoded fail: " . $e->getMessage());
                return response()->json(['error' => 1, 'message' => 'Invalid token signature']);
            }
        }

        // Check status
        // 2 = Ready for saving
        // 6 = Editing ended, being saved automatically
        $status = (int) ($body['status'] ?? 0);

        if ($status === 2 || $status === 6) {
            $downloadUri = $body['url'] ?? null;
            if (!$downloadUri) {
                Log::error("[OnlyOffice] Status 2 but no download URL received for Attachment {$attachment->id}");
                return response()->json(['error' => 1, 'message' => 'No download URL provided by editor']);
            }

            try {
                // OPTIMIZACIÓN PARA RED INTERNA:
                // Si Laravel también debe conectar internamente a OnlyOffice para guardar cambios
                $internalServerUrl = config('onlyoffice.internal_server_url'); // Ej: http://192.168.10.152
                if (!empty($internalServerUrl)) {
                    $publicServerUrl = rtrim(config('onlyoffice.url'), '/');
                    $downloadUri = str_replace($publicServerUrl, rtrim($internalServerUrl, '/'), $downloadUri);
                }

                // Download the modified file from OnlyOffice server temporary storage
                $newFileContent = file_get_contents($downloadUri);
                if ($newFileContent === false) {
                    throw new \Exception("Could not retrieve file from {$downloadUri}");
                }

                // Update existing file in our Storage
                Storage::disk('public')->put($attachment->file_path, $newFileContent);
                
                // Update metadata: file size
                $attachment->update([
                    'file_size' => strlen($newFileContent),
                    'updated_at' => now()
                ]);

                Log::info("[OnlyOffice] Attachment ID {$attachment->id} ({$attachment->file_name}) updated and saved successfully via callback.");
                
                // Trigger audit log if module exists
                \App\Models\AttachmentLog::create([
                    'attachment_id' => $attachment->id,
                    'user_id' => $body['users'][0] ?? null, // Could use current editor ID if passed
                    'action' => 'edited',
                    'details' => 'Edición completada mediante OnlyOffice.',
                ]);

            } catch (\Exception $e) {
                Log::error("[OnlyOffice] Error updating file for Attachment {$attachment->id}: " . $e->getMessage());
                return response()->json(['error' => 1, 'message' => 'Internal write failure']);
            }
        }

        // Always return {"error": 0} to tell OnlyOffice to keep calm.
        return response()->json(['error' => 0]);
    }

    private function getDocumentType($ext): ?string
    {
        $map = config('onlyoffice.extensions');
        if (in_array($ext, $map['word'])) return 'word';
        if (in_array($ext, $map['cell'])) return 'cell';
        if (in_array($ext, $map['slide'])) return 'slide';
        return null;
    }
}
