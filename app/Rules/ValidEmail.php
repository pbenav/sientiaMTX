<?php

namespace App\Rules;

use App\Services\EmailValidationService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidEmail implements ValidationRule
{
    /**
     * Valida que el email no solo tenga formato correcto,
     * sino que realmente exista (verificación DNS + SMTP).
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $email = trim($value);

        if (empty($email)) {
            return; // El campo es nullable, así que vacío es válido
        }

        // Primero validación básica de formato
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $fail('El campo :attribute no es un correo electrónico válido.');
            return;
        }

        // Verificación completa usando el servicio
        $service = app(EmailValidationService::class);
        $result = $service->verify($email);

        if (!$result['valid']) {
            $fail('Este correo electrónico no parece existir. Por favor, verifica que sea correcto.');
            return;
        }
    }
}
