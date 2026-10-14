<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Team;
use App\Models\TeamFolder;
use App\Services\Library\LibraryFolderService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador para la biblioteca de documentos y navegador de archivos del equipo.
 */
class TeamLibraryController extends Controller
{
    protected LibraryFolderService $folderService;

    public function __construct(LibraryFolderService $folderService)
    {
        $this->folderService = $folderService;
    }

    /**
     * Muestra el explorador de archivos y biblioteca del equipo.
     */
    public function index(Team $team, Request $request): View
    {
        $this->authorize('view', $team);

        $isManager = $team->isManager(auth()->user());

        // 1. Carpeta actual y navegación jerárquica
        $folderId = $request->query('folder');
        $currentFolder = null;
        $breadcrumbs = [];

        if ($folderId) {
            $currentFolder = TeamFolder::where('team_id', $team->id)->findOrFail($folderId);
            if (!$currentFolder->isVisibleTo(auth()->user(), $isManager)) {
                abort(403, __('No tienes permisos para acceder a esta carpeta privada.'));
            }
            $breadcrumbs = $currentFolder->getBreadcrumbs();
        }

        // 2. Subcarpetas en el nivel actual (filtradas por visibilidad con conteo seguro)
        $subfolders = TeamFolder::where('team_id', $team->id)
            ->where('parent_id', $currentFolder?->id)
            ->visibleTo(auth()->user(), $isManager)
            ->withCount([
                'documents' => fn($q) => $q->visibleTo(auth()->user(), $isManager),
                'children'  => fn($q) => $q->visibleTo(auth()->user(), $isManager),
            ])
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        // 3. Documentos en el nivel actual (o resultado de búsqueda)
        $query = Activity::where('team_id', $team->id)
            ->where('type', 'document')
            ->visibleTo(auth()->user(), $isManager);

        $searchQuery = trim($request->input('q', ''));
        $searchScope = $request->input('scope', 'folder'); // 'folder' | 'all'

        if ($searchQuery !== '') {
            $searchTerm = '%' . $searchQuery . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'LIKE', $searchTerm)
                  ->orWhere('description', 'LIKE', $searchTerm)
                  ->orWhere('metadata', 'LIKE', $searchTerm)
                  ->orWhereHas('notes', function ($nq) use ($searchTerm) {
                      $nq->where('content', 'LIKE', $searchTerm);
                  });
            });

            // Si se busca solo dentro de la carpeta actual y no globalmente:
            if ($searchScope === 'folder') {
                $query->where('folder_id', $currentFolder?->id);
            }
        } else {
            // Sin búsqueda: mostrar los documentos de la carpeta actual (o raíz)
            $query->where('folder_id', $currentFolder?->id);
        }

        if ($request->filled('status')) {
            $query->where('status->value', $request->input('status'));
        }

        $dateFrom = $request->filled('date_from') ? $request->input('date_from') : '1970-01-01';
        $dateTo = $request->filled('date_to') ? $request->input('date_to') : Carbon::today()->toDateString();

        $query->whereDate('created_at', '>=', $dateFrom);
        $query->whereDate('created_at', '<=', $dateTo);

        $documents = $query->with(['creator', 'attachments', 'folder'])
            ->orderBy('is_archived', 'asc') // Activos primero
            ->orderBy('created_at', 'desc')
            ->get();

        // 4. Documento activo para vista de lectura (Reader / Wiki)
        $activeDocumentId = $request->get('doc');
        $activeDocument = null;
        if ($activeDocumentId) {
            $activeDocument = Activity::where('team_id', $team->id)
                ->where('type', 'document')
                ->visibleTo(auth()->user(), $isManager)
                ->with(['attachments', 'notes', 'tags', 'creator'])
                ->find($activeDocumentId);
        }

        // Si no se especifica y el usuario está en modo lector (o primer doc disponible)
        $mode = $request->query('mode', 'browser'); // 'browser' | 'reader'
        if ($mode === 'reader' && !$activeDocument && $documents->isNotEmpty()) {
            $activeDocument = $documents->first();
        }

        // 5. Opciones para el selector de movimiento de carpetas (filtradas por visibilidad)
        $folderOptions = $this->folderService->getFolderOptions($team, null, auth()->user(), $isManager);

        // 6. Estadísticas globales del equipo para la cabecera (filtradas por visibilidad)
        $totalTeamDocuments = Activity::where('team_id', $team->id)
            ->where('type', 'document')
            ->visibleTo(auth()->user(), $isManager)
            ->count();
        $totalTeamFolders = TeamFolder::where('team_id', $team->id)
            ->visibleTo(auth()->user(), $isManager)
            ->count();

        return view('teams.library.index', compact(
            'team',
            'currentFolder',
            'breadcrumbs',
            'subfolders',
            'documents',
            'activeDocument',
            'mode',
            'searchQuery',
            'searchScope',
            'folderOptions',
            'totalTeamDocuments',
            'totalTeamFolders',
            'dateFrom',
            'dateTo'
        ));
    }
}
