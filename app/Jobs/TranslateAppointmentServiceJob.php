<?php

namespace App\Jobs;

use App\Models\AppointmentService;
use App\Services\Ai\GeminiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TranslateAppointmentServiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public AppointmentService $service)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(GeminiService $gemini): void
    {
        // Forzamos a que el modelo cargue sus datos sin accessors
        $name = $this->service->getRawOriginal('name');
        $description = $this->service->getRawOriginal('description');
        
        if (empty($name)) {
            return;
        }

        $locales = ['en' => 'Inglés', 'fr' => 'Francés', 'ro' => 'Rumano', 'ar' => 'Árabe', 'wo' => 'Wolof'];

        $prompt = "Traduce exactamente los siguientes textos a los idiomas especificados en formato JSON estricto.\n";
        $prompt .= "Textos originales en Español:\n";
        $prompt .= "Nombre: " . $name . "\n";
        if ($description) {
            $prompt .= "Descripción: " . $description . "\n";
        }

        $customFields = $this->service->getRawOriginal('custom_fields');
        $customFieldsArray = is_string($customFields) ? json_decode($customFields, true) : $customFields;
        if (!empty($customFieldsArray)) {
            $prompt .= "Campos personalizados (traduce solo el 'name' y asócialo al 'id'):\n";
            foreach ($customFieldsArray as $cf) {
                if (isset($cf['id']) && isset($cf['name'])) {
                    $prompt .= "- ID: {$cf['id']} | Nombre: {$cf['name']}\n";
                }
            }
        }

        $prompt .= "\nIdiomas destino:\n";
        foreach ($locales as $code => $lang) {
            $prompt .= "- {$code} ({$lang})\n";
        }

        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'translations' => [
                    'type' => 'OBJECT',
                    'description' => 'Mapa de idiomas (claves: en, fr, ro, ar, wo)',
                    'additionalProperties' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'name' => ['type' => 'STRING'],
                            'description' => ['type' => 'STRING'],
                            'custom_fields' => [
                                'type' => 'OBJECT',
                                'description' => 'Mapa de ID del campo a nombre traducido (ej: "field_123": "ID Card")',
                                'additionalProperties' => [
                                    'type' => 'STRING'
                                ]
                            ]
                        ],
                        'required' => ['name']
                    ]
                ]
            ],
            'required' => ['translations']
        ];

        // Usamos el servicio del creador del servicio o el global
        $gemini->forUser($this->service->user);
        
        try {
            // Pasamos el schema JSON a callGemini (GeminiService.php linea 384)
            // Espera, callGemini en GeminiService es protected.
            // Wait, I should use generateText but how to pass JSON schema? generateText does not take schema.
            // Oh, wait, in GeminiService, generateText returns the string. It includes:
            // $systemInstruction .= "REGLA DE FORMATO: Casi todas tus respuestas deben ser un JSON envuelto en [PAYLOAD]."
            // Let's just ask it to return a clean JSON.
            $response = $gemini->generateText("Por favor, devuelve UNICAMENTE un JSON (sin etiquetas [PAYLOAD] ni markdown extra) con las traducciones: \n" . $prompt);
            
            if (str_contains($response, '[PAYLOAD]')) {
                $response = str_replace(['[PAYLOAD]', '[/PAYLOAD]'], '', $response);
            }
            if (str_starts_with(trim($response), '```json')) {
                $response = preg_replace('/```json\s*(.*?)\s*```/s', '$1', trim($response));
            }

            $data = json_decode(trim($response), true);
            if (json_last_error() === JSON_ERROR_NONE && isset($data['translations'])) {
                // Preservar traducciones previas si existieran
                $currentTranslations = $this->service->translations ?? [];
                
                foreach ($locales as $code => $lang) {
                    if (isset($data['translations'][$code])) {
                        $currentTranslations[$code] = $data['translations'][$code];
                    }
                }
                
                // Actualizar de forma silenciosa para no disparar de nuevo el evento
                $this->service->translations = $currentTranslations;
                $this->service->saveQuietly();
            }
        } catch (\Throwable $e) {
            \Log::error("Ax.ia: Fallo al traducir AppointmentService " . $this->service->id . ": " . $e->getMessage());
        }
    }
}
