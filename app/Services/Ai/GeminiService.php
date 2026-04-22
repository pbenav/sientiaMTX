<?php

namespace App\Services\Ai;

use App\Contracts\AiAssistantInterface;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService implements AiAssistantInterface
{
    protected ?User $user = null;
    protected ?\App\Models\Task $taskContext = null;
    protected ?\App\Models\TaskAttachment $attachmentContext = null;
    protected ?\App\Models\ForumThread $threadContext = null;
    protected ?\App\Models\ForumMessage $messageContext = null;
    protected ?\Illuminate\Http\UploadedFile $directFile = null;
    protected ?string $cachedFileContent = null;
    protected ?string $cachedFileMime = null;
    protected ?string $cachedFileName = null;
    protected string $apiKey = '';
    protected string $targetModel = 'gemini-1.5-flash';
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';
    protected array $userStats = [];
    protected $tasksContext = [];

    public function __construct()
    {
        // By default, use the app config key if available
        $this->apiKey = config('services.gemini.key') ?? env('GEMINI_API_KEY') ?? '';
    }

    public function setTemporaryKey(string $key): self
    {
        $this->apiKey = $key;
        return $this;
    }

    public function forUser(User $user, ?int $teamId = null): self
    {
        $this->user = $user;
        $this->taskContext = null; // Clear context on brand new session
        $this->attachmentContext = null;
        $this->threadContext = null;
        $this->messageContext = null;
        $this->directFile = null;
        $this->tasksContext = [];
        
        // Contexto específico o global
        // Contexto específico o global
        $pref = $user->aiPreferences()->where('team_id', $teamId)->first() 
                ?? $user->aiPreferences()->whereNull('team_id')->first();

        $keySource = "ARCHIVO .ENV / CONFIG (POR DEFECTO)";
        if ($pref) {
            if (!empty($pref->api_key)) {
                $this->apiKey = (string) $pref->api_key;
                $keySource = "BASE DE DATOS (Preferencia ID: {$pref->id}" . ($pref->team_id ? " - Equipo {$pref->team_id}" : " - Global") . ")";
            } else {
                Log::warning("La clave de API en Base de Datos para el usuario {$user->id} está vacía o falló la desencriptación.");
            }

            if (!empty($pref->ai_model)) {
                $this->targetModel = $pref->ai_model;
            }
        }
        
        $maskedKey = $this->apiKey ? (substr($this->apiKey, 0, 4) . '....' . substr($this->apiKey, -4)) : 'VACÍA';
        Log::debug("Ax.ia: Usando clave desde {$keySource} [Key: {$maskedKey}]");
        Log::debug("Ax.ia: Usando modelo {$this->targetModel} para el contexto " . ($teamId ? "Equipo $teamId" : "Global"));

        $this->userStats = $user->getAiContextStats();

        return $this;
    }

    public function getTargetModel(): string
    {
        return $this->targetModel;
    }

    public function withTaskContext(\App\Models\Task $task): self
    {
        $this->taskContext = $task;
        return $this;
    }

    public function withAttachmentContext(\App\Models\TaskAttachment $attachment): self
    {
        $this->attachmentContext = $attachment;
        return $this;
    }

    public function withForumContext(\App\Models\ForumThread $thread, ?\App\Models\ForumMessage $message = null): self
    {
        $this->threadContext = $thread;
        $this->messageContext = $message;
        return $this;
    }

    public function withTasksContext($tasks): self
    {
        $this->tasksContext = $tasks;
        return $this;
    }

    public function withFile(\Illuminate\Http\UploadedFile $file): self
    {
        $this->directFile = $file;
        try {
            $this->cachedFileContent = file_get_contents($file->getPathname());
            $mime = $file->getMimeType() ?: $file->getClientMimeType() ?: 'application/octet-stream';
            $this->cachedFileName = $file->getClientOriginalName();
            
            // OPTIMIZACIÓN: Si es imagen y pesa mucho, la redimensionamos para ahorrar ancho de banda y tiempo de proceso
            if (str_starts_with($mime, 'image/') && $file->getSize() > 1024 * 1024) {
               $this->cachedFileContent = $this->resizeImageIfNeeded($file->getPathname(), $mime);
            }

            // FIX: Some browsers (especially on tablets/mobile) record audio as video/webm.
            // Gemini fails with "0 frames found" if we send video/webm without video tracks.
            // We coerce it to audio/webm if the extension or common patterns suggest it's a voice note.
            if (str_contains($mime, 'video/webm') || (empty($mime) && str_ends_with(strtolower($this->cachedFileName), '.webm'))) {
                Log::info("Ax.ia: Coerciendo MIME de video/webm a audio/webm para compatibilidad con Gemini (Voz)");
                $mime = 'audio/webm';
            }

            $this->cachedFileMime = $mime;
            Log::debug("Ax.ia: Archivo cacheado con éxito ({$this->cachedFileName}, {$this->cachedFileMime})");
        } catch (\Exception $e) {
            Log::error("Ax.ia: No se pudo cachear el archivo: " . $e->getMessage());
        }
        return $this;
    }

    public function generateText(string $prompt): string
    {
        $contextInfo = "";
        $parts = [];

        if ($this->taskContext) {
            $contextInfo .= "CONTEXTO DE LA TAREA ACTUAL:\n";
            $contextInfo .= "- Título: {$this->taskContext->title}\n";
            $contextInfo .= "- Descripción: " . ($this->taskContext->description ?: 'N/A') . "\n";
            $contextInfo .= "- Equipo: " . ($this->taskContext->team->name ?? 'N/A') . "\n";
            $contextInfo .= "- Estado: " . ($this->taskContext->status ?? 'pending') . "\n";
            $contextInfo .= "- Fecha prevista: " . ($this->taskContext->scheduled_date?->format('Y-m-d') ?? 'N/A') . "\n";
        }

        if (!empty($this->tasksContext)) {
            $contextInfo .= "\nLISTADO DE TAREAS ACTIVAS/PENDIENTES:\n";
            foreach ($this->tasksContext as $t) {
                $contextInfo .= "- [ID: {$t->id}] \"{$t->title}\" (Carga: {$t->cognitive_load}/5, Estado: {$t->status})\n";
            }
            $contextInfo .= "NOTA: El impacto en energía depende de la Carga Cognitiva. A mayor carga, más desgaste al trabajar, pero más recompensa al completar.\n";
        }

        if ($this->cachedFileContent) {
            $mime = $this->cachedFileMime;
            
            if ($this->isMultimodalMime($mime)) {
                try {
                    $parts[] = [
                        'inline_data' => [
                            'mime_type' => $mime,
                            'data' => base64_encode($this->cachedFileContent)
                        ]
                    ];
                    $contextInfo .= "\nHE ADJUNTO UN ARCHIVO DIRECTO:\n";
                    $contextInfo .= "- Nombre: {$this->cachedFileName}\n";
                    $contextInfo .= "- Tipo: {$mime}\n";
                    $contextInfo .= "- Instrucción: Analiza este archivo directamente.\n";
                } catch (\Exception $e) {
                    Log::error("Ax.ia: Error procesando contenido cacheado: " . $e->getMessage());
                    $contextInfo .= "- Error: No se pudo procesar el archivo directo.\n";
                }
            } else {
                Log::warning("Ax.ia: El tipo MIME {$mime} no es reconocido como multimodal. Intentando tratar como texto.");
                $contextInfo .= "\nARCHIVO ADJUNTO (Texto): " . mb_substr($this->cachedFileContent, 0, 5000) . "\n";
            }
        }

        if ($this->attachmentContext) {
            $contextInfo .= "\nCONTEXTO DEL ARCHIVO ADJUNTO:\n";
            $contextInfo .= "- Nombre: {$this->attachmentContext->file_name}\n";
            $contextInfo .= "- Tipo: {$this->attachmentContext->mime_type}\n";
            $contextInfo .= "- Tamaño: " . number_format($this->attachmentContext->file_size / 1024, 2) . " KB\n";
            
            $mime = $this->attachmentContext->mime_type;
            $canSendAsMedia = $this->isMultimodalMime($mime);

            if ($this->attachmentContext->storage_provider === 'google') {
                $contextInfo .= "- Fuente: Google Drive\n";
                $contextInfo .= "- Enlace: {$this->attachmentContext->web_view_link}\n";
                
                try {
                    $driveService = app(\App\Services\Google\GoogleDriveService::class);
                    $driveResult = $driveService->getFileContent($this->user, $this->attachmentContext->provider_file_id, $this->attachmentContext->task->team_id ?? null);
                    
                    if ($driveResult) {
                        $driveContent = $driveResult['content'];
                        $realMime = $driveResult['mimeType'];
                        
                        // Recalculate if it's multimodal based on the REAL mime type (essential for shortcuts!)
                        if ($this->isMultimodalMime($realMime)) {
                            $parts[] = [
                                'inline_data' => [
                                    'mime_type' => $realMime,
                                    'data' => base64_encode($driveContent)
                                ]
                            ];
                            $contextInfo .= "- Instrucción: He adjuntado este archivo binario para que lo analices directamente (visión/multimodal).\n";
                        } else {
                            $contextInfo .= "- Contenido (extraído): " . mb_substr($driveContent, 0, 5000) . "\n";
                        }
                    } else {
                        $contextInfo .= "- Contenido: No se pudo extraer el contenido.\n";
                    }
                } catch (\Exception $e) {
                    $contextInfo .= "- Contenido: Error al conectar con Google Drive.\n";
                }
            } else {
                $contextInfo .= "- Fuente: Almacenamiento Local\n";
                try {
                    $content = \Illuminate\Support\Facades\Storage::disk('public')->get($this->attachmentContext->file_path);
                    if ($content) {
                        if ($canSendAsMedia) {
                            $parts[] = [
                                'inline_data' => [
                                    'mime_type' => $mime,
                                    'data' => base64_encode($content)
                                ]
                            ];
                            $contextInfo .= "- Instrucción: He adjuntado este archivo binario para que lo analices directamente (visión/multimodal).\n";
                        } else {
                            $contextInfo .= "- Contenido (fragmento): " . mb_substr($content, 0, 3000) . "\n";
                        }
                    }
                } catch (\Exception $e) {
                    $contextInfo .= "- Contenido: No se pudo leer el archivo local.\n";
                }
            }
        }

        $systemInstruction = "";

        // Añadimos el prompt del usuario
        $parts[] = ['text' => $prompt];

        return $this->callGemini($this->targetModel, $parts, false, $systemInstruction);
    }

    protected function isMultimodalMime(string $mime): bool
    {
        // Permissive check for audio/video/images/pdf
        $mime = strtolower($mime);
        
        return str_starts_with($mime, 'image/') || 
               str_starts_with($mime, 'audio/') || 
               str_starts_with($mime, 'video/') || 
               $mime === 'application/pdf';
    }

    public function analyzeEnergyLevel(array $recentData): int
    {
        $prompt = "Actúa como un psicólogo y analista de productividad. Aquí tienes los datos recientes de un usuario:\n";
        $prompt .= json_encode($recentData) . "\n";
        $prompt .= "Basado en estos datos, dame un nivel estimado de energía del 1 al 5 como único número de respuesta. Nada de texto extra, solo el número entero.";

        $response = $this->callGemini($this->targetModel, [['text' => $prompt]]);
        $level = (int) trim($response);

        if ($level < 1) return 1;
        if ($level > 5) return 5;

        return $level;
    }

    public function simplifyText(string $complexText): string
    {
        $prompt = "Simplifica y traduce la siguiente tarea o texto complejo en pasos sencillos:\n\n" . $complexText;
        return $this->callGemini($this->targetModel, [['text' => $prompt]]);
    }

    /**
     * Lists all models available for the current API key.
     */
    public function listAvailableModels(): array
    {
        if (empty($this->apiKey)) {
            return [];
        }

        $url = "{$this->baseUrl}?key={$this->apiKey}";

        try {
            $response = Http::timeout(10)->get($url);
            if ($response->successful()) {
                $data = $response->json();
                $models = [];
                $ids = [];
                foreach ($data['models'] ?? [] as $m) {
                    // Filter only models that support content generation
                    if (in_array('generateContent', $m['supportedGenerationMethods'] ?? [])) {
                        $id = str_replace('models/', '', $m['name']);
                        $ids[] = $id;
                        $models[] = [
                            'id' => $id,
                            'display_name' => $m['displayName'] ?? $m['name'],
                            'description' => $m['description'] ?? ''
                        ];
                    }
                }
                Log::info("Ax.ia: Modelos disponibles detectados: " . implode(', ', $ids));
                return $models;
            }
        } catch (\Exception $e) {
            Log::error("Error listing Gemini models: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Resizes an image if it's too large to improve AI processing speed.
     */
    protected function resizeImageIfNeeded(string $path, string $mime): string
    {
        try {
            if (!extension_loaded('gd')) return file_get_contents($path);

            $img = null;
            if ($mime === 'image/jpeg' || $mime === 'image/jpg') $img = imagecreatefromjpeg($path);
            elseif ($mime === 'image/png') $img = imagecreatefrompng($path);
            elseif ($mime === 'image/webp') $img = imagecreatefromwebp($path);

            if (!$img) return file_get_contents($path);

            $width = imagesx($img);
            $height = imagesy($img);
            $maxDim = 1600;

            if ($width > $maxDim || $height > $maxDim) {
                $ratio = $maxDim / max($width, $height);
                $newWidth = (int)($width * $ratio);
                $newHeight = (int)($height * $ratio);
                $newImg = imagecreatetruecolor($newWidth, $newHeight);
                
                if ($mime === 'image/png') {
                    imagealphablending($newImg, false);
                    imagesavealpha($newImg, true);
                }
                
                imagecopyresampled($newImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                
                ob_start();
                if ($mime === 'image/png') imagepng($newImg, null, 7);
                elseif ($mime === 'image/webp') imagewebp($newImg, null, 75);
                else imagejpeg($newImg, null, 80);
                
                $data = ob_get_clean();
                imagedestroy($img);
                imagedestroy($newImg);
                
                Log::info("Ax.ia: Imagen redimensionada de {$width}x{$height} a {$newWidth}x{$newHeight} para eficiencia.");
                return $data;
            }
        } catch (\Exception $e) {
            Log::warning("Ax.ia: Falló redimensión de imagen: " . $e->getMessage());
        }
        return file_get_contents($path);
    }

    /**
     * Helper to make the HTTP request to the Gemini API.
     * $parts should be an array of Gemini parts (text, inline_data, etc.)
     */
    protected function callGemini(string $model, array $parts, bool $isFallback = false, ?string $systemInstruction = null): string
    {
        if (empty($this->apiKey)) {
            Log::error('Gemini API key is missing.');
            return "Error: No se ha configurado la clave API de Gemini. Configúrala en tu perfil.";
        }

        // CLEANUP: Ensure model name is just the ID
        $model = str_replace('models/', '', $model);

        $url = "{$this->baseUrl}/{$model}:generateContent?key={$this->apiKey}";

        $payload = [
            'contents' => [
                [
                    'parts' => $parts
                ]
            ]
        ];

        if ($systemInstruction) {
            $payload['system_instruction'] = [
                'parts' => [
                    ['text' => $systemInstruction]
                ]
            ];
        }

        try {
            // Aumentamos el timeout para archivos pesados pero mantenemos el stream razonable
            $response = Http::timeout(180)->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'La IA no devolvió ninguna respuesta.';
            }

            $status = $response->status();
            $errorBody = $response->json('error') ?? [];
            $errorMsg = $errorBody['message'] ?? 'Error desconocido';

            // Si es un error de Cuota (429) o Prohibido (403) con mención a cuota, intentamos el modelo LITE
            $isQuotaError = ($status === 429 || (isset($errorBody['status']) && $errorBody['status'] === 'RESOURCE_EXHAUSTED'));
            
            if ($isQuotaError && !$isFallback) {
                if (!str_contains($model, 'lite')) {
                    Log::info("Cuota agotada para {$model}. Intentando fallback automático a gemini-2.0-flash-lite...");
                    return $this->callGemini('gemini-2.0-flash-lite', $parts, true);
                } else {
                    // Si el Lite también falla, intentamos el 1.5 en v1beta como último recurso desesperado
                    Log::warning("Cuota agotada incluso en LITE. Intentando v1beta/gemini-1.5-flash...");
                    $emergencyUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$this->apiKey}";
                    try {
                        $fallbackResponse = Http::timeout(30)->post($emergencyUrl, ['contents' => [['parts' => $parts]]]);
                        if ($fallbackResponse->successful()) {
                             $data = $fallbackResponse->json();
                             return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Respuesta obtenida con el último recurso.';
                        }
                    } catch (\Exception $e) { /* ignore and fail later */ }
                }
            }

            if ($status === 404 && !$isFallback && $model !== 'gemini-1.5-flash') {
                return $this->callGemini('gemini-1.5-flash', $parts, true);
            }

            return "Lo siento, ha ocurrido un error al procesar tu solicitud ({$errorMsg}).";

        } catch (\Exception $e) {
            Log::error('Gemini API Exception: ' . $e->getMessage());
            return "Lo siento, el servicio de inteligencia artificial no está disponible en este momento.";
        }
    }
}
