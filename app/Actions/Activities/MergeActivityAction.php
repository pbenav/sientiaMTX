<?php

namespace App\Actions\Activities;

use App\Models\Activity;

class MergeActivityAction
{
    /**
     * Fusiona las notas y adjuntos de una actividad fuente en una actividad objetivo.
     *
     * @param  Activity  $sourceActivity  Actividad fuente (se fusionarán sus datos)
     * @param  Activity  $targetActivity  Actividad objetivo (recibirá los datos)
     * @param  int  $userId  ID del usuario que realiza la fusión
     */
    public function execute(Activity $sourceActivity, Activity $targetActivity, int $userId): void
    {
        // Fusionar notas
        foreach ($sourceActivity->notes as $note) {
            $targetActivity->notes()->create([
                'user_id' => $note->user_id,
                'content' => $note->content . "\n[Heredado por fusión de la actividad #{$sourceActivity->id}]",
                'visibility' => $note->visibility ?? 'public',
            ]);
        }

        // Fusionar adjuntos
        foreach ($sourceActivity->attachments as $attachment) {
            $targetActivity->attachments()->create([
                'user_id' => $attachment->user_id,
                'name' => $attachment->name,
                'file_path' => $attachment->file_path,
                'file_type' => $attachment->file_type,
                'file_size' => $attachment->file_size,
            ]);
        }

        $targetActivity->histories()->create([
            'user_id' => $userId,
            'action' => 'merged_from_deprecated',
            'details' => json_encode(['from_activity_id' => $sourceActivity->id, 'from_uuid' => $sourceActivity->uuid])
        ]);
    }
}
