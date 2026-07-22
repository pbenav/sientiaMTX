<?php

namespace App\Traits;

trait UserStorage
{
    public function hasAvailableQuota(int $bytes): bool
    {
        return ($this->disk_used + $bytes) <= $this->disk_quota;
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
