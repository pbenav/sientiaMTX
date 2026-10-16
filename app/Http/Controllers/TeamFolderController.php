<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Team;
use App\Models\TeamFolder;
use App\Services\Library\LibraryFolderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Controlador para la gestión de carpetas y operaciones de movimiento en la biblioteca.
 */
class TeamFolderController extends Controller
{
    protected LibraryFolderService $folderService;

    public function __construct(LibraryFolderService $folderService)
    {
        $this->folderService = $folderService;
    }

    /**
     * Crea una nueva carpeta en la biblioteca del equipo.
     */
    public function store(Team $team, Request $request): JsonResponse|RedirectResponse
    {
        $this->authorize('view', $team);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'visibility'  => ['nullable', 'string', 'in:public,private'],
            'color'       => ['nullable', 'string', Rule::in(array_keys(TeamFolder::COLORS))],
            'parent_id'   => [
                'nullable',
                'integer',
                Rule::exists('team_folders', 'id')->where('team_id', $team->id),
            ],
        ]);

        $folder = $this->folderService->createFolder($team, auth()->user(), $validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Carpeta creada correctamente.'),
                'folder'  => $folder,
            ], 201);
        }

        return redirect()
            ->route('teams.library', [$team, 'folder' => $folder->id])
            ->with('success', __('Carpeta ":name" creada correctamente.', ['name' => $folder->name]));
    }

    /**
     * Actualiza nombre, descripción o color de una carpeta.
     */
    public function update(Team $team, TeamFolder $folder, Request $request): JsonResponse|RedirectResponse
    {
        $this->authorize('view', $team);

        if ($folder->team_id !== $team->id) {
            abort(404);
        }

        $this->authorize('update', $folder);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'visibility'  => ['nullable', 'string', 'in:public,private'],
            'color'       => ['nullable', 'string', Rule::in(array_keys(TeamFolder::COLORS))],
        ]);

        $updated = $this->folderService->updateFolder($folder, $validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Carpeta actualizada correctamente.'),
                'folder'  => $updated,
            ]);
        }

        return redirect()->back()
            ->with('success', __('Carpeta actualizada correctamente.'));
    }

    /**
     * Elimina una carpeta.
     */
    public function destroy(Team $team, TeamFolder $folder, Request $request): JsonResponse|RedirectResponse
    {
        $this->authorize('view', $team);

        if ($folder->team_id !== $team->id) {
            abort(404);
        }

        $this->authorize('delete', $folder);

        $deleteContents = $request->boolean('delete_contents', false);
        $parentId = $folder->parent_id;

        $this->folderService->deleteFolder($folder, $deleteContents);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Carpeta eliminada correctamente.'),
            ]);
        }

        return redirect()
            ->route('teams.library', array_filter([$team, 'folder' => $parentId]))
            ->with('success', __('Carpeta eliminada correctamente.'));
    }

    /**
     * Mueve una carpeta o un documento a otra carpeta (o a la raíz).
     */
    public function move(Team $team, Request $request): JsonResponse|RedirectResponse
    {
        $this->authorize('view', $team);

        $validated = $request->validate([
            'type'             => ['required', 'string', 'in:folder,document,link'],
            'id'               => ['required', 'integer'],
            'target_folder_id' => [
                'nullable',
                'integer',
                Rule::exists('team_folders', 'id')->where('team_id', $team->id),
            ],
        ]);

        $targetFolderId = $validated['target_folder_id'] ?? null;

        try {
            if ($validated['type'] === 'folder') {
                $folder = TeamFolder::where('team_id', $team->id)->findOrFail($validated['id']);
                $this->folderService->moveFolder($folder, $targetFolderId);
                $message = __('Carpeta movida correctamente.');
            } else {
                $activity = Activity::where('team_id', $team->id)
                    ->whereIn('type', ['document', 'link'])
                    ->findOrFail($validated['id']);
                $this->folderService->moveDocument($activity, $targetFolderId);
                $message = $activity->type === 'link'
                    ? __('Enlace movido correctamente.')
                    : __('Documento movido correctamente.');
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return redirect()->back()->with('success', $message);
        } catch (InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mueve masivamente documentos y/o carpetas a una carpeta destino.
     */
    public function bulkMove(Team $team, Request $request): JsonResponse|RedirectResponse
    {
        $this->authorize('view', $team);

        $validated = $request->validate([
            'document_ids'     => ['nullable', 'array'],
            'document_ids.*'   => ['integer'],
            'folder_ids'       => ['nullable', 'array'],
            'folder_ids.*'     => ['integer'],
            'target_folder_id' => [
                'nullable',
                'integer',
                Rule::exists('team_folders', 'id')->where('team_id', $team->id),
            ],
        ]);

        $documentIds = $validated['document_ids'] ?? [];
        $folderIds = $validated['folder_ids'] ?? [];
        $targetFolderId = isset($validated['target_folder_id']) && $validated['target_folder_id'] !== ''
            ? (int) $validated['target_folder_id']
            : null;

        if (empty($documentIds) && empty($folderIds)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('No se seleccionó ningún elemento para mover.'),
                ], 422);
            }
            return redirect()->back()->with('error', __('No se seleccionó ningún elemento para mover.'));
        }

        $result = $this->folderService->bulkMove($team, $documentIds, $folderIds, $targetFolderId);

        $totalMoved = $result['moved_documents'] + $result['moved_folders'];
        $message = __(':count elementos movidos correctamente.', ['count' => $totalMoved]);
        if (!empty($result['errors'])) {
            $message .= ' ' . implode(' ', $result['errors']);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'result'  => $result,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Elimina masivamente documentos y/o carpetas.
     */
    public function bulkDelete(Team $team, Request $request): JsonResponse|RedirectResponse
    {
        $this->authorize('view', $team);

        $validated = $request->validate([
            'document_ids'    => ['nullable', 'array'],
            'document_ids.*'  => ['integer'],
            'folder_ids'      => ['nullable', 'array'],
            'folder_ids.*'    => ['integer'],
            'delete_contents' => ['nullable', 'boolean'],
        ]);

        $documentIds = $validated['document_ids'] ?? [];
        $folderIds = $validated['folder_ids'] ?? [];
        $deleteContents = $request->boolean('delete_contents', false);

        if (empty($documentIds) && empty($folderIds)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('No se seleccionó ningún elemento para eliminar.'),
                ], 422);
            }
            return redirect()->back()->with('error', __('No se seleccionó ningún elemento para eliminar.'));
        }

        $result = $this->folderService->bulkDelete($team, $documentIds, $folderIds, $deleteContents);

        $totalDeleted = $result['deleted_documents'] + $result['deleted_folders'];
        $message = __(':count elementos eliminados correctamente.', ['count' => $totalDeleted]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'result'  => $result,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Retorna el árbol de carpetas del equipo para selectores y modales de movimiento.
     */
    public function tree(Team $team, Request $request): JsonResponse
    {
        $this->authorize('view', $team);

        $excludeId = $request->query('exclude_descendants_of');
        $options = $this->folderService->getFolderOptions(
            $team,
            $excludeId ? (int) $excludeId : null,
            auth()->user(),
            $team->isManager(auth()->user())
        );

        return response()->json([
            'success' => true,
            'options' => $options,
        ]);
    }
}

