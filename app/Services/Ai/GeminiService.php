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

    public function generateText(string $prompt): string
    {
        $finalPrompt = $prompt;

        if ($this->taskContext) {
            $contextInfo = "CONTEXTO DE LA TAREA ACTUAL:\n";
            $contextInfo .= "- Título: {$this->taskContext->title}\n";
            $contextInfo .= "- Descripción: " . ($this->taskContext->description ?: 'N/A') . "\n";
            $contextInfo .= "- Equipo: " . ($this->taskContext->team->name ?? 'N/A') . "\n";
            $contextInfo .= "- Estado: " . ($this->taskContext->status ?? 'pending') . "\n";
            $contextInfo .= "- Fecha prevista: " . ($this->taskContext->scheduled_date?->format('Y-m-d') ?? 'N/A') . "\n";
            $contextInfo .= "\nREGLAS CRÍTICAS DE RESPUESTA:\n";
            $contextInfo .= "1. Puedes saludar y explicar cosas brevemente.\n";
            $contextInfo .= "2. Todo contenido que sea una propuesta de descripción, resumen, pasos o comentario PARA LA TAREA, DEBE ir encerrado entre etiquetas [PAYLOAD] y [/PAYLOAD].\n";
            $contextInfo .= "3. NO incluyas introducciones ni despedidas dentro de las etiquetas [PAYLOAD]. Ese bloque debe estar 'limpio de polvo y paja'.\n";
            $contextInfo .= "\nINSTRUCCIÓN DEL USUARIO: {$prompt}\n";
            
            $finalPrompt = "Eres Ax.ia, el asistente de Sientia MTX. Usa siempre [PAYLOAD] para el contenido técnico inyectable.\n\n" . $contextInfo;
        }

        return $this->callGemini($this->targetModel, $finalPrompt);
    }

    public function analyzeEnergyLevel(array $recentData): int
    {
        $prompt = "Actúa como un psicólogo y analista de productividad. Aquí tienes los datos recientes de un usuario:\n";
        $prompt .= json_encode($recentData) . "\n";
        $prompt .= "Basado en estos datos, dame un nivel estimado de energía del 1 al 5 como único número de respuesta. Nada de texto extra, solo el número entero.";

        $response = $this->callGemini($this->targetModel, $prompt);
        $level = (int) trim($response);

        // Fallback or boundary check
        if ($level < 1) return 1;
        if ($level > 5) return 5;

        return $level;
    }

    public function simplifyText(string $complexText): string
    {
        $prompt = "Simplifica y traduce la siguiente tarea o texto complejo en pasos sencillos, como si fueras a explicárselo a un niño de 10 años:\n\n" . $complexText;
        return $this->callGemini($this->targetModel, $prompt);
    }

    /**
     * Helper to make the HTTP request to the Gemini API.
     */
    protected function callGemini(string $model, string $prompt, bool $isFallback = false): string
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
                        'parts' => [
                            ['text' => $prompt]
                        ]
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

            // Si falla con 404 y no estamos en fallback, intentamos con el modelo Pro 1.0 que es el más compatible
            if ($status === 404 && !$isFallback && $model !== 'gemini-pro') {
                return $this->callGemini('gemini-pro', $prompt, true);
            }

            if ($status === 403) {
                return "Error 403: La API Key no parece válida o no tiene permisos para usar Gemini. Asegúrate de que la 'Generative Language API' esté activada en tu consola de Google.";
            }

            if ($status === 404) {
                return "Error 404: El modelo especificado ('{$model}') no se encuentra en esta cuenta de Google. Prueba a cambiar el modelo en tu perfil de Integraciones.";
            }

            return "Lo siento, ha ocurrido un error al procesar tu solicitud ({$errorMsg}).";

        } catch (\Exception $e) {
            Log::error('Gemini API Exception: ' . $e->getMessage());
            return "Lo siento, el servicio de inteligencia artificial no está disponible en este momento.";
        }
    }
}
