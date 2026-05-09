<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Services;

class TotpService
{
    private static $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a random base32 secret.
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
     * Get the 6-digit TOTP code for a secret at a specific time slice.
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
     * Verify a TOTP code within a specific time window discrepancy.
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
     * Get the provisioning URI for QR code configuration.
     */
    public function getQrCodeUri(string $email, string $secret): string
    {
        $appName = rawurlencode('SientiaMTX');
        return "otpauth://totp/{$appName}:" . rawurlencode($email) . "?secret={$secret}&issuer={$appName}";
    }

    /**
     * Helper to decode a base32 string to binary.
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
