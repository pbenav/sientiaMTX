<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Notifications;

use App\Models\Activity;
use App\Models\User;
use App\Traits\DeterminesNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class SignatureRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable, DeterminesNotificationChannels;

    public function __construct(
        protected Activity $activity,
        protected User $requestedBy
    ) {}

    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('✍️ Solicitud de firma: ' . $this->activity->title)
            ->icon('/images/logo-icon.png')
            ->body("{$this->requestedBy->name} necesita tu firma en el acuerdo \"{$this->activity->title}\".")
            ->action('Firmar ahora', 'view_activity')
            ->options(['TTL' => 1000]);
    }

    public function toTelegram(object $notifiable): array
    {
        $url = route('teams.activities.show', [$this->activity->team_id, $this->activity]);

        return [
            'text' => "✍️ *SOLICITUD DE FIRMA*\n\n" .
                      "*Acuerdo*: {$this->activity->title}\n" .
                      "*Solicitado por*: {$this->requestedBy->name}\n\n" .
                      "[Firmar en Sientia]({$url})"
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('teams.activities.show', [$this->activity->team_id, $this->activity]);

        return (new MailMessage)
            ->subject('Solicitud de firma: ' . $this->activity->title)
            ->greeting('Hola, ' . $notifiable->name . '.')
            ->line("{$this->requestedBy->name} requiere tu firma en el acuerdo \"{$this->activity->title}\".")
            ->action('Ir a firmar', $url)
            ->line('Accede a la plataforma y firma directamente desde la vista del acuerdo.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'activity_id'  => $this->activity->id,
            'title'        => $this->activity->title,
            'team_id'      => $this->activity->team_id,
            'team_name'    => $this->activity->team?->name,
            'assigned_by'  => $this->requestedBy->name,
            'type'         => 'signature_requested',
            'message'      => "{$this->requestedBy->name} requiere tu firma en \"{$this->activity->title}\".",
        ];
    }
}
