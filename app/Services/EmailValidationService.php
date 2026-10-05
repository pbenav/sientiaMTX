<?php

namespace App\Services;

use Exception;

class EmailValidationService
{
    /**
     * Valida si un email existe realmente conectando al servidor MX/SMTP.
     * No envía ningún email, solo verifica la existencia de la casilla.
     */
    public function verify(string $email, ?int $visitorId = null): array
    {
        if (!config('email_validation.enabled', true)) {
            return ['valid' => true, 'reason' => 'Email validation is disabled', 'method' => 'disabled'];
        }

        $parts = explode('@', $email);

        if (count($parts) !== 2) {
            $result = ['valid' => false, 'reason' => 'Invalid email format', 'method' => 'none'];
            $this->logValidation($email, $result, $visitorId);
            return $result;
        }

        [, $domain] = $parts;

        // Excepciones: permitir Gmail, Outlook, Yahoo, Hotmail, etc.
        if ($this->isTrustedProvider($domain)) {
            $result = $this->verifyTrustedProvider($email);
            $this->logValidation($email, $result, $visitorId);
            return $result;
        }

        // Para otros dominios: SMTP directo
        $result = $this->verifyViaSMTP($email);
        $this->logValidation($email, $result, $visitorId);
        return $result;
    }

    /**
     * Proveedores de confianza donde no hacemos SMTP (protección anti-baneo).
     * Solo verificamos DNS MX + formato.
     */
    protected function verifyTrustedProvider(string $email): array
    {
        $parts = explode('@', $email);
        [, $domain] = $parts;

        // Verificar MX records
        if (!checkdnsrr($domain, 'MX')) {
            return [
                'valid' => false,
                'reason' => "Domain '$domain' has no MX records",
                'method' => 'mx_only',
            ];
        }

        // Verificar que no sea desechable
        if ($this->isDisposableDomain($domain)) {
            return [
                'valid' => false,
                'reason' => "Domain '$domain' is a known disposable email provider",
                'method' => 'mx_only',
            ];
        }

        return [
            'valid' => true,
            'reason' => "Valid MX records for trusted provider '$domain'",
            'method' => 'mx_only',
        ];
    }

    /**
     * Verifica mediante conexión SMTP real al servidor del dominio.
     */
    protected function verifyViaSMTP(string $email): array
    {
        $parts = explode('@', $email);
        [$localPart, $domain] = $parts;

        // Obtener servidores MX
        $mxRecords = $this->getMXRecords($domain);

        if (empty($mxRecords)) {
            return ['valid' => false, 'reason' => 'No MX records found for domain', 'method' => 'smtp', 'mx_count' => 0];
        }

        // Ordenar por prioridad (menor = más prioritario)
        usort($mxRecords, fn($a, $b) => $a['priority'] <=> $b['priority']);

        $lastError = null;

        foreach ($mxRecords as $mx) {
            $mxHost = $mx['host'];

            // Limpiar el local part para evitar inyección en el socket
            $safeLocal = preg_replace('/[^\w\.\-\_]/', '', $localPart);

            try {
                $socket = @fsockopen($mxHost, 25, $errno, $errstr, 8);

                if (!$socket) {
                    $lastError = $errstr ?? "Connection failed (errno: $errno)";
                    continue;
                }

                stream_set_timeout($socket, 8);

                // Leer banner inicial
                $response = @fgets($socket, 1024);
                if (!preg_match('/220/i', $response)) {
                    @fclose($socket);
                    $lastError = 'No 220 banner received';
                    continue;
                }

                // EHLO
                @fwrite($socket, "EHLO sientiaMTX\r\n");
                @fgets($socket, 1024);

                // MAIL FROM (silent validation)
                @fwrite($socket, "MAIL FROM:<validation@sientia.com>\r\n");
                @fgets($socket, 1024);

                // RCPT TO — aquí el servidor dice si el email existe
                @fwrite($socket, "RCPT TO:<$safeLocal@$domain>\r\n");
                $response = @fgets($socket, 1024);

                // 250 = existe
                if (preg_match('/250|2\.0\.0|accepted/i', $response)) {
                    @fclose($socket);
                    return [
                        'valid' => true,
                        'reason' => 'SMTP server confirmed mailbox exists',
                        'method' => 'smtp',
                        'mx_count' => count($mxRecords),
                        'mx_host' => $mxHost,
                    ];
                }

                // 450/4.2.1 = greylisting (temporal) → probablemente existe
                if (preg_match('/450|4\.2\.1/i', $response)) {
                    @fclose($socket);
                    return [
                        'valid' => true,
                        'reason' => 'Mailbox temporarily unavailable (greylisting) — likely exists',
                        'method' => 'smtp',
                        'mx_count' => count($mxRecords),
                        'mx_host' => $mxHost,
                    ];
                }

                // 550 = no existe / rechazado permanentemente
                @fclose($socket);
                $lastError = 'SMTP rejected: ' . trim($response);
                break;

            } catch (Exception $e) {
                $lastError = $e->getMessage();
                continue;
            }
        }

        return [
            'valid' => false,
            'reason' => "SMTP verification failed: " . ($lastError ?? 'unknown error'),
            'method' => 'smtp',
            'mx_count' => count($mxRecords),
            'mx_host' => $mxRecords[0]['host'] ?? null,
        ];
    }

    /**
     * Obtiene los registros MX de un dominio.
     */
    protected function getMXRecords(string $domain): array
    {
        $records = dns_get_record($domain, DNS_MX);

        if (!$records) {
            return [];
        }

        $mxRecords = [];
        foreach ($records as $record) {
            $priority = $record['mxpriority'] ?? $record['priority'] ?? 10;
            $mxRecords[] = [
                'host' => $record['target'],
                'priority' => (int) $priority,
            ];
        }

        return $mxRecords;
    }

    /**
     * Proveedores de confianza (grandes proveedores de email).
     * No hacemos SMTP contra ellos para evitar ser bloqueados.
     */
    protected function isTrustedProvider(string $domain): bool
    {
        $trusted = config('email_validation.trusted_providers', [
            'gmail.com', 'googlemail.com',
            'outlook.com', 'hotmail.com', 'live.com', 'msn.com',
            'yahoo.com', 'yahoo.co.uk', 'yahoo.es', 'yahoo.fr',
            'aol.com', 'icloud.com', 'me.com', 'mac.com',
            'protonmail.com', 'proton.me', 'pm.me',
            'zoho.com', 'mail.com', 'gmx.com', 'gmx.net',
            'yandex.com', 'fastmail.com', 'hey.com',
        ]);

        return in_array(strtolower($domain), $trusted);
    }

    /**
     * Dominios desechables/temporales conocidos.
     */
    protected function isDisposableDomain(string $domain): bool
    {
        $disposable = [
            'tempmail.com', 'tempmail.org', 'temp-mail.org', 'temp-mail.io',
            'throwaway.email', 'guerrillamail.com', 'guerrillamail.net',
            'guerrillamail.de', 'guerrillamail.org', 'guerrillamailblock.com',
            'mailinator.com', 'mailinator.net', 'mailinator.org',
            'yopmail.com', 'yopmail.fr', 'yopmail.net',
            'trashmail.com', 'trashmail.net', 'trashmail.org',
            'sharklasers.com', 'grr.la', 'guerrillamail.info', 'guerrillamail.biz',
            'spam4.me', 'dispostable.com', 'fakeinbox.com', 'fakeinbox.net',
            'mailnesia.com', 'maildrop.cc', 'mailnull.com',
            'mytemp.email', 'mytempmail.com', 'getnada.com',
            'tempail.com', 'tempr.email', 'tempinbox.com',
            'tempmailaddress.com', 'throwawayemail.com', 'trashmail.me',
            'wegwerfmail.de', 'wegwerfmail.net', 'wegwerfmail.org',
            'binkemail.com', 'emkei.org', 'emails.pw', 'emz.net',
            'fivemail.de', 'fleckens.hu', 'getairmail.com',
            'gishpuppy.com', 'harakirimail.com', 'hideyoumail.com',
            'inboxalias.com', 'inboxclean.org', 'jetable.net', 'jetable.org',
            'mailexpire.com', 'mailgc.com', 'mailimo.com', 'mailmancrm.com',
            'mailmoth.com', 'mailnull.com', 'mailscrap.com',
            'mailzilla.com', 'meltmail.com', 'minutemail.com',
            'mintemail.com', 'moakt.com', 'mytemp.email',
            'nailsforsale.com', 'nospamfor.us', 'nowmail.io',
            'nurfmail.com', 'nyr7.com', 'objectmail.com',
            'odnr.org', 'one-time.email', 'oneoffmail.com',
            'onetimermail.com', 'onlymsg.com', 'orangeott.de',
            'playheate.com', 'plugorg.org', 'plexolan.de',
            'pmailinator.com', 'polarmail.com', 'pookmail.com',
            'spam4.me', 'spamavert.com', 'spambog.com', 'spambot.net',
            'spamfree24.org', 'spamhole.com', 'spamhole.net',
            'spamix.org', 'spamfree24.com', 'spamfree24.de',
            'spamfree24.eu', 'spamfree24.info', 'spamfree24.net',
            'spamfree24.org', 'spamfree24.top',
            'trash-mail.com', 'trash2009.com', 'trash2010.com',
            'trashdevil.com', 'trashdevil.de', 'trashemail.de',
            'trashmail.at', 'trashmail.io', 'trashmail.me',
            'trashmail.ws', 'trashmailer.com', 'trbvm.com',
            'trillianxi.com', 'tryalert.com', 'turual.com',
            'uin.me', 'umail.net', 'upliftech.com',
            'uhura.com', 'unmail.ru', 'upload-nlw.com',
            'urbismail.com', 'usearnold.com', 'useit.ch',
            'uroid.com', 'v3t.com', 'victoriantwins.com',
            'vmail.me', 'voidbay.com', 'voila.fr',
            'vomoto.com', 'vubby.com', 'w3internet.co.uk',
            'wasteland.rfc822.org', 'watch-harry-potter.com',
            'watchever.com', 'watchfull.net', 'webemail.me',
            'webm4il.info', 'webmail4u.eu', 'webtrip.ch',
            'wee.my', 'webs-u.net', 'websense.ca',
            'wuzdu.net', 'wuzup.net', 'wuzupmail.net',
            'www.com', 'www.e4.biz', 'www2008.zzn.com',
            'xagloo.com', 'xbaby.com', 'xcode.ro',
            'xl.cx', 'xmail.com', 'xmailer.be',
            'xpmail.org', 'xtreak.com', 'xwaretech.com',
            'xxuz.com', 'xxxdraft.com', 'xxxxx.online',
            'yahooinc.com', 'yamailwin.com', 'yandexmail.ru',
            'yebox.com', 'yep.it', 'yogamaven.com',
            'yomail.info', 'yopmail.com', 'yopmail.fr',
            'yopweb.com', 'yourdomain.com', 'yourinbox.com',
            'yourspamgoeshere.com', 'yroid.com', 'yssience.com',
            'zain.com', 'zainmax.net', 'ze.tc',
            'zebins.com', 'zebins.eu', 'zehnminuten.de',
            'zehnminutenmail.de', 'zippymail.info', 'zoaxe.com',
            'zoemail.com', 'zoemail.net', 'zoemail.org',
            'zomg.info', 'zserio.com', 'zybermail.com',
        ];

        return in_array(strtolower($domain), $disposable);
    }

    /**
     * Registra la validación en la base de datos para auditoría.
     */
    protected function logValidation(string $email, array $result, ?int $visitorId = null): void
    {
        if (!config('email_validation.log_validations', true)) {
            return;
        }

        try {
            $parts = explode('@', $email);
            $domain = $parts[1] ?? '';

            \App\Models\EmailValidation::create([
                'visitor_id' => $visitorId,
                'email' => $email,
                'domain' => $domain,
                'is_valid' => $result['valid'],
                'verification_method' => $result['method'] ?? null,
                'verification_reason' => $result['reason'] ?? null,
                'mx_count' => $result['mx_count'] ?? 0,
                'mx_host' => $result['mx_host'] ?? null,
                'verified_at' => $result['valid'] ? now() : null,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Failed to log email validation: ' . $e->getMessage());
        }
    }
}
