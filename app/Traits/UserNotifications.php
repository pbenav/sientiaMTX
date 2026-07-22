<?php

namespace App\Traits;

trait UserNotifications
{
    /**
     * Determine if the user wants to be notified via a specific channel.
     */
    public function wantsNotification(string $channel, string $priority = 'medium'): bool
    {
        $settings = $this->notification_settings ?? $this->defaultNotificationSettings();
        
        // Channel disabled?
        if (!($settings[$channel] ?? false)) {
            return false;
        }

        // Safeguard: Individual WhatsApp notifications strictly require authorized permission
        if ($channel === 'whatsapp' && !($settings['whatsapp_personal_allowed'] ?? false)) {
            return false;
        }

        // Quiet hours check
        if ($this->isInQuietHours()) {
            return false;
        }

        // Priority filter (High/Critical always pass if channel is on, low only if specifically allowed)
        // For simplicity, we'll assume the channel setting implies permission unless it's a "loud" channel
        return true;
    }

    /**
     * Check if user is currently in their "Quiet Hours".
     */
    public function isInQuietHours(): bool
    {
        $settings = $this->notification_settings ?? $this->defaultNotificationSettings();
        
        if (!($settings['quiet_hours_enabled'] ?? false)) {
            return false;
        }

        $start = $settings['quiet_hours_start'] ?? '22:00';
        $end = $settings['quiet_hours_end'] ?? '08:00';
        
        $siteTimezone = config('app.timezone', 'UTC');
        $now = now($this->timezone ?? $siteTimezone)->format('H:i');

        if ($start <= $end) {
            return $now >= $start && $now < $end;
        } else {
            // Overlapping midnight (e.g. 22:00 to 08:00)
            return $now >= $start || $now < $end;
        }
    }

    /**
     * Default notification settings for new users or if not set.
     */
    public function defaultNotificationSettings(): array
    {
        $siteTimezone = config('app.timezone', 'UTC');
        return [
            'mail' => true,
            'web_push' => false,
            'telegram' => false,
            'whatsapp' => false,
            'sync_chats' => false,
            'quiet_hours_enabled' => true,
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '08:00',
            'notify_before_hours' => 2,
            'notify_scheduled_tasks' => true,
            'notify_scheduled_before_minutes' => 15,
            'morning_summary' => true,
            'morning_summary_time' => '08:00',
            'morning_summary_weekends' => true,
            'chat_sounds' => true,
            'timezone' => $siteTimezone,
        ];
    }
}
