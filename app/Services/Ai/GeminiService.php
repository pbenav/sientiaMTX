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
    protected string $apiKey = '';
    protected string $targetModel = 'gemini-1.5-flash-latest';
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct()
    {
        // By default, use the app config key if available
        $this->apiKey = config('services.gemini.key') ?? env('GEMINI_API_KEY') ?? '';
    }

    public function forUser(User $user, ?int $teamId = null): self
    {
        $this->user = $user;
        $this->taskContext = null; // Clear context on brand new session
        $this->attachmentContext = null;
        $this->threadContext = null;
        $this->messageContext = null;
        
        // Contexto específico o global
        $pref = $user->aiPreferences()->where('team_id', $teamId)->first() 
                ?? $user->aiPreferences()->whereNull('team_id')->first();

        if ($pref) {
            if (!empty($pref->api_key)) {
                $this->apiKey = (string) $pref->api_key;
            }
            if (!empty($pref->ai_model)) {
                $this->targetModel = $pref->ai_model;
            }
        }

        return $this;
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

        if ($contextInfo) {
            $contextInfo .= "\nREGLAS CRÍTICAS DE RESPUESTA:\n";
            $contextInfo .= "1. Puedes saludar y explicar cosas brevemente.\n";
            $contextInfo .= "2. Todo contenido que sea una propuesta de descripción, resumen, pasos o comentario PARA LA TAREA, DEBE ir encerrado entre etiquetas [PAYLOAD] y [/PAYLOAD].\n";
            $contextInfo .= "3. NO incluyas introducciones ni despedidas dentro de las etiquetas [PAYLOAD].\n";
            $contextInfo .= "4. Si se te ha proporcionado un archivo (texto o binario), úsalo como fuente principal.\n";
            $contextInfo .= "\nINSTRUCCIÓN DEL USUARIO: {$prompt}\n";
            
            $systemPrompt = "Eres Ax.ia, el asistente de Sientia MTX. Usa siempre [PAYLOAD] para el contenido técnico inyectable.\n\n" . $contextInfo;
            array_unshift($parts, ['text' => $systemPrompt]);
        } else {
            $parts[] = ['text' => $prompt];
        }

        if ($this->threadContext) {
            // Forum logic would also need multi-part if supporting attachments there, 
            // but for now let's keep it simple or unify.
            // (Forum logic implementation simplified for brevity, similar to above)
        }

        return $this->callGemini($this->targetModel, $parts);
    }

    protected function isMultimodalMime(string $mime): bool
    {
        $multimodalTypes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/heic',
            'image/heif',
        ];

        foreach ($multimodalTypes as $type) {
            if (str_starts_with($mime, str_replace('*', '', $type))) {
                return true;
            }
        }

        return false;
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
     * Helper to make the HTTP request to the Gemini API.
     * $parts should be an array of Gemini parts (text, inline_data, etc.)
     */
    protected function callGemini(string $model, array $parts, bool $isFallback = false): string
    {
        if (empty($this->apiKey)) {
            Log::error('Gemini API key is missing.');
            return "Error: No se ha configurado la clave API de Gemini. Configúrala en tu perfil.";
        }

        $url = "{$this->baseUrl}/{$model}:generateContent?key={$this->apiKey}";

        try {
            $response = Http::post($url, [
                'contents' => [
                    [
                        'parts' => $parts
                    ]
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'La IA no devolvió ninguna respuesta.';
            }

            $status = $response->status();
            $errorBody = $response->json('error') ?? [];
            $errorMsg = $errorBody['message'] ?? 'Error desconocido';

            Log::error("Gemini API Error ({$status}) on model {$model}: " . json_encode($errorBody));

            if ($status === 404 && !$isFallback && $model !== 'gemini-pro') {
                return $this->callGemini('gemini-pro', $parts, true);
            }

            return "Lo siento, ha ocurrido un error al procesar tu solicitud ({$errorMsg}).";

        } catch (\Exception $e) {
            Log::error('Gemini API Exception: ' . $e->getMessage());
            return "Lo siento, el servicio de inteligencia artificial no está disponible en este momento.";
        }
    }
}
