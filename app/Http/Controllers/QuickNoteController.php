<?php

namespace App\Http\Controllers;

use App\Models\QuickNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QuickNoteController extends Controller
{
    public function index()
    {
        return response()->json(auth()->user()->quickNotes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => 'nullable|string',
            'position_x' => 'integer',
            'position_y' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'color' => 'string',
        ]);

        $note = auth()->user()->quickNotes()->create($validated);

        return response()->json($note);
    }

    public function update(Request $request, QuickNote $quickNote)
    {
        $this->authorize('update', $quickNote);

        $validated = $request->validate([
            'content' => 'nullable|string',
            'position_x' => 'integer',
            'position_y' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'color' => 'string',
            'is_pinned' => 'boolean',
            'is_minimized' => 'boolean',
        ]);

        $quickNote->update($validated);

        return response()->json($quickNote);
    }

    public function destroy(QuickNote $quickNote)
    {
        $this->authorize('delete', $quickNote);
        
        // Clean up attachments if any
        if ($quickNote->attachments) {
            foreach ($quickNote->attachments as $attachment) {
                if (isset($attachment['path'])) {
                    Storage::disk('public')->delete($attachment['path']);
                }
            }
        }

        $quickNote->delete();

        return response()->json(['success' => true]);
    }

    public function uploadAttachment(Request $request, QuickNote $quickNote)
    {
        $this->authorize('update', $quickNote);

        $request->validate([
            'file' => 'required|file|max:10240', // 10MB limit
        ]);

        $file = $request->file('file');
        $path = $file->store('quick-notes/' . auth()->id(), 'public');
        
        $attachments = $quickNote->attachments ?? [];
        $attachments[] = [
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'url' => Storage::url($path),
            'type' => $file->getMimeType(),
            'created_at' => now(),
        ];

        $quickNote->update(['attachments' => $attachments]);

        return response()->json($quickNote);
    }
}
