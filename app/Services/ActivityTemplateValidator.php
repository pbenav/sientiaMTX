<?php

namespace App\Services;

use Exception;
use Illuminate\Validation\ValidationException;

class ActivityTemplateValidator
{
    protected TemplateLoader $templateLoader;

    public function __construct(TemplateLoader $templateLoader)
    {
        $this->templateLoader = $templateLoader;
    }

    /**
     * Valida un array de metadatos contra las reglas de la plantilla JSON correspondiente al tipo de actividad.
     *
     * @param string $type
     * @param array $data
     * @return bool
     * @throws Exception|ValidationException
     */
    public function validate(string $type, array $data): bool
    {
        $template = $this->templateLoader->getTemplate($type);
        
        if (!$template) {
            throw new Exception("Tipo de actividad '{$type}' no encontrado en las plantillas.");
        }

        // Si la plantilla no tiene la llave de propiedades, no hay validaciones específicas a aplicar.
        if (!isset($template['properties']) || !is_array($template['properties'])) {
            return true;
        }

        $errors = [];

        foreach ($template['properties'] as $fieldName => $rules) {
            $value = $data[$fieldName] ?? $rules['default'] ?? null;

            // 1. Validar requeridos
            if (!empty($rules['required']) && ($value === null || $value === '')) {
                $errors[$fieldName][] = "El campo '{$fieldName}' es obligatorio.";
                continue; // Si falta un campo requerido, pasamos al siguiente campo
            }

            // Si está vacío pero no es obligatorio, saltamos validaciones posteriores para este campo
            if ($value === null || $value === '') {
                continue;
            }

            // 2. Validar tipo de dato (string, integer, etc)
            if (isset($rules['type']) && !$this->validateType($value, $rules['type'])) {
                $errors[$fieldName][] = "El campo '{$fieldName}' debe ser de tipo {$rules['type']}.";
            }

            // 3. Validar listas enums limitadas
            if (isset($rules['enum']) && !in_array($value, $rules['enum'], true)) {
                $errors[$fieldName][] = "El campo '{$fieldName}' debe ser uno de: " . implode(', ', $rules['enum']);
            }

            // 4. Validar rangos numéricos
            if (isset($rules['validation'])) {
                if (isset($rules['validation']['min']) && $value < $rules['validation']['min']) {
                    $errors[$fieldName][] = "El campo '{$fieldName}' debe ser mayor o igual a {$rules['validation']['min']}.";
                }
                if (isset($rules['validation']['max']) && $value > $rules['validation']['max']) {
                    $errors[$fieldName][] = "El campo '{$fieldName}' debe ser menor o igual a {$rules['validation']['max']}.";
                }
            }

            // 5. Validar longitud máxima de textos
            if (isset($rules['max_length']) && is_string($value) && mb_strlen($value) > $rules['max_length']) {
                $errors[$fieldName][] = "El campo '{$fieldName}' no puede exceder los {$rules['max_length']} caracteres.";
            }
        }

        if (!empty($errors)) {
            // Utilizamos el ValidationException para que Laravel devuelva un 422 automáticamente en peticiones HTTP
            throw ValidationException::withMessages($errors);
        }

        return true;
    }

    /**
     * Valida si el valor coincide con el tipo esperado definido en la plantilla.
     */
    protected function validateType(mixed $value, string $expectedType): bool
    {
        return match ($expectedType) {
            'string', 'text' => is_string($value),
            'integer' => is_int($value),
            'float' => is_float($value) || is_int($value),
            'boolean' => is_bool($value),
            'array' => is_array($value),
            'object' => is_object($value) || (is_array($value) && $this->isAssoc($value)),
            default => false,
        };
    }

    /**
     * Comprueba si un array es asociativo (tratado como un Object en JSON).
     */
    protected function isAssoc(array $arr): bool
    {
        if (array_keys($arr) === range(0, count($arr) - 1)) {
            return false;
        }
        return true;
    }
}
