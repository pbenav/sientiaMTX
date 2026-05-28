<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;

/**
 * Migrates all AES-256-CBC encrypted fields in the database to AES-256-GCM.
 *
 * Detection: A GCM payload has a non-empty `tag` field in its JSON structure.
 * A CBC payload has an empty or missing `tag`.
 *
 * Safe to run multiple times (idempotent): already-GCM fields are skipped.
 *
 * Usage:
 *   php artisan crypto:migrate-to-gcm          # dry-run (no writes)
 *   php artisan crypto:migrate-to-gcm --write  # apply changes
 */
class MigrateEncryptionToGcm extends Command
{
    protected $signature   = 'crypto:migrate-to-gcm {--write : Actually write changes (default is dry-run)}';
    protected $description = 'Migrate all CBC-encrypted fields to AES-256-GCM';

    /** Fields to migrate: [ table => [ column, nullable ] ] */
    private array $targets = [
        'users' => [
            ['column' => 'two_factor_secret',   'nullable' => true],
            ['column' => 'google_token',         'nullable' => true],
            ['column' => 'google_refresh_token', 'nullable' => true],
        ],
        'user_ai_preferences' => [
            ['column' => 'api_key', 'nullable' => true],
        ],
    ];

    public function handle(): int
    {
        $write = $this->option('write');

        $appKey = config('app.key');
        if (!$appKey) {
            $this->error('APP_KEY is not set.');
            return self::FAILURE;
        }

        // Decode the key (supports base64: prefix)
        $rawKey = str_starts_with($appKey, 'base64:')
            ? base64_decode(substr($appKey, 7))
            : $appKey;

        if (strlen($rawKey) !== 32) {
            $this->error('APP_KEY must be 32 bytes (256-bit). Got ' . strlen($rawKey) . ' bytes.');
            return self::FAILURE;
        }

        $cbcEnc = new Encrypter($rawKey, 'aes-256-cbc');
        $gcmEnc = new Encrypter($rawKey, 'aes-256-gcm');

        $this->info($write ? '🔐 Running LIVE migration (--write)' : '🔍 DRY-RUN — no changes will be saved (add --write to apply)');
        $this->newLine();

        $totalMigrated = 0;
        $totalSkipped  = 0;
        $totalErrors   = 0;
        $totalNull      = 0;

        foreach ($this->targets as $table => $fields) {
            $this->line("📋 Table: <fg=cyan>{$table}</>");

            $rows = DB::table($table)->get();

            foreach ($rows as $row) {
                foreach ($fields as ['column' => $column, 'nullable' => $nullable]) {
                    $rawValue = $row->$column ?? null;

                    if ($rawValue === null || $rawValue === '') {
                        $totalNull++;
                        continue;
                    }

                    // Detect if already GCM
                    $payload = $this->decodePayload($rawValue);
                    if ($payload === null) {
                        // Not a valid Laravel encrypted payload — likely plain text or unknown format, skip silently.
                        $totalSkipped++;
                        if ($this->getOutput()->isVerbose()) {
                            $this->line("  [{$table}#{$row->id}] {$column}: <fg=yellow>not an encrypted payload — skipped</>");
                        }
                        continue;
                    }

                    if (!empty($payload['tag'])) {
                        // Already GCM
                        $totalSkipped++;
                        continue;
                    }

                    // Decrypt with CBC
                    try {
                        $plaintext = $cbcEnc->decrypt($rawValue);
                    } catch (\Throwable $e) {
                        $this->warn("  [{$table}#{$row->id}] {$column}: CBC decrypt failed — {$e->getMessage()}");
                        $totalErrors++;
                        continue;
                    }

                    // Re-encrypt with GCM
                    try {
                        $newValue = $gcmEnc->encrypt($plaintext);
                    } catch (\Throwable $e) {
                        $this->error("  [{$table}#{$row->id}] {$column}: GCM encrypt failed — {$e->getMessage()}");
                        $totalErrors++;
                        continue;
                    }

                    if ($write) {
                        DB::table($table)->where('id', $row->id)->update([$column => $newValue]);
                    }

                    $totalMigrated++;

                    if ($this->getOutput()->isVerbose()) {
                        $this->line("  [{$table}#{$row->id}] {$column}: <fg=green>migrated CBC→GCM</>");
                    }
                }
            }
        }

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Migrated CBC→GCM', $totalMigrated],
                ['Already GCM (skipped)', $totalSkipped],
                ['NULL / empty (skipped)', $totalNull],
                ['Errors', $totalErrors],
            ]
        );

        if (!$write && $totalMigrated > 0) {
            $this->newLine();
            $this->comment("Run with <fg=yellow>--write</> to apply {$totalMigrated} migration(s).");
        }

        if ($write && $totalMigrated > 0) {
            $this->newLine();
            $this->info("✅ Done! {$totalMigrated} field(s) migrated to AES-256-GCM.");
            $this->comment("You can now change APP_CIPHER=aes-256-gcm in config/app.php (or .env if you use it).");
        }

        if ($totalErrors > 0) {
            $this->error("⚠️  {$totalErrors} error(s) encountered. Check output above.");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Safely decode an encrypted Laravel payload and return the parsed JSON,
     * or null if it's not a valid payload.
     */
    private function decodePayload(string $value): ?array
    {
        try {
            $decoded = base64_decode($value, strict: true);
            if ($decoded === false) {
                return null;
            }
            $payload = json_decode($decoded, associative: true);
            if (!is_array($payload) || !isset($payload['iv'], $payload['value'])) {
                return null;
            }
            return $payload;
        } catch (\Throwable) {
            return null;
        }
    }
}
