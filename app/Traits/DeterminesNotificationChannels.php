<?php

namespace App\Traits;

use App\Notifications\Channels\TelegramChannel;
use NotificationChannels\WebPush\WebPushChannel;

trait DeterminesNotificationChannels
{
    /**
     * Get the notification's delivery channels based on user preferences.
     *
     * @param  object  $notifiable
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Database is always enabled as the primary in-app indicator
        $channels = ['database'];

        // If the notifiable is a User, check their specific preferences
        if (method_exists($notifiable, 'wantsNotification')) {
            if ($notifiable->wantsNotification('mail')) {
                $channels[] = 'mail';
            }

            if ($notifiable->wantsNotification('telegram') && !empty($notifiable->telegram_chat_id)) {
                $channels[] = TelegramChannel::class;
            }

            if ($notifiable->wantsNotification('web_push')) {
                $channels[] = WebPushChannel::class;
            }
        } else {
            // Fallback for non-user notifiables (should reach mail by default)
            $channels[] = 'mail';
        }

        return $channels;
    }
}
