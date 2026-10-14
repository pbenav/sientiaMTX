<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>

namespace App\Services\Library;

use App\Models\Activity;
use App\Models\Team;
use App\Models\TeamFolder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Servicio de dominio para gestión de carpetas y organización en la biblioteca.
 */
class LibraryFolderService
{
    /**
     * Crea una nueva carpeta en el equipo.
     */
    public function createFolder(Team $team, User $user, array $data): TeamFolder
    {
        $parentId = $data['parent_id'] ?? null;
        $visibility = in_array($data['visibility'] ?? 'public', ['public', 'private']) ? $data['visibility'] : 'public';

        if ($parentId !== null) {
            $parent = TeamFolder::where('team_id', $team->id)->findOrFail($parentId);
            $parentId = $parent->id;
            // Herencia de privacidad: si la carpeta padre es privada, la subcarpeta nace privada
            if ($parent->isPrivate()) {
                $visibility = 'private';
            }
        }

        return DB::transaction(function () use ($team, $user, $data, $parentId, $visibility) {
            return TeamFolder::create([
                'team_id'       => $team->id,
                'created_by_id' => $user->id,
                'parent_id'     => $parentId,
                'name'          => trim($data['name']),
                'description'   => isset($data['description']) ? trim($data['description']) : null,
                'visibility'    => $visibility,
                'color'         => $data['color'] ?? 'violet',
                'icon'          => $data['icon'] ?? 'folder',
                'order'         => (int) ($data['order'] ?? 0),
            ]);
        });
    }

    /**
     * Actualiza metadatos de una carpeta.
     */
    public function updateFolder(TeamFolder $folder, array $data): TeamFolder
    {
        $fields = [
            'name'        => isset($data['name']) ? trim($data['name']) : $folder->name,
            'description' => array_key_exists('description', $data) ? trim($data['description'] ?? '') : $folder->description,
            'color'       => $data['color'] ?? $folder->color,
            'icon'        => $data['icon'] ?? $folder->icon,
            'order'       => isset($data['order']) ? (int) $data['order'] : $folder->order,
        ];

        if (isset($data['visibility']) && in_array($data['visibility'], ['public', 'private'])) {
            $fields['visibility'] = $data['visibility'];
        }

        $folder->update($fields);

        return $folder->fresh();
    }

    /**
     * Mueve una carpeta a otra carpeta padre o a la raíz.
     * Incluye detección y prevención de ciclos en el árbol jerárquico.
     */
    public function moveFolder(TeamFolder $folder, ?int $targetParentId): bool
    {
        $targetFolder = null;
        if ($targetParentId !== null) {
            $targetFolder = TeamFolder::where('team_id', $folder->team_id)->findOrFail($targetParentId);
        }

        if (!$folder->canMoveTo($targetFolder)) {
            throw new InvalidArgumentException(__('No se puede mover la carpeta dentro de sí misma o de una de sus subcarpetas.'));
        }

        return DB::transaction(function () use ($folder, $targetParentId) {
            $folder->parent_id = $targetParentId;
            return $folder->save();
        });
    }

    /**
     * Elimina una carpeta.
     * Si no se solicita borrado recursivo de contenidos, los elementos directos
     * (subcarpetas y documentos) se promueven a la carpeta padre para no perderlos.
     */
    public function deleteFolder(TeamFolder $folder, bool $deleteContents = false): void
    {
        DB::transaction(function () use ($folder, $deleteContents) {
            if ($deleteContents) {
                // Borrado recursivo en cascada
                foreach ($folder->children as $child) {
                    $this->deleteFolder($child, true);
                }
                // Borrar documentos dentro de la carpeta
                $folder->documents()->delete();
            } else {
                // Preservar contenidos: promover a la carpeta padre (o a la raíz si era null)
                $newParentId = $folder->parent_id;

                TeamFolder::where('parent_id', $folder->id)
                    ->update(['parent_id' => $newParentId]);

                Activity::where('folder_id', $folder->id)
                    ->where('type', 'document')
                    ->update(['folder_id' => $newParentId]);
            }

            $folder->delete();
        });
    }

    /**
     * Mueve un documento a una carpeta o a la raíz.
     */
    public function moveDocument(Activity $document, ?int $targetFolderId): bool
    {
        if ($document->type !== 'document') {
            throw new InvalidArgumentException(__('Solo se pueden organizar actividades de tipo documento en la biblioteca.'));
        }

        $targetFolder = null;
        if ($targetFolderId !== null) {
            // Verificar que la carpeta exista y pertenezca al mismo equipo
            $targetFolder = TeamFolder::where('team_id', $document->team_id)->findOrFail($targetFolderId);
        }

        $document->folder_id = $targetFolderId;
        // Si se mueve a una carpeta privada, hereda privacidad
        if ($targetFolder && $targetFolder->isPrivate()) {
            $document->visibility = 'private';
        }

        return $document->save();
    }

    /**
     * Mueve masivamente documentos y/o carpetas a una carpeta destino.
     *
     * @param Team $team
     * @param array<int> $documentIds
     * @param array<int> $folderIds
     * @param int|null $targetFolderId
     * @return array{moved_documents: int, moved_folders: int, errors: array<string>}
     */
    public function bulkMove(Team $team, array $documentIds, array $folderIds, ?int $targetFolderId): array
    {
        $targetFolder = null;
        if ($targetFolderId !== null) {
            $targetFolder = TeamFolder::where('team_id', $team->id)->findOrFail($targetFolderId);
        }

        $result = [
            'moved_documents' => 0,
            'moved_folders'   => 0,
            'errors'          => [],
        ];

        DB::transaction(function () use ($team, $documentIds, $folderIds, $targetFolder, $targetFolderId, &$result) {
            // Mover documentos
            if (!empty($documentIds)) {
                $docUpdate = ['folder_id' => $targetFolderId];
                if ($targetFolder && $targetFolder->isPrivate()) {
                    $docUpdate['visibility'] = 'private';
                }

                $count = Activity::where('team_id', $team->id)
                    ->where('type', 'document')
                    ->whereIn('id', $documentIds)
                    ->update($docUpdate);
                $result['moved_documents'] = $count;
            }

            // Mover carpetas
            if (!empty($folderIds)) {
                $folders = TeamFolder::where('team_id', $team->id)
                    ->whereIn('id', $folderIds)
                    ->get();

                foreach ($folders as $folder) {
                    if (!$folder->canMoveTo($targetFolder)) {
                        $result['errors'][] = __("No se pudo mover la carpeta ':name' para evitar ciclos de anidamiento.", ['name' => $folder->name]);
                        continue;
                    }
                    $folder->parent_id = $targetFolderId;
                    if ($targetFolder && $targetFolder->isPrivate()) {
                        $folder->visibility = 'private';
                    }
                    $folder->save();
                    $result['moved_folders']++;
                }
            }
        });

        return $result;
    }

    /**
     * Elimina masivamente documentos y/o carpetas.
     *
     * @param Team $team
     * @param array<int> $documentIds
     * @param array<int> $folderIds
     * @param bool $deleteContents
     * @return array{deleted_documents: int, deleted_folders: int}
     */
    public function bulkDelete(Team $team, array $documentIds, array $folderIds, bool $deleteContents = false): array
    {
        $result = [
            'deleted_documents' => 0,
            'deleted_folders'   => 0,
        ];

        DB::transaction(function () use ($team, $documentIds, $folderIds, $deleteContents, &$result) {
            // Eliminar documentos
            if (!empty($documentIds)) {
                $docs = Activity::where('team_id', $team->id)
                    ->where('type', 'document')
                    ->whereIn('id', $documentIds)
                    ->get();

                foreach ($docs as $doc) {
                    $doc->delete();
                    $result['deleted_documents']++;
                }
            }

            // Eliminar carpetas
            if (!empty($folderIds)) {
                $folders = TeamFolder::where('team_id', $team->id)
                    ->whereIn('id', $folderIds)
                    ->get();

                foreach ($folders as $folder) {
                    $this->deleteFolder($folder, $deleteContents);
                    $result['deleted_folders']++;
                }
            }
        });

        return $result;
    }

    /**
     * Obtiene el árbol completo de carpetas del equipo para selectores y modales de movimiento.
     *
     * @return array<int, array{id: int, name: string, level: int, path: string, color: string, visibility: string}>
     */
    public function getFolderOptions(Team $team, ?int $excludeDescendantsOf = null, ?User $user = null, bool $isManager = false): array
    {
        $excludedIds = [];
        if ($excludeDescendantsOf) {
            $excludedFolder = TeamFolder::where('team_id', $team->id)->find($excludeDescendantsOf);
            if ($excludedFolder) {
                $excludedIds = array_merge([$excludedFolder->id], $excludedFolder->getAllDescendantIds());
            }
        }

        $query = TeamFolder::where('team_id', $team->id)
            ->whereNotIn('id', $excludedIds)
            ->orderBy('name');

        if ($user) {
            $query->visibleTo($user, $isManager);
        }

        $allFolders = $query->get();

        $tree = [];
        $this->buildTreeOptions($allFolders, null, 0, '', $tree);

        return $tree;
    }

    /**
     * Helper recursivo para aplanar el árbol de carpetas con indentación y rutas.
     */
    protected function buildTreeOptions($folders, ?int $parentId, int $level, string $parentPath, array &$result): void
    {
        $children = $folders->where('parent_id', $parentId);

        foreach ($children as $folder) {
            $currentPath = $parentPath === '' ? $folder->name : $parentPath . ' / ' . $folder->name;
            $result[] = [
                'id'         => $folder->id,
                'name'       => $folder->name,
                'level'      => $level,
                'path'       => $currentPath,
                'color'      => $folder->color,
                'visibility' => $folder->visibility,
            ];

            $this->buildTreeOptions($folders, $folder->id, $level + 1, $currentPath, $result);
        }
    }
}

