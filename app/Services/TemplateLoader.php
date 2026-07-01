<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class TemplateLoader
{
    /**
     * Ruta donde se encuentran los archivos JSON de plantillas.
     * 
     * @var string
     */
    protected string $templatesPath;

    /**
     * Tiempo de vida de la caché en segundos (1 hora por defecto).
     * 
     * @var int
     */
    protected int $cacheTtl = 3600;

    public function __construct()
    {
        $this->templatesPath = base_path('config/activity_templates');
    }

    /**
     * Carga todas las plantillas de actividades disponibles.
     * 
     * Utiliza la caché para evitar lecturas de disco innecesarias.
     *
     * @return array
     */
    public function allTemplates(): array
    {
        return Cache::remember('activity_templates_all', $this->cacheTtl, function () {
            $templates = [];
            
            if (!File::exists($this->templatesPath)) {
                return [];
            }

            $files = File::files($this->templatesPath);

            foreach ($files as $file) {
                if ($file->getExtension() === 'json') {
                    $content = File::get($file->getPathname());
                    $decoded = json_decode($content, true);
                    
                    if (is_array($decoded) && isset($decoded['type'])) {
                        // Indexamos cada plantilla por su nombre de "type" (ej: TaskActivity)
                        $templates[$decoded['type']] = $decoded;
                    }
                }
            }

            return $templates;
        });
    }

    /**
     * Obtiene una plantilla específica por su tipo.
     *
     * @param string $type
     * @return array|null
     */
    public function getTemplate(string $type): ?array
    {
        $templates = $this->allTemplates();
        
        return $templates[$type] ?? null;
    }

    /**
     * Obtiene solo los nombres de los tipos de plantillas disponibles.
     * Útil para reglas de validación en requests.
     *
     * @return array
     */
    public function allTypes(): array
    {
        $templates = $this->allTemplates();
        
        return array_keys($templates);
    }

    /**
     * Limpia la caché de plantillas (útil tras crear o actualizar un archivo JSON).
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::forget('activity_templates_all');
    }
}
