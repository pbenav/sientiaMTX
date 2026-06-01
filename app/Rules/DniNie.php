<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DniNie implements ValidationRule
{
    /**
     * Valida DNI o NIE español.
     * Si el valor parece un pasaporte (empieza por 2+ letras o tiene formato mixto)
     * se deja pasar sin validar el dígito de control.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = strtoupper(trim($value));

        // Pasaporte: empieza por 2 letras o tiene 9 chars con letras mezcladas -> skip
        if ($this->isPassport($value)) {
            return;
        }

        // NIE: empieza por X, Y o Z
        if (preg_match('/^[XYZ]\d{7}[A-Z]$/', $value)) {
            if (!$this->validateNie($value)) {
                $fail('El NIE introducido no es válido.');
            }
            return;
        }

        // DNI: 8 dígitos + letra
        if (preg_match('/^\d{8}[A-Z]$/', $value)) {
            if (!$this->validateDni($value)) {
                $fail('El DNI introducido no es válido. Comprueba el dígito de control.');
            }
            return;
        }

        $fail('El documento introducido no tiene un formato válido (DNI, NIE o Pasaporte).');
    }

    /**
     * Detecta si el valor parece un pasaporte u otro doc extranjero.
     * Criterios: empieza por 2+ letras, o tiene longitud > 9, o combina letras/números sin patrón DNI/NIE.
     */
    private function isPassport(string $value): bool
    {
        // Pasaportes españoles: 3 letras + 6 dígitos (ej. AAA123456)
        if (preg_match('/^[A-Z]{2,}/', $value)) {
            return true;
        }

        // Pasaportes extranjeros variados: longitud ≠ 9 y no es DNI ni NIE
        if (!preg_match('/^[XYZ]\d{7}[A-Z]$/', $value) && !preg_match('/^\d{8}[A-Z]$/', $value)) {
            return true;
        }

        return false;
    }

    private function validateDni(string $dni): bool
    {
        $letters = 'TRWAGMYFPDXBNJZSQVHLCKE';
        $number  = (int) substr($dni, 0, 8);
        $letter  = substr($dni, 8, 1);

        return $letter === $letters[$number % 23];
    }

    private function validateNie(string $nie): bool
    {
        $letters = 'TRWAGMYFPDXBNJZSQVHLCKE';
        $map     = ['X' => '0', 'Y' => '1', 'Z' => '2'];

        $nieNormalized = $map[$nie[0]] . substr($nie, 1, 7);
        $number        = (int) $nieNormalized;
        $letter        = substr($nie, 8, 1);

        return $letter === $letters[$number % 23];
    }
}
