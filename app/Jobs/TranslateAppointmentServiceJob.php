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

        $locales = ['en' => 'Inglés', 'fr' => 'Francés', 'ro' => 'Rumano', 'ar' => 'Árabe Marroquí (Darija)', 'wo' => 'Wolof'];

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

        $languageSchema = [
            'type' => 'OBJECT',
            'properties' => [
                'name' => ['type' => 'STRING'],
                'description' => ['type' => 'STRING'],
                'custom_fields' => [
                    'type' => 'ARRAY',
                    'description' => 'Lista de campos personalizados traducidos.',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'id' => ['type' => 'STRING', 'description' => 'ID original del campo'],
                            'name' => ['type' => 'STRING', 'description' => 'Nombre traducido del campo']
                        ],
                        'required' => ['id', 'name']
                    ]
                ]
            ],
            'required' => ['name', 'custom_fields']
        ];

        $translationsProperties = [];
        foreach ($locales as $code => $lang) {
            $translationsProperties[$code] = $languageSchema;
        }

        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'translations' => [
                    'type' => 'OBJECT',
                    'description' => 'Mapa de idiomas (claves: en, fr, ro, ar, wo)',
                    'properties' => $translationsProperties
                ]
            ],
            'required' => ['translations']
        ];

        // Usamos el servicio del creador del servicio o el global
        $gemini->forUser($this->service->user);
        
        try {
            $system = "Eres un asistente traductor. Devuelve exactamente el JSON solicitado según el esquema de salida.";
            $data = $gemini->generateStructuredData($prompt, $schema, $system);
            
            if (!empty($data) && isset($data['translations'])) {
                // Preservar traducciones previas si existieran
                $currentTranslations = $this->service->translations ?? [];
                
                foreach ($locales as $code => $lang) {
                    if (isset($data['translations'][$code])) {
                        $langData = $data['translations'][$code];
                        
                        // Convertir array de custom_fields a mapa (id => name) para el accesor
                        if (isset($langData['custom_fields']) && is_array($langData['custom_fields'])) {
                            $mappedFields = [];
                            foreach ($langData['custom_fields'] as $cf) {
                                if (isset($cf['id']) && isset($cf['name'])) {
                                    $mappedFields[$cf['id']] = $cf['name'];
                                }
                            }
                            $langData['custom_fields'] = $mappedFields;
                        }
                        
                        $currentTranslations[$code] = $langData;
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
