<?php

namespace App\Services\Ai;

use App\Contracts\AiAssistantInterface;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService implements AiAssistantInterface
{
    protected ?User $user = null;
    protected \App\Models\Activity|\App\Models\Task|null $taskContext = null;
    protected \App\Models\ActivityAttachment|\App\Models\TaskAttachment|null $attachmentContext = null;
    protected ?\App\Models\ForumThread $threadContext = null;
    protected ?\App\Models\ForumMessage $messageContext = null;
    protected ?\Illuminate\Http\UploadedFile $directFile = null;
    protected ?string $cachedFileContent = null;
    protected ?string $cachedFileMime = null;
    protected ?string $cachedFileName = null;
    protected string $apiKey = '';
    protected string $targetModel = 'gemini-1.5-flash'; 
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1';
    protected array $userStats = [];
    protected $tasksContext = [];
    protected ?int $teamId = null;
    protected ?AiSearchService $searchService = null;
    protected array $messagesHistory = [];

    /** Lista de modelos conocidos y fiables, en orden de preferencia */
    protected const FALLBACK_MODELS = [
        'gemini-2.5-flash',
        'gemini-2.0-flash',
        'gemini-1.5-flash',
        'gemini-3.5-flash',
        'gemini-flash-latest',
        'gemini-2.5-pro',
        'gemini-1.5-pro',
        'gemini-pro-latest',
    ];

    public function __construct()
    {
        // By default, use the app config key if available
        $this->apiKey = config('services.gemini.key') ?? '';
        $this->searchService = new AiSearchService();
    }

    public function setTemporaryKey(string $key): self
    {
        $this->apiKey = $key;
        return $this;
    }

    public function clearWorkingModelCache(): self
    {
        if (!empty($this->apiKey)) {
            $cacheKey = 'ai_working_model_' . md5($this->apiKey);
            session()->forget($cacheKey);
            Log::info("Ax.ia: Limpiado caché de modelo funcional en sesión.");
        }
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

        // 1. Obtener todas las preferencias del usuario ordenadas por relevancia
        $preferences = $user->aiPreferences()
            ->orderByRaw("CASE 
                WHEN team_id = ? THEN 0 
                WHEN team_id IS NULL THEN 1 
                ELSE 2 END", [$teamId])
            ->get();

        $keySource = "ARCHIVO .ENV / CONFIG (POR DEFECTO)";
        $modelSource = "ESTÁTICO (POR DEFECTO)";
        $this->targetModel = 'gemini-1.5-flash'; // Modelo por defecto

        // 2. Determinar el modelo más específico configurado
        if ($teamId) {
            $teamPref = $preferences->where('team_id', $teamId)->first();
            if ($teamPref && !empty($teamPref->ai_model)) {
                $this->targetModel = $teamPref->ai_model;
                $modelSource = "BASE DE DATOS (Equipo {$teamId})";
            }
        }

        if ($modelSource === "ESTÁTICO (POR DEFECTO)") {
            $globalPref = $preferences->where('team_id', null)->first();
            if ($globalPref && !empty($globalPref->ai_model)) {
                $this->targetModel = $globalPref->ai_model;
                $modelSource = "BASE DE DATOS (Global)";
            }
        }

        // 3. Determinar la Clave API más específica configurada
        if ($teamId) {
            $teamPref = $preferences->where('team_id', $teamId)->first();
            if ($teamPref && !empty($teamPref->api_key)) {
                $this->apiKey = (string) $teamPref->api_key;
                $keySource = "BASE DE DATOS (Preferencia ID: {$teamPref->id} - Equipo {$teamId})";
            }
        }

        if (empty($this->apiKey)) {
            $globalPref = $preferences->where('team_id', null)->first();
            if ($globalPref && !empty($globalPref->api_key)) {
                $this->apiKey = (string) $globalPref->api_key;
                $keySource = "BASE DE DATOS (Preferencia ID: {$globalPref->id} - Global)";
            }
        }

        if (empty($this->apiKey)) {
            $this->apiKey = config('services.gemini.key') ?? '';
        }

        $maskedKey = $this->apiKey ? (substr($this->apiKey, 0, 4) . '....' . substr($this->apiKey, -4)) : 'VACÍA';
        Log::debug("Ax.ia: Usando clave desde {$keySource} [Key: {$maskedKey}]");
        Log::debug("Ax.ia: Usando modelo {$this->targetModel} [Fuente: {$modelSource}] para el contexto " . ($teamId ? "Equipo $teamId" : "Global"));

        // Leer el modelo configurado por el usuario
        $configuredModel = $this->targetModel;

        // Comprobar si hay un modelo funcional cacheado en sesión para este usuario + api key
        $cacheKey = 'ai_working_model_' . md5($this->apiKey);
        $cachedWorkingModel = session($cacheKey);
        if ($cachedWorkingModel && $cachedWorkingModel !== $configuredModel) {
            Log::debug("Ax.ia: Usando modelo funcional cacheado en sesión: {$cachedWorkingModel} (configurado: {$configuredModel})");
            $this->targetModel = $cachedWorkingModel;
        }

        $this->userStats = $user->getAiContextStats();

        return $this;
    }

    public function getTargetModel(): string
    {
        return $this->targetModel;
    }

    public function withTaskContext(\App\Models\Activity|\App\Models\Task $task): self
    {
        $this->taskContext = $task;
        return $this;
    }

    public function withAttachmentContext(\App\Models\ActivityAttachment|\App\Models\TaskAttachment $attachment): self
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

    public function withHistory(\Illuminate\Support\Collection $messages): self
    {
        $this->messagesHistory = [];
        foreach ($messages as $msg) {
            $role = $msg->role === 'ai' ? 'model' : 'user';
            $this->messagesHistory[] = [
                'role' => $role,
                'parts' => [['text' => $msg->content]]
            ];
        }
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
                
                if (isset($this->taskContext->metadata['chapters']) && is_array($this->taskContext->metadata['chapters'])) {
                    $contextInfo .= "- Capítulos del Documento:\n";
                    foreach ($this->taskContext->metadata['chapters'] as $idx => $chap) {
                        $cTitle = $chap['title'] ?? 'Sin título';
                        $cContent = mb_substr($chap['content'] ?? '', 0, 1500); // truncate if too long
                        $contextInfo .= "  Capítulo " . ($idx+1) . " - {$cTitle}:\n  {$cContent}\n";
                    }
                }
            } else {
                $contextInfo .= "- Descripción: [CONTENIDO PRIVADO - NO DISPONIBLE]\n";
                $contextInfo .= "- Nota: Tienes acceso a la discusión pero el contenido de la tarea es privado para su creador/asignados.\n";
            }
            
            $contextInfo .= "- Equipo: " . ($this->taskContext->team->name ?? 'N/A') . "\n";
            $statusVal = $this->taskContext->status_value ?? (is_array($this->taskContext->status) ? ($this->taskContext->status['value'] ?? 'pending') : ($this->taskContext->status ?? 'pending'));
            $contextInfo .= "- Estado: " . $statusVal . "\n";
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
                $tStatus = $t->status_value ?? (is_array($t->status) ? ($t->status['value'] ?? 'pending') : ($t->status ?? 'pending'));
                $contextInfo .= "- [ID: {$t->id}] \"{$t->title}\" (Carga: {$t->cognitive_load}/5, Estado: {$tStatus})\n";
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
            
            // Proveer siempre una URL pública permanente para micrositios
            if ($this->attachmentContext->storage_provider !== 'google') {
                $this->attachmentContext->ensurePublicCopy();
            }

            $embedUrl = $this->attachmentContext->getPublicEmbedUrl();
            if ($embedUrl) {
                $contextInfo .= "- URL para incrustar/enlazar (OBLIGATORIA, copiar exacta): {$embedUrl}\n";
                $contextInfo .= "- ID del adjunto: {$this->attachmentContext->id}\n";
            }
            
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
        $systemInstruction .= "REGLA DE FORMATO: Casi todas tus respuestas deben ser un JSON envuelto en [PAYLOAD] (salvo charla trivial). Mantén un tono profesional, entusiasta y premium.\n\n";
        
        $systemInstruction .= "INTENCIONES DE PAYLOAD ADMITIDAS:\n";
        $systemInstruction .= "1. 'simple_text': Para responder preguntas generales, análisis, resúmenes, explicaciones y traducciones. Estructura: {\"intent\": \"simple_text\", \"content\": \"Contenido en Markdown\"}.\n";
        $systemInstruction .= "2. 'search_results': Para mostrar resultados de búsqueda.\n";
        $systemInstruction .= "3. 'full_task': Para CREAR TAREAS nuevas (cuando el usuario lo pida, sugiera o cuando la tarea requiera ser registrada en el sistema). Estructura: {\"intent\": \"full_task\", \"task_data\": {\"title\": \"Título de la tarea\", \"description\": \"Resumen/Descripción breve\", \"observations\": \"Desarrollo paso a paso u observaciones detalladas\"}}.\n";
        $systemInstruction .= "4. 'generate_survey': Para diseñar o generar ENCUESTAS cuando el usuario lo solicite. Estructura JSON: {\"intent\": \"generate_survey\", \"survey_data\": [{\"title\": \"Pregunta 1\", \"type\": \"single_choice|multiple_choice|rating|text\", \"options\": [\"A\", \"B\"], \"is_required\": true}]}.\n";
        $systemInstruction .= $this->getMicrositeDesignInstructions();
        
        $systemInstruction .= "ANÁLISIS DE DOCUMENTOS Y ARCHIVOS:\n";
        $systemInstruction .= "- Si se te proporciona un archivo adjunto o directo (multimodal o texto), PRIORIZA su lectura exhaustiva. Extrae conclusiones clave, listas ordenadas, resúmenes organizados o responde con total precisión técnica sobre el contenido del documento usando la intención 'simple_text'.\n";
        $systemInstruction .= "- Si vas a generar un micrositio y necesitas mostrar, incrustar o enlazar el archivo adjunto, utiliza SIEMPRE la 'URL para incrustar/enlazar (OBLIGATORIA, copiar exacta)' del contexto, sin modificarla (ej. en <iframe src=\"...\"> o <a href=\"...\">). PROHIBIDO inventar rutas como /files/, /storage/ o usar solo el nombre del archivo.\n\n";
        
        $systemInstruction .= "CREACIÓN DE TAREAS:\n";
        $systemInstruction .= "- Si el usuario te pide crear, programar, generar, registrar o planificar una tarea, debes utilizar la intención 'full_task' para que el sistema la cree automáticamente. No te limites por estar dentro de una tarea de edición; si se solicita una nueva tarea, créala.\n\n";
        
        $systemInstruction .= "SOBRE BÚSQUEDAS: Si no hay resultados, explica por qué y ofrece ayuda para refinar la búsqueda. No respondas con un payload vacío.\n";
        $systemInstruction .= "IMPORTANTE: Cierra siempre tus bloques con [/PAYLOAD].\n";
        
        $fullPrompt = $contextInfo . "\nINSTRUCCIÓN DEL USUARIO: " . $prompt;

        if ($this->taskContext) {
            $systemInstruction .= "CONTEXTO: El usuario está editando la tarea '{$this->taskContext->title}'. Usa la intención 'simple_text' para tus análisis habituales sobre esta tarea, pero si explícitamente te pide generar o crear otra tarea nueva, utiliza la intención 'full_task'.\n\n";
        }

        if ($contextInfo) {
            $systemInstruction .= "DATOS DE REFERENCIA (SILENCIOSOS):\n" . $contextInfo . "\n";
        }

        $systemInstruction .= "ENERGÍA DEL USUARIO: " . json_encode($this->userStats, JSON_UNESCAPED_UNICODE) . "\n";
        $userName = $this->user?->name ?? 'usuario';
        
        $systemInstruction .= "Instrucción final para {$userName}: Responde de inmediato a la petición. Si su energía es baja, sé empático y añade sutilmente la etiqueta secreta [RECHARGE] al final de tu contenido.";

        if (preg_match('/micrositio|microsite|genera(?:r)?\s+(?:un\s+)?(?:sitio|p[aá]gina|web)/iu', $prompt)) {
            $systemInstruction .= "\n\n⚡ REFUERZO ACTIVO — DISEÑO DE MICROSITIO: El usuario pide un micrositio. Responde con intent 'generate_microsite'. Aplica el design system ms-* (hero, secciones, tarjetas, botones). Si hay PDF adjunto, usa OBLIGATORIAMENTE la plantilla ms-pdf-viewer del system prompt. El resultado debe verse como una landing premium de agencia, no como un documento HTML básico.\n";
        }

        // Añadimos el prompt del usuario
        $parts[] = ['text' => $prompt];

        return $this->callGemini($this->targetModel, $parts, false, $systemInstruction);
    }

    /**
     * Directrices de diseño premium para micrositios generados por Ax.ia.
     */
    protected function getMicrositeDesignInstructions(): string
    {
        return <<<'MS'

4. 'generate_microsite': EXCLUSIVO para diseño de micrositios web premium de nivel agencia.
   Estructura JSON: {"intent": "generate_microsite", "html": "<fragmento HTML>", "css": "<CSS completo>"}
   ⚠️ REGLA CRÍTICA DE JSON: Debes devolver un JSON ESTRICTAMENTE VÁLIDO. 
   - NUNCA uses comillas triples (''') ni backticks (```) para delimitar los valores de 'html' o 'css'.
   - Usa EXCLUSIVAMENTE comillas dobles (") para las claves y valores.
   - Escapa obligatoriamente los saltos de línea (\n) y las comillas dobles internas (\") en el código.

═══ ENTORNO DE RENDERIZADO (CRÍTICO) ═══
- HTML insertado en <main> de Laravel. PROHIBIDO <html>, <head>, <body>.
- CSS en campo separado. PROHIBIDO etiqueta <style> dentro del JSON.
- PROHIBIDO clases Tailwind (bg-*, text-*, flex, p-4…) salvo petición EXPRESA del usuario.
  Si pide Tailwind: primer elemento del HTML → <script src="https://cdn.tailwindcss.com"></script>
- Usa CSS vanilla + clases del design system ms-* (ya precargado en la plataforma).
- Envuelve TODO en: <div class="ms-root">...</div>

═══ DESIGN SYSTEM ms-* (ÚSALO — NO REINVENTES DESDE CERO) ═══
Clases disponibles en la plataforma (puedes extenderlas en tu CSS):
  .ms-container  → contenedor centrado max-width
  .ms-hero       → cabecera impactante con gradiente (texto SIEMPRE blanco/claro)
  .ms-section     → bloque de contenido con padding
  .ms-section--alt → sección con fondo surface
  .ms-card        → tarjeta elevada
  .ms-btn .ms-btn--primary / .ms-btn--outline / .ms-btn--ghost
  .ms-pdf-viewer  → visor PDF con toolbar y pantalla completa (OBLIGATORIO para PDFs)

ESTRUCTURA HTML MÍNIMA RECOMENDADA:
<div class="ms-root">
  <header class="ms-hero"><div class="ms-container"><h1>Título</h1><p>Subtítulo</p></div></header>
  <section class="ms-section"><div class="ms-container">...</div></section>
  <section class="ms-section ms-section--alt"><div class="ms-container">...</div></section>
</div>

═══ PALETA Y ESTILO (OBLIGATORIO) ═══
- Extrae colores y tono del prompt del usuario (corporativo, elegante, vibrante, etc.).
- Si no indica colores: paleta índigo + rosa accent sobre fondo claro, o modo oscuro slate + violeta.
- Sobrescribe variables en .ms-root { --ms-primary, --ms-bg, --ms-surface, --ms-text, --ms-accent, … }.
- NUNCA dejes secciones sin color de fondo y texto explícitos en CSS.

═══ CONTRASTE Y ACCESIBILIDAD (VERIFICA SIEMPRE) ═══
- PROHIBIDO texto blanco sobre fondos claros (#fff, #f8fafc, amarillo pálido).
- PROHIBIDO texto oscuro sobre fondos oscuros sin contraste ≥ 4.5:1.
- .ms-hero SIEMPRE: fondo oscuro/degradado + color: #ffffff en h1, p y botones ghost.
- Enlaces y botones claramente distinguibles.

═══ CALIDAD PREMIUM (NIVEL AGENCIA — OBLIGATORIO) ═══
- Tipografía: @import Google Fonts al inicio del CSS (Playfair Display + DM Sans, o similar serif + sans).
- Hero con titular grande (clamp), subtítulo legible, CTA opcional (.ms-btn--primary).
- Secciones alternadas (fondo / surface), tarjetas con sombra, espaciado generoso (3–4rem).
- Micro-interacciones: hover en botones y tarjetas (transform, shadow).
- Responsive @media (max-width: 768px): tipografía fluida, padding reducido, PDF 60vh mínimo.
- Evita páginas planas de solo texto: usa jerarquía visual, iconos Unicode (✦ ◆ →), separadores.

═══ PDF / DOCUMENTOS ADJUNTOS (PLANTILLA OBLIGATORIA) ═══
Si hay URL de PDF en el contexto, incrusta EXACTAMENTE con esta estructura (copia la URL tal cual):

<div class="ms-pdf-viewer">
  <div class="ms-pdf-toolbar">
    <span class="ms-pdf-title">Nombre del documento</span>
    <div class="ms-pdf-actions">
      <button type="button" class="ms-btn ms-btn--ghost" data-ms-fullscreen>⛶ Pantalla completa</button>
      <a href="URL_EXACTA_DEL_CONTEXTO" target="_blank" rel="noopener" class="ms-btn ms-btn--ghost">↗ Abrir en pestaña</a>
    </div>
  </div>
  <iframe class="ms-pdf-frame" src="URL_EXACTA_DEL_CONTEXTO" title="Nombre del documento" allowfullscreen loading="lazy"></iframe>
</div>

- PROHIBIDO iframe PDF suelto sin ms-pdf-viewer (sin pantalla completa ni toolbar).
- Altura mínima del iframe: 75vh (clase ms-pdf-frame ya lo define).
- Imágenes: <img> con border-radius y sombra dentro de .ms-card.

═══ AUTO-REVISIÓN FINAL (antes de emitir JSON) ═══
1. ¿Parece una landing premium, no un informe Word convertido a HTML?
2. ¿Todas las clases del HTML están en CSS o en el design system ms-*?
3. ¿Contraste correcto en hero, secciones y tarjetas?
4. ¿PDF con ms-pdf-viewer + data-ms-fullscreen + allowfullscreen?
5. ¿Colores del prompt reflejados en --ms-* variables?

MS;
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

    public function generateStructuredData(string $prompt, array $schema, string $systemInstruction = ''): array
    {
        $response = $this->callGemini($this->targetModel, [['text' => $prompt]], false, $systemInstruction, false, true, $schema);
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return $data;
        }
        return [];
    }

    public function generateMotivationalPhrase(int $taskCount, string $userName, string $locale): string
    {
        $system = "Eres Ax.ia. Tu única función ahora es motivar al usuario con una frase corta.";
        $prompt = "El usuario {$userName} (idioma {$locale}) tiene {$taskCount} tareas pendientes hoy. Escribe la frase motivacional de máximo 15 palabras.";

        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'phrase' => [
                    'type' => 'STRING',
                    'description' => 'La frase motivacional final.'
                ]
            ],
            'required' => ['phrase']
        ];

        // Usamos el modelo, sin herramientas y pasamos el schema JSON
        $response = $this->callGemini($this->targetModel, [['text' => $prompt]], false, $system, false, true, $schema);
        
        $data = json_decode($response, true);
        
        if (json_last_error() === JSON_ERROR_NONE && isset($data['phrase'])) {
            return trim($data['phrase']);
        }

        // Fallback robusto en caso de que la API falle
        return "¡Vamos {$userName}! Hoy es un gran día para avanzar en tus tareas.";
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
    protected function callGemini(string $model, array $parts, bool $isFallback = false, ?string $systemInstruction = null, bool $isToolCall = false, bool $forceNoTools = false, ?array $jsonSchema = null): string
    {
        if (empty($this->apiKey)) {
            Log::error('Gemini API key is missing.');
            return "Error: No se ha configurado la clave de Ax.ia. Por favor, ve a tu Perfil -> Integraciones y configura tu Clave API de Gemini.";
        }

        // Determinar si el modelo soporta herramientas
        $supportsTools = (str_contains($model, '1.5') || str_contains($model, '2.0') || str_contains($model, 'flash')) && !$forceNoTools;

        // Determinar la URL. v1 para estables, v1beta para experimentales/preview/tools.
        $cleanId = str_replace('models/', '', $model);
        $finalModelPath = "models/{$cleanId}";
        
        // FIX: Si usamos tools, es mucho más seguro usar v1beta ya que v1 a veces rechaza el campo 'tools' según el modelo/región
        $v = (str_contains($cleanId, 'preview') || str_contains($cleanId, 'deep-research') || $isToolCall || $supportsTools) ? 'v1beta' : 'v1';
        $url = "https://generativelanguage.googleapis.com/{$v}/{$finalModelPath}:generateContent?key={$this->apiKey}";

        if (!$isToolCall) {
            $existingHistory = $this->messagesHistory;
            $this->messagesHistory = []; // Reset context for new top-level call if needed
            
            if ($systemInstruction) {
                $this->messagesHistory[] = ['role' => 'user', 'parts' => [['text' => "NUEVA SESIÓN: " . $systemInstruction]]];
                $this->messagesHistory[] = ['role' => 'model', 'parts' => [['text' => "Entendido. Aplicaré estas directrices a partir de ahora."]]];
            }
            
            foreach ($existingHistory as $hMsg) {
                $this->messagesHistory[] = $hMsg;
            }
            
            $this->messagesHistory[] = ['role' => 'user', 'parts' => $parts];
        }

        $payload = [
            'contents' => $this->messagesHistory,
        ];
        
        if ($supportsTools) {
            $payload['tools'] = $this->getToolsDefinition();
        }

        if ($jsonSchema) {
            $payload['generationConfig'] = [
                'responseMimeType' => 'application/json',
                'responseSchema' => $jsonSchema
            ];
        }

        try {
            $response = Http::timeout(25)->post($url, $payload);
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

            // --- Lógica de Resiliencia y Auto-Curación ---

            // ERROR ESPECIAL: Si el modelo no reconoce "tools", reintentamos inmediatamente SIN tools
            if ((str_contains($errorMsg, 'tools') || str_contains($errorMsg, 'Unknown name')) && !$forceNoTools) {
                Log::warning("Ax.ia: El modelo {$model} parece no soportar herramientas o el endpoint las rechaza. Reintentando sin ellas...");
                return $this->callGemini($model, $parts, $isFallback, $systemInstruction, $isToolCall, true);
            }
            
            // Si el error indica que este modelo no sirve (por cuota, carga, error de servidor o esquema), buscamos otro.
            $shouldRetryWithAlternative = ($status >= 400 && $status !== 401);
            
            if ($shouldRetryWithAlternative && !$isFallback) {
                return $this->handleFallback($model, $parts, $systemInstruction, $forceNoTools, "Error HTTP {$status}: {$errorMsg}");
            }

            // Si llegamos aquí tras el barrido o es un 401, realmente nada funciona
            Log::error("Ax.ia: Fallo crítico absoluto tras intentar con el modelo {$model}. Último error: {$errorMsg}");
            return "Lo siento, ha ocurrido un error crítico. He intentado usar el modelo pero no ha respondido correctamente en este momento.\n\nDetalle: `{$errorMsg}`";

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Timeout o error de red
            Log::warning("Ax.ia: Timeout/conexión con {$model}: " . $e->getMessage());
            if (!$isFallback) {
                return $this->handleFallback($model, $parts, $systemInstruction, $forceNoTools, "Timeout de red: " . $e->getMessage());
            }
            return "Lo siento, el servicio de IA no ha respondido a tiempo. El modelo '{$model}' puede no existir o estar caído. Ax.ia ha intentado alternativas pero ninguna ha respondido.";
        
        } catch (\Exception $e) {
            Log::error('Gemini API Exception: ' . $e->getMessage());
            if (!$isFallback) {
                return $this->handleFallback($model, $parts, $systemInstruction, $forceNoTools, "Excepción inesperada: " . $e->getMessage());
            }
            return "Lo siento, el servicio de inteligencia artificial no está disponible en este momento por un error de conexión.";
        }
    }

    /**
     * Intenta recuperar la petición usando modelos alternativos en caso de fallo crítico.
     */
    protected function handleFallback(string $failedModel, array $parts, ?string $systemInstruction, bool $forceNoTools, string $errorMsg): string
    {
        $cleanFailedModel = preg_replace('/^models\//', '', $failedModel);
        Log::warning("Ax.ia: Activando protocolo de resiliencia tras fallo en '{$failedModel}'. Detalle: {$errorMsg}");

        $candidatesFromApi = [];
        try {
            $available = $this->listAvailableModels();
            $candidatesFromApi = array_map(fn($m) => $m['id'], $available);
        } catch (\Exception $ignored) { /* Ignorar fallos al listar modelos */ }

        $fullCandidateList = array_unique(array_merge(self::FALLBACK_MODELS, $candidatesFromApi));

        foreach ($fullCandidateList as $candidate) {
            if ($candidate === $cleanFailedModel) {
                continue;
            }

            Log::warning("Ax.ia: Probando modelo alternativo de resiliencia: {$candidate}...");
            $res = $this->callGemini($candidate, $parts, true, $systemInstruction, false, $forceNoTools);
            
            // Si la respuesta no contiene indicación de fallo crítico
            if (!str_contains($res, 'Lo siento, ha ocurrido un error') && !str_contains($res, 'no está disponible') && !str_contains($res, 'no ha respondido a tiempo')) {
                // ✅ ¡Este modelo funciona! Guardarlo en sesión para las próximas peticiones
                $cacheKey = 'ai_working_model_' . md5($this->apiKey);
                session([$cacheKey => $candidate]);
                $this->targetModel = $candidate;
                Log::info("Ax.ia: Modelo de resiliencia funcional cacheado en sesión: {$candidate}");
                return $res;
            }
        }

        Log::error("Ax.ia: Fallo crítico absoluto. Ninguno de los modelos alternativos respondió.");
        return "Lo siento, el servicio de IA no ha respondido a tiempo o ha fallado. El modelo '{$failedModel}' puede no existir, no estar disponible o estar caído. Ax.ia ha intentado recuperarse con modelos alternativos pero ninguno ha respondido.";
    }
}
