<?php

namespace App\Services;

class ActivityStatusManager
{
    /**
     * Obtiene todos los estados posibles agrupados por los tipos de actividad que los soportan.
     */
    public static function getAllStatusesWithTypes(): array
    {
        $loader = app(TemplateLoader::class);
        $templates = $loader->allTemplates();
        
        $statuses = [];
        foreach ($templates as $type => $template) {
            $states = array_keys($template['states'] ?? []);
            foreach ($states as $state) {
                if (!isset($statuses[$state])) {
                    $statuses[$state] = [];
                }
                $statuses[$state][] = $type;
            }
        }
        
        $result = [];
        foreach ($statuses as $state => $types) {
            // Check if translation exists, fallback to ucfirst
            $transKey = "activities.statuses.{$state}";
            $label = __($transKey);
            if ($label === $transKey) {
                // Translation missing fallback
                $fallbacks = [
                    'final' => 'Final',
                    'implemented' => 'Implementado'
                ];
                $label = $fallbacks[$state] ?? ucfirst($state);
            }

            $result[$state] = [
                'label' => $label,
                'types' => array_unique($types)
            ];
        }
        
        // Sort by translated label
        uasort($result, fn($a, $b) => strcmp($a['label'], $b['label']));
        
        return $result;
    }
}
