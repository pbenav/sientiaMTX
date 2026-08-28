<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Console\Commands;

use App\Models\TimeLog;
use Illuminate\Console\Command;

class MarkAnomalousTimeLogs extends Command
{
    protected $signature   = 'timelogs:mark-anomalous
                                {--dry-run : Sólo muestra qué registros se marcarían, sin modificar nada}
                                {--user=  : ID de usuario concreto (opcional, procesa todos si se omite)}';

    protected $description = 'Revisa todos los registros de jornada cerrados y marca como anómalos '
                           . 'los que superan la jornada esperada del usuario o el hard cap de 10 horas.';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $userId = $this->option('user');

        $hardCapMinutes = TimeLog::ANOMALY_HARD_CAP_HOURS * 60;

        // Procesa:
        // 1. Registros aún no marcados (is_anomalous = false) que superen el umbral
        // 2. Registros marcados pero sin expected_minutes (normalización incompleta)
        $query = TimeLog::query()
            ->where('type', 'workday')
            ->whereNotNull('end_at')
            ->where(function($q) use ($hardCapMinutes) {
                $q->where('is_anomalous', false)
                  ->orWhere(function($q2) {
                      // Anómalos sin expected_minutes = normalización pendiente
                      $q2->where('is_anomalous', true)
                         ->whereNull('expected_minutes');
                  });
            })
            ->with('user');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $total   = $query->count();
        $marked  = 0;
        $fixed   = 0;
        $skipped = 0;

        $this->info("Analizando {$total} registros de jornada cerrados" . ($dryRun ? ' [DRY-RUN]' : '') . '…');

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunkById(200, function ($logs) use ($dryRun, &$marked, &$fixed, &$skipped, $bar) {
            foreach ($logs as $log) {
                $user = $log->user;
                if (!$user) {
                    $bar->advance();
                    $skipped++;
                    continue;
                }

                $durationMinutes = (int) $log->start_at->diffInMinutes($log->end_at);
                $hardCapMinutes  = TimeLog::ANOMALY_HARD_CAP_HOURS * 60;
                $expectedMinutes = TimeLog::expectedMinutesForUser($user, $log->start_at);
                $threshold       = min((int) ($expectedMinutes * 1.20), $hardCapMinutes);

                $alreadyMarked = $log->is_anomalous && is_null($log->expected_minutes);

                if ($alreadyMarked || $durationMinutes > $threshold) {
                    $reason = $durationMinutes > $hardCapMinutes
                        ? 'exceeded_threshold'
                        : 'exceeded_schedule';

                    if (!$dryRun) {
                        $log->update([
                            'is_anomalous'     => true,
                            'anomaly_reason'   => $log->anomaly_reason ?? $reason,
                            'expected_minutes' => $expectedMinutes,
                        ]);
                    } else {
                        $this->newLine();
                        $this->line(sprintf(
                            '  [DRY-RUN] Log #%d  user=%s  dur=%dm  expected=%dm  reason=%s%s',
                            $log->id, $user->name, $durationMinutes, $expectedMinutes, $reason,
                            $alreadyMarked ? '  [CORRIGIENDO expected_minutes faltante]' : ''
                        ));
                    }

                    $alreadyMarked ? $fixed++ : $marked++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Completado: {$marked} nuevos registros marcados, {$fixed} normalizaciones completadas, {$skipped} omitidos (sin usuario).");

        if ($dryRun && ($marked + $fixed) > 0) {
            $this->warn('ℹ️  Modo DRY-RUN: ningún registro fue modificado. Ejecuta sin --dry-run para aplicar.');
        }

        return self::SUCCESS;
    }
}
