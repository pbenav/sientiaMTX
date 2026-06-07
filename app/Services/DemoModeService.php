<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Services;

/**
 * DemoModeService
 *
 * Centralises all logic related to the demonstration / privacy mode.
 * When the mode is active, sensitive data is masked or scrambled before
 * being shown in the UI, while the real data in the database is NEVER touched.
 *
 * Activated via the APP_DEMO_MODE=on variable in .env (readable through
 * config('settings.demo_mode')).
 */
class DemoModeService
{
    // ─── Scramble character sets ──────────────────────────────────────────────
    protected const CHARS_ALPHA  = 'abcdefghijklmnopqrstuvwxyz';
    protected const CHARS_UPPER  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    protected const CHARS_DIGITS = '0123456789';
    protected const CHARS_HEX   = '0123456789abcdef';

    /**
     * Whether demo mode is currently active.
     */
    public function isActive(): bool
    {
        return strtolower((string) config('settings.demo_mode', 'off')) === 'on';
    }

    /**
     * Mask a single value according to its type.
     *
     * Supported types:
     *   'name'    → Random realistic-looking first+last name
     *   'email'   → Scrambled address keeping domain TLD structure
     *   'phone'   → Digits replaced with random digits, keeping format chars
     *   'text'    → Words replaced char-by-char with same-length random words
     *   'token'   → Hex-like random string of the same length
     *   'id'      → Numeric-safe scramble (same digit count)
     *   'url'     → Keeps protocol/domain structure but scrambles the path
     *   'initial' → Returns a single uppercase random letter
     *
     * @param  string|null  $value
     * @param  string       $type
     * @return string
     */
    public function mask(?string $value, string $type = 'text'): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return match ($type) {
            'name'    => $this->maskName($value),
            'email'   => $this->maskEmail($value),
            'phone'   => $this->maskPhone($value),
            'token'   => $this->maskToken($value),
            'id'      => $this->maskId($value),
            'url'     => $this->maskUrl($value),
            'initial' => $this->maskInitial(),
            default   => $this->maskText($value),   // 'text', 'message', etc.
        };
    }

    /**
     * Return the Tailwind CSS utility class used for visual blurring in Blade views.
     * Useful for avatar images, profile photos, etc.
     */
    public function blurClass(): string
    {
        return 'demo-blur';
    }

    /**
     * Return a CSS filter style string for inline use.
     */
    public function blurStyle(): string
    {
        return 'filter: blur(6px); user-select: none; pointer-events: none;';
    }

    // ─── Private masking strategies ──────────────────────────────────────────

    protected function maskName(string $value): string
    {
        static $firstNames = [
            'Alejandro','Beatriz','Carlos','Diana','Eduardo','Fátima',
            'Gonzalo','Helena','Ignacio','Julia','Lucas','María',
            'Nicolás','Olivia','Pablo','Raquel','Sergio','Teresa',
            'Úrsula','Valentín','Xenia','Yaiza','Zara',
        ];
        static $lastNames = [
            'García','Martínez','López','Sánchez','Fernández','González',
            'Rodríguez','Pérez','Álvarez','Torres','Romero','Díaz',
            'Morales','Castro','Vega','Ramos','Molina','Herrera',
        ];

        // Use a deterministic seed based on the original so the same name
        // always maps to the same demo name within a single request.
        $seed = crc32($value);
        $fn   = $firstNames[abs($seed) % count($firstNames)];
        $ln   = $lastNames[abs($seed >> 4) % count($lastNames)];

        return "{$fn} {$ln}";
    }

    protected function maskEmail(string $value): string
    {
        if (!str_contains($value, '@')) {
            return $this->maskText($value);
        }

        [$local, $domain] = explode('@', $value, 2);

        $maskedLocal  = $this->scrambleAlpha($local);
        $domainParts  = explode('.', $domain);
        $maskedDomain = $this->scrambleAlpha($domainParts[0])
            . '.' . ($domainParts[count($domainParts) - 1] ?? 'com');

        return strtolower("{$maskedLocal}@{$maskedDomain}");
    }

    protected function maskPhone(string $value): string
    {
        return preg_replace_callback('/\d/', fn() => random_int(0, 9), $value);
    }

    protected function maskToken(string $value): string
    {
        return $this->scrambleHex(strlen($value));
    }

    protected function maskId(string $value): string
    {
        if (!is_numeric($value)) {
            return $this->maskToken($value);
        }

        $len    = strlen($value);
        $result = '';
        for ($i = 0; $i < $len; $i++) {
            $result .= ($i === 0) ? random_int(1, 9) : random_int(0, 9);
        }
        return $result;
    }

    protected function maskUrl(string $value): string
    {
        // Keep scheme + host, scramble path segments
        $parts = parse_url($value);
        if (!$parts) {
            return $this->maskText($value);
        }

        $base = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? 'example.com');
        $path = $parts['path'] ?? '/';

        $maskedPath = implode('/', array_map(
            fn($seg) => $seg === '' ? '' : $this->scrambleAlpha($seg),
            explode('/', $path)
        ));

        return $base . $maskedPath;
    }

    protected function maskInitial(): string
    {
        return self::CHARS_UPPER[random_int(0, 25)];
    }

    protected function maskText(string $value): string
    {
        // Replace every alphabetical character with a random letter of the same case,
        // keep digits, spaces, punctuation and newlines as-is so the visual layout is preserved.
        return preg_replace_callback('/[a-zA-Z]/', function (array $m) {
            $char = $m[0];
            return ctype_upper($char)
                ? self::CHARS_UPPER[random_int(0, 25)]
                : self::CHARS_ALPHA[random_int(0, 25)];
        }, $value);
    }

    // ─── Helper utilities ─────────────────────────────────────────────────────

    protected function scrambleAlpha(string $value): string
    {
        return preg_replace_callback('/[a-zA-Z]/', function (array $m) {
            return ctype_upper($m[0])
                ? self::CHARS_UPPER[random_int(0, 25)]
                : self::CHARS_ALPHA[random_int(0, 25)];
        }, $value);
    }

    protected function scrambleHex(int $length): string
    {
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= self::CHARS_HEX[random_int(0, 15)];
        }
        return $result;
    }
}
