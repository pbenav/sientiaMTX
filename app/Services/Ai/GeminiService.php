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
    protected string $targetModel = ''; 
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1';
    protected array $userStats = [];
    protected $tasksContext = [];
    protected ?int $teamId = null;
    protected ?AiSearchService $searchService = null;
    protected array $messagesHistory = [];

    public function __construct()
    {
        // By default, use the app config key if available
        $this->apiKey = config('services.gemini.key') ?? env('GEMINI_API_KEY') ?? '';
        $this->searchService = new AiSearchService();
    }

    public function setTemporaryKey(string $key): self
    {
        $this->apiKey = $key;
        return $this;
    }

    public function forUser(User $user, ?int $teamId = null): self
    {
        $this->user = $user;
        $this->teamId = $teamId;
        $this->taskContext = null; // Clear context on brand new session
        $this->attachmentContext = null;
        $this->threadContext = null;
        $this->messageContext = null;
        // Intentar obtener la mejor clave disponible
        $preferences = $user->aiPreferences()
            ->orderByRaw("CASE 
                WHEN team_id = ? THEN 0 
                WHEN team_id IS NULL THEN 1 
                ELSE 2 END", [$teamId])
            ->get();

        $keySource = "ARCHIVO .ENV / CONFIG (POR DEFECTO)";
        $modelSource = "ESTÁTICO (POR DEFECTO)";

        // 1. Obtener primero el modelo global si no hay otro
        $globalPref = $preferences->where('team_id', null)->first();
        if ($globalPref && $globalPref->ai_model) {
            $this->targetModel = $globalPref->ai_model;
            $modelSource = "BASE DE DATOS (Global)";
        }

        // 2. Buscar clave (empezando por la más específica)
        foreach ($preferences as $pref) {
            $key = $pref->api_key; 
            
            if (!empty($key)) {
                $this->apiKey = (string) $key;
                if ($pref->ai_model) {
                    $this->targetModel = $pref->ai_model;
                    $modelSource = "BASE DE DATOS (" . ($pref->team_id ? "Equipo {$pref->team_id}" : "Global") . ")";
                }
                $keySource = "BASE DE DATOS (Preferencia ID: {$pref->id}" . ($pref->team_id ? " - Equipo {$pref->team_id}" : " - Global") . ")";
                break;
            }
        }

        $maskedKey = $this->apiKey ? (substr($this->apiKey, 0, 4) . '....' . substr($this->apiKey, -4)) : 'VACÍA';
        Log::debug("Ax.ia: Usando clave desde {$keySource} [Key: {$maskedKey}]");
        Log::debug("Ax.ia: Usando modelo {$this->targetModel} [Fuente: {$modelSource}] para el contexto " . ($teamId ? "Equipo $teamId" : "Global"));

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
            $canViewPrivate = $this->user ? $this->user->can('view', $this->taskContext) : false;
            
            $contextInfo .= "CONTEXTO DE LA TAREA ACTUAL:\n";
            $contextInfo .= "- Título: {$this->taskContext->title}\n";
            
            if ($this->taskContext->visibility === 'public' || $canViewPrivate) {
                $contextInfo .= "- Descripción: " . ($this->taskContext->description ?: 'N/A') . "\n";
                $contextInfo .= "- Observaciones: " . ($this->taskContext->observations ?: 'N/A') . "\n";
            } else {
                $contextInfo .= "- Descripción: [CONTENIDO PRIVADO - NO DISPONIBLE]\n";
                $contextInfo .= "- Nota: Tienes acceso a la discusión pero el contenido de la tarea es privado para su creador/asignados.\n";
            }
            
            $contextInfo .= "- Equipo: " . ($this->taskContext->team->name ?? 'N/A') . "\n";
            $contextInfo .= "- Estado: " . ($this->taskContext->status ?? 'pending') . "\n";
            $contextInfo .= "- Fecha prevista: " . ($this->taskContext->scheduled_date?->format('Y-m-d') ?? 'N/A') . "\n";
        }

        if ($this->threadContext) {
            $contextInfo .= "\nCONTEXTO DEL HILO DEL FORO:\n";
            $contextInfo .= "- Título del Hilo: {$this->threadContext->title}\n";
            
            $messages = $this->threadContext->messages()
                ->with('user')
                ->oldest()
                ->limit(20) // Últimos 20 mensajes para no saturar el contexto
                ->get();

            $contextInfo .= "- Mensajes recientes:\n";
            foreach ($messages as $msg) {
                $contextInfo .= "  [{$msg->user->name}]: {$msg->content}\n";
            }

            if ($this->messageContext) {
                $contextInfo .= "\nMENSAJE ESPECÍFICO SELECCIONADO:\n";
                $contextInfo .= "De {$this->messageContext->user->name}: {$this->messageContext->content}\n";
            }
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

        $systemInstruction = "Eres Ax.ia, la inteligencia avanzada de Sientia MTX. Tu objetivo es ser extremadamente útil, directo y profesional.\n";
        $systemInstruction .= "BASE_URL: " . config('app.url') . "\n";
        $systemInstruction .= "TEAM_CONTEXT_ID: " . ($this->teamId ?? 'GLOBAL') . "\n";
        $systemInstruction .= "MODELO ACTIVO: {$this->targetModel}.\n";
        $systemInstruction .= "REGLA DE FORMATO: Casi todas tus respuestas deben ser un JSON envuelto en [PAYLOAD]. Si saludas o es charla trivial, usa texto plano.\n";
        $systemInstruction .= "REGLA DE ENLACES: NUNCA, bajo ningún concepto, uses 'undefined' o 'null' en una URL. Si un recurso (tarea, hilo, mensaje) no tiene un TEAM_ID asociado en los datos de referencia, utiliza SIEMPRE el TEAM_CONTEXT_ID: " . ($this->teamId ?? '0') . ".\n";
        $systemInstruction .= "REGLA DE FORMATO: Casi todas tus respuestas deben ser un JSON envuelto en [PAYLOAD]. Mantén un tono profesional, entusiasta y premium. Evita frases genéricas como 'Tu referencia se ha actualizado'. Sé específico.\n\n";
        
        $systemInstruction .= "INTENCIONES DE PAYLOAD:\n";
        $systemInstruction .= "1. 'simple_text': Resúmenes, explicaciones, correcciones. Usa 'content' para el Markdown.\n";
        $systemInstruction .= "2. 'search_results': ¡IMPORTANTE! Úsalo siempre que consultes herramientas de búsqueda. Asegúrate de que cada elemento del resultado incluya su 'team_id'.\n";
        $systemInstruction .= "3. 'full_task': Solo para crear tareas nuevas. Requiere objeto 'task_data'.\n\n";
        
        $systemInstruction .= "SOBRE BÚSQUEDAS: Si no hay resultados, explica por qué y ofrece ayuda para refinar la búsqueda. No respondas con un payload vacío.\n";
        $systemInstruction .= "IMPORTANTE: Cierra siempre tus bloques con [/PAYLOAD].\n";
        
        $fullPrompt = $contextInfo . "\nINSTRUCCIÓN DEL USUARIO: " . $prompt;

        if ($this->taskContext) {
            $systemInstruction .= "CONTEXTO: El usuario está editando la tarea '{$this->taskContext->title}'. Usa SIEMPRE intent: 'simple_text' a menos que exija crear múltiples tareas nuevas.\n\n";
        }

        if ($contextInfo) {
            $systemInstruction .= "DATOS DE REFERENCIA (SILENCIOSOS):\n" . $contextInfo . "\n";
        }

        $systemInstruction .= "ENERGÍA DEL USUARIO: " . json_encode($this->userStats, JSON_UNESCAPED_UNICODE) . "\n";
        $userName = $this->user?->name ?? 'usuario';
        
        $systemInstruction .= "Instrucción final para {$userName}: Responde de inmediato a la petición. Si su energía es baja, sé empático y añade sutilmente la etiqueta secreta [RECHARGE] al final de tu contenido.";

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

        // El listado de modelos suele ser más robusto en v1beta todavía
        $url = "https://generativelanguage.googleapis.com/v1beta/models?key={$this->apiKey}";
        Log::info("Ax.ia: Solicitando lista de modelos a Google desde v1beta...");

        try {
            $response = Http::timeout(10)->get($url);
            
            if (!$response->successful()) {
                Log::error("Ax.ia: Error al listar modelos (Status: {$response->status()}): " . $response->body());
                return [];
            }

            $data = $response->json();
            $models = [];
            $ids = [];

            if (isset($data['models'])) {
                foreach ($data['models'] as $m) {
                    // Solo modelos que soporten generación de texto
                    if (isset($m['supportedGenerationMethods']) && !in_array('generateContent', $m['supportedGenerationMethods'])) {
                        continue;
                    }

                    // Filtramos modelos que sabemos que NO funcionan con la API estándar de generación
                    // o que requieren arquitecturas especiales (Deep Research, Robotics, etc.)
                    $isSpecialModel = str_contains($m['name'], 'deep-research') 
                                   || str_contains($m['name'], 'robotics')
                                   || str_contains($m['name'], 'computer-use')
                                   || str_contains($m['name'], 'lyria');
                    
                    if ($isSpecialModel) {
                        continue;
                    }

                    $id = str_replace('models/', '', $m['name']);
                    $ids[] = $id;
                    $models[] = [
                        'id' => $id,
                        'display_name' => $m['displayName'] ?? $id,
                        'description' => $m['description'] ?? ''
                    ];
                }
            }
            
            Log::info("Ax.ia: Detectados " . count($models) . " modelos: " . implode(', ', $ids));
            return $models;
        } catch (\Exception $e) {
            Log::error("Ax.ia: Excepción listando modelos: " . $e->getMessage());
            return [];
        }
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
     * Define las herramientas (functions) que la IA puede llamar.
     */
    protected function getToolsDefinition(): array
    {
        return [[
            'function_declarations' => [
                [
                    'name' => 'search_tasks',
                    'description' => 'Busca tareas en el equipo actual por título, descripción o notas.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => [
                                'type' => 'string',
                                'description' => 'Término o palabra clave a buscar'
                            ]
                        ],
                        'required' => ['query']
                    ]
                ],
                [
                    'name' => 'search_forum',
                    'description' => 'Busca hilos de conversación o mensajes específicos en el foro del equipo.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => [
                                'type' => 'string',
                                'description' => 'Término a buscar en las discusiones'
                            ]
                        ],
                        'required' => ['query']
                    ]
                ]
            ]
        ]];
    }

    /**
     * Helper to make the HTTP request to the Gemini API with Tool Support.
     */
    protected function callGemini(string $model, array $parts, bool $isFallback = false, ?string $systemInstruction = null, bool $isToolCall = false): string
    {
        if (empty($this->apiKey)) {
            Log::error('Gemini API key is missing.');
            return "Error: No se ha configurado la clave de Ax.ia. Por favor, ve a tu Perfil -> Integraciones y configura tu Clave API de Gemini.";
        }

        if (empty($model)) {
            $model = 'gemini-1.5-flash';
        }

        // Determinar la URL. v1 para estables, v1beta para experimentales/preview.
        $cleanId = str_replace('models/', '', $model);
        $finalModelPath = "models/{$cleanId}";
        
        $v = (str_contains($cleanId, 'preview') || str_contains($cleanId, 'deep-research') || $isToolCall) ? 'v1beta' : 'v1';
        $url = "https://generativelanguage.googleapis.com/{$v}/{$finalModelPath}:generateContent?key={$this->apiKey}";

        if (!$isToolCall) {
            $this->messagesHistory = []; // Reset context for new top-level call if needed
            
            if ($systemInstruction) {
                $this->messagesHistory[] = ['role' => 'user', 'parts' => [['text' => "NUEVA SESIÓN: " . $systemInstruction]]];
                $this->messagesHistory[] = ['role' => 'model', 'parts' => [['text' => "Entendido. Aplicaré estas directrices a partir de ahora."]]];
            }
            $this->messagesHistory[] = ['role' => 'user', 'parts' => $parts];
        }

        $payload = [
            'contents' => $this->messagesHistory,
            'tools' => $this->getToolsDefinition()
        ];

        try {
            $response = Http::timeout(60)->post($url, $payload);
            $status = $response->status();
            $errorBody = $response->json('error') ?? [];
            $errorMsg = $errorBody['message'] ?? 'Error desconocido';

            if ($response->successful()) {
                $candidate = $response->json('candidates.0');
                $part = $candidate['content']['parts'][0] ?? null;

                // 1. Manejar LLAMADA A FUNCIÓN (Tool Call)
                if (isset($part['functionCall'])) {
                    $fnName = $part['functionCall']['name'];
                    $args = $part['functionCall']['args'] ?? [];
                    Log::info("Ax.ia: Herramienta solicitada: {$fnName}", $args);

                    $result = null;
                    if ($fnName === 'search_tasks') {
                        $result = $this->searchService->searchTasks($this->teamId, $args['query'] ?? '');
                    } elseif ($fnName === 'search_forum') {
                        $result = $this->searchService->searchForum($this->teamId, $args['query'] ?? '');
                    }

                    // Meter la llamada del modelo en el historial
                    $this->messagesHistory[] = ['role' => 'model', 'parts' => [$part]];
                    
                    // Meter el resultado de la función en el historial
                    $this->messagesHistory[] = [
                        'role' => 'function', 
                        'parts' => [[
                            'functionResponse' => [
                                'name' => $fnName,
                                'response' => ['name' => $fnName, 'content' => $result]
                            ]
                        ]]
                    ];

                    // Hacer llamada recursiva con el contexto actualizado
                    return $this->callGemini($model, $parts, $isFallback, null, true);
                }

                // 2. Respuesta de Texto normal
                return $part['text'] ?? 'No se recibió contenido de la IA.';
            }

            // --- Lógica de Resiliencia Total y Auto-Curación ---
            
            // Si el error indica que este modelo no sirve (por cuota, carga o esquema), buscamos otro.
            $shouldRetryWithAlternative = ($status === 429 || $status === 503 || $status === 400 || $status === 404);
            
            if ($shouldRetryWithAlternative && !$isFallback) {
                $available = $this->listAvailableModels();
                
                // 1. Primero probamos los candidatos "VIPS" por ser los más estables
                $vips = ['gemini-2.0-flash', 'gemini-flash-latest', 'gemini-1.5-pro', 'gemini-1.5-flash', 'gemini-pro-latest'];
                
                // 2. Si fallan, añadiremos el resto de modelos disponibles como "reserva"
                $allOtherModels = array_map(fn($m) => $m['id'], $available);
                $fullCandidateList = array_unique(array_merge($vips, $allOtherModels));

                foreach ($fullCandidateList as $candidate) {
                    if ($candidate === $cleanId) continue; // No repetir el que acaba de fallar
                    
                    // Verificar si el candidato está en su lista real (si no era VIP)
                    $isAvailable = false;
                    foreach ($available as $avail) {
                        if ($avail['id'] === $candidate) {
                            $isAvailable = true;
                            break;
                        }
                    }
                    
                    if ($isAvailable) {
                        Log::warning("Ax.ia: Resiliencia activada. Probando modelo alternativo: {$candidate}...");
                        $res = $this->callGemini($candidate, $parts, true, $systemInstruction);
                        
                        // Si el rescate funciona, devolvemos la respuesta inmediatamente y SIN notas
                        if (!str_contains($res, 'Lo siento, ha ocurrido un error')) {
                            return $res;
                        }
                    }
                }
            }

            // Si llegamos aquí tras el barrido, realmente nada funciona
            Log::error("Ax.ia: Fallo crítico absoluto tras intentar con todos los modelos. Último error: {$errorMsg}");
            return "Lo siento, ha ocurrido un error crítico. He intentado usar todos tus modelos disponibles pero ninguno ha respondido correctamente en este momento.\n\nDetalle: `{$errorMsg}`";

        } catch (\Exception $e) {
            Log::error('Gemini API Exception: ' . $e->getMessage());
            return "Lo siento, el servicio de inteligencia artificial no está disponible en este momento por un error de conexión.";
        }
    }
}
