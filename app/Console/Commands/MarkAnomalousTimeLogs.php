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

        $query = TimeLog::query()
            ->where('type', 'workday')
            ->whereNotNull('end_at')
            ->where('is_anomalous', false) // Solo procesa los aún no marcados
            ->with('user');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $total   = $query->count();
        $marked  = 0;
        $skipped = 0;

        $this->info("Analizando {$total} registros de jornada cerrados" . ($dryRun ? ' [DRY-RUN]' : '') . '…');

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunkById(200, function ($logs) use ($dryRun, &$marked, &$skipped, $bar) {
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

                if ($durationMinutes > $threshold) {
                    $reason = $durationMinutes > $hardCapMinutes
                        ? 'exceeded_threshold'
                        : 'exceeded_schedule';

                    if (!$dryRun) {
                        $log->update([
                            'is_anomalous'     => true,
                            'anomaly_reason'   => $reason,
                            'expected_minutes' => $expectedMinutes,
                        ]);
                    } else {
                        $this->newLine();
                        $this->line(sprintf(
                            '  [DRY-RUN] Log #%d  user=%s  dur=%dm  expected=%dm  reason=%s',
                            $log->id,
                            $user->name,
                            $durationMinutes,
                            $expectedMinutes,
                            $reason
                        ));
                    }

                    $marked++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Completado: {$marked} registros marcados como anómalos, {$skipped} omitidos (sin usuario).");

        if ($dryRun && $marked > 0) {
            $this->warn('ℹ️  Modo DRY-RUN: ningún registro fue modificado. Ejecuta sin --dry-run para aplicar.');
        }

        return self::SUCCESS;
    }
}
