<?php

namespace App\Actions\Teams;

use App\Models\Team;
use Illuminate\Support\Str;

class UpdateTeamAction
{
    public function execute(Team $team, array $validated, bool $isAdmin, ?float $softDiskQuotaGb = null): Team
    {
        if (isset($validated['telegram_chat_id'])) {
            $validated['telegram_chat_id'] = trim($validated['telegram_chat_id']);
        }
        if (isset($validated['whatsapp_chat_id'])) {
            $validated['whatsapp_chat_id'] = trim($validated['whatsapp_chat_id']);
        }

        if ($isAdmin && isset($validated['disk_quota_gb'])) {
            $validated['disk_quota'] = (int)($validated['disk_quota_gb'] * 1024 * 1024 * 1024);
        }

        if ($softDiskQuotaGb !== null) {
            $softLimitBytes = (int)($softDiskQuotaGb * 1024 * 1024 * 1024);
            if ($softLimitBytes > $team->disk_quota) {
                $softLimitBytes = $team->disk_quota;
            }
            $validated['settings'] = $validated['settings'] ?? $team->settings ?? [];
            $validated['settings']['soft_disk_quota'] = $softLimitBytes;
        }

        if (isset($validated['settings'])) {
            $validated['settings'] = array_merge($team->settings ?? [], $validated['settings']);
            
            if (!$isAdmin) {
                $validated['settings']['has_whatsapp'] = $team->settings['has_whatsapp'] ?? false;
                $validated['settings']['has_appointments'] = $team->settings['has_appointments'] ?? false;
                $validated['settings']['surveys_enabled'] = $team->settings['surveys_enabled'] ?? false;
            } else {
                $validated['settings']['has_whatsapp'] = filter_var($validated['settings']['has_whatsapp'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $validated['settings']['has_appointments'] = filter_var($validated['settings']['has_appointments'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $validated['settings']['microsites_enabled'] = filter_var($validated['settings']['microsites_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $validated['settings']['surveys_enabled'] = filter_var($validated['settings']['surveys_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
            }
        }

        $validated['slug'] = Str::slug($validated['name']);
        
        $team->update($validated);
        
        return $team;
    }
}
