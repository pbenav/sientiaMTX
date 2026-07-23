<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Services;

/**
 * Servicio de generación y verificación de códigos TOTP (RFC 6238).
 *
 * Implementa generación de secretos base32, códigos TOTP de 6 dígitos
 * con ventana de discrepancia configurable, y URIs de provisioning para QR.
 */
class TotpService
{
    private static string $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Genera un secreto aleatorio en formato base32.
     *
     * @param  int  $length  Longitud del secreto (por defecto 16 caracteres).
     * @return string
     */
    public function generateSecret(int $length = 16): string
    {
        $secret = '';
        while (strlen($secret) < $length) {
            $secret .= self::$base32Chars[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Obtiene el código TOTP de 6 dígitos para un secreto en un slice de tiempo dado.
     *
     * Usa HMAC-SHA1 con truncamiento dinámico según RFC 6238.
     * Si timeSlice es null, usa el slice actual (floor(time/30)).
     *
     * @param  string  $secret  Secreto base32.
     * @param  int|null  $timeSlice  Slice de tiempo (default: time actual dividido entre 30).
     * @return string
     */
    public function getCode(string $secret, int $timeSlice = null): string
    {
        if ($timeSlice === null) {
            $timeSlice = floor(time() / 30);
        }

        $secretKey = $this->base32Decode($secret);

        // Pack time slice to 64-bit big-endian binary string
        $time = chr(0) . chr(0) . chr(0) . chr(0) . pack('N', $timeSlice);

        // Calculate HMAC-SHA1
        $hmac = hash_hmac('sha1', $time, $secretKey, true);

        // Dynamic truncation
        $offset = ord($hmac[19]) & 0xf;
        $hashpart = substr($hmac, $offset, 4);

        // Unpack 32-bit big-endian integer
        $value = unpack('N', $hashpart);
        $value = $value[1];
        $value = $value & 0x7fffffff;

        $modulo = pow(10, 6);
        $code = $value % $modulo;

        return str_pad((string)$code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verifica un código TOTP dentro de una ventana de discrepancia de tiempo.
     *
     * Compara el código proporcionado con los códigos calculados para el slice actual
     * más/minus la discrepancia (por defecto 1, es decir 3 ventanas de 30s = 90s totales).
     * Usa hash_equals para prevenir timing attacks.
     *
     * @param  string  $secret  Secreto base32.
     * @param  string  $code  Código de 6 dígitos a verificar.
     * @param  int  $discrepancy  Ventanas de tiempo a verificar por lado (default 1).
     * @return bool
     */
    public function verifyCode(string $secret, string $code, int $discrepancy = 1): bool
    {
        $currentTimeSlice = floor(time() / 30);

        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = $this->getCode($secret, $currentTimeSlice + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtiene la URI de provisioning para configuración de código QR.
     *
     * Formato: otpauth://totp/{issuer}:{label}?secret={secret}&issuer={issuer}
     *
     * @param  string  $email  Email/identificador del usuario (label).
     * @param  string  $secret  Secreto base32.
     * @return string
     */
    public function getQrCodeUri(string $email, string $secret): string
    {
        $appName = rawurlencode('SientiaMTX');
        return "otpauth://totp/{$appName}:" . rawurlencode($email) . "?secret={$secret}&issuer={$appName}";
    }

    /**
     * Decodifica una cadena base32 a binario.
     *
     * Valida el formato con regex /^[A-Z2-7]+$/ antes de procesar.
     * Implementa decodificación bit a bit con buffer de 5 bits por carácter.
     *
     * @param  string  $base32  Cadena base32 a decodificar.
     * @return string
     * @throws \InvalidArgumentException
     */
    private function base32Decode(string $base32): string
    {
        $base32 = strtoupper($base32);
        if (!preg_match('/^[A-Z2-7]+$/', $base32)) {
            throw new \InvalidArgumentException('Invalid base32 string.');
        }

        $decoded = '';
        $buffer = 0;
        $bufferLength = 0;

        $length = strlen($base32);
        for ($i = 0; $i < $length; $i++) {
            $char = $base32[$i];
            $value = strpos(self::$base32Chars, $char);

            $buffer = ($buffer << 5) | $value;
            $bufferLength += 5;

            if ($bufferLength >= 8) {
                $bufferLength -= 8;
                $decoded .= chr(($buffer >> $bufferLength) & 0xff);
            }
        }

        return $decoded;
    }
}
