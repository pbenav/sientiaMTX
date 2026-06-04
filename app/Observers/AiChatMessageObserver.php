<?php

namespace App\Observers;

use App\Models\AiChatMessage;
use Illuminate\Support\Facades\Storage;

class AiChatMessageObserver
{
    /**
     * Handle the AiChatMessage "deleting" event.
     */
    public function deleting(AiChatMessage $message): void
    {
        if ($message->file_path) {
            Storage::disk('public')->delete($message->file_path);
        }
    }
}
