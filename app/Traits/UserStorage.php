<?php

namespace App\Traits;

trait UserStorage
{
    public function hasAvailableQuota(int $bytes): bool
    {
        return ($this->disk_used + $bytes) <= $this->disk_quota;
    }

    /**
     * Sincroniza el campo disk_used del usuario sumando sus adjuntos en disco.
     */
    public function syncDiskUsed(): void
    {
        $taskAttSize = \App\Models\TaskAttachment::where('user_id', $this->id)
            ->where('storage_provider', '!=', 'google')
            ->sum('file_size');

        $actAttSize = \App\Models\ActivityAttachment::where('uploaded_by_id', $this->id)
            ->where('disk', '!=', 'google')
            ->sum('file_size');

        $this->update(['disk_used' => (int)($taskAttSize + $actAttSize)]);
    }

    /**
     * Get disk usage as percentage (0-100)
     */
    public function getDiskUsagePercentageAttribute(): int
    {
        if ($this->disk_quota <= 0) return 0;
        return (int) min(100, round(($this->disk_used / $this->disk_quota) * 100));
    }
}
