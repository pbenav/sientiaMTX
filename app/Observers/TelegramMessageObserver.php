<?php

namespace App\Observers;

use App\Models\TelegramMessage;
use Illuminate\Support\Facades\Storage;

class TelegramMessageObserver
{
    /**
     * Handle the TelegramMessage "created" event.
     */
    public function created(TelegramMessage $message): void
    {
        if ($message->file_size > 0 && $message->team) {
            $message->team->increment('disk_used', $message->file_size);
            // Refresh usage and check for alerts
            $message->team->refresh()->checkStorageAlerts();
        }
    }

    /**
     * Handle the TelegramMessage "deleted" event.
     */
    public function deleted(TelegramMessage $message): void
    {
        if ($message->file_size > 0 && $message->team) {
            $message->team->decrement('disk_used', max(0, $message->file_size));
        }

        // Eliminar archivos físicos
        $paths = array_filter([$message->photo_path, $message->voice_path, $message->sticker_path]);
        foreach ($paths as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }
}
