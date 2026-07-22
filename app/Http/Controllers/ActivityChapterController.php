<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Team;
use Illuminate\Http\Request;
use App\Services\ActivityService;

class ActivityChapterController extends Controller
{
    protected $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    public function addChapter(Request $request, Team $team, Activity $activity)
    {
        if ($activity->team_id !== $team->id || $activity->type !== 'document') {
            return back()->with('warning', __('activities.chapters_only_documents'));
        }

        $request->validate([
            'chapter_title' => 'required|string|max:255',
            'chapter_content' => 'required|string',
        ]);

        $metadata = $activity->metadata ?? [];
        $chapters = $metadata['chapters'] ?? [];

        $newChapter = [
            'id' => uniqid('chap_'),
            'title' => $request->get('chapter_title'),
            'content' => $request->get('chapter_content'),
            'author_id' => auth()->id(),
            'author_name' => auth()->user()->name,
            'created_at' => now()->format('Y-m-d H:i:s'),
            'updated_at' => now()->format('Y-m-d H:i:s'),
        ];

        $chapters[] = $newChapter;
        $metadata['chapters'] = $chapters;

        // Auto-incrementar versión si existe
        if (isset($metadata['version'])) {
            $parts = explode('.', $metadata['version']);
            if (count($parts) === 3) {
                $parts[1] = ((int)$parts[1]) + 1;
                $metadata['version'] = implode('.', $parts);
            }
        } else {
            $metadata['version'] = '1.1.0';
        }

        $activity->metadata = $metadata;
        $activity->save();

        $activity->histories()->create([
            'user_id' => auth()->id(),
            'action' => \Illuminate\Support\Str::limit("Añadido capítulo: {$newChapter['title']}", 100),
        ]);

        return back()->withFragment('chapters-section')->with('success', __('activities.chapter_added'));
    }

    /**
     * Actualiza un capítulo existente en un documento.
     */
    public function updateChapter(Request $request, Team $team, Activity $activity, $chapterId)
    {
        if ($activity->team_id !== $team->id || $activity->type !== 'document') {
            return back()->with('warning', __('activities.invalid_operation'));
        }

        $request->validate([
            'chapter_title' => 'required|string|max:255',
            'chapter_content' => 'required|string',
        ]);

        $metadata = $activity->metadata ?? [];
        $chapters = $metadata['chapters'] ?? [];

        $found = false;
        foreach ($chapters as &$chapter) {
            if ($chapter['id'] === $chapterId) {
                $chapter['title'] = $request->get('chapter_title');
                $chapter['content'] = $request->get('chapter_content');
                $chapter['updated_at'] = now()->format('Y-m-d H:i:s');
                $chapter['updated_by_name'] = auth()->user()->name;
                $found = true;
                break;
            }
        }

        if (!$found) {
            return back()->with('error', __('activities.chapter_not_found'));
        }

        $metadata['chapters'] = $chapters;
        $activity->metadata = $metadata;
        $activity->save();

        $activity->histories()->create([
            'user_id' => auth()->id(),
            'action' => "Actualizado capítulo ID #{$chapterId}",
        ]);

        return back()->withFragment('chapters-section')->with('success', __('activities.chapter_updated'));
    }

    /**
     * Elimina un capítulo de un documento.
     */
    public function deleteChapter(Request $request, Team $team, Activity $activity, $chapterId)
    {
        if ($activity->team_id !== $team->id || $activity->type !== 'document') {
            return back()->with('warning', __('activities.invalid_operation'));
        }

        $metadata = $activity->metadata ?? [];
        $chapters = $metadata['chapters'] ?? [];

        $filtered = array_filter($chapters, function($chapter) use ($chapterId) {
            return $chapter['id'] !== $chapterId;
        });

        if (count($filtered) === count($chapters)) {
            return back()->with('error', __('activities.chapter_not_found'));
        }

        $metadata['chapters'] = array_values($filtered);
        $activity->metadata = $metadata;
        $activity->save();

        $activity->histories()->create([
            'user_id' => auth()->id(),
            'action' => "Eliminado capítulo ID #{$chapterId}",
        ]);

        return back()->withFragment('chapters-section')->with('success', __('activities.chapter_deleted'));
    }

    /**
     * Restaura los metadatos y configuraciones de la actividad desde su ancestro deprecado.
     */

}
