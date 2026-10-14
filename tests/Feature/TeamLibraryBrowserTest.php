<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Team;
use App\Models\TeamFolder;
use App\Models\User;
use App\Services\Library\LibraryFolderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class TeamLibraryBrowserTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Team $team;
    protected LibraryFolderService $folderService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->team = Team::create([
            'name' => 'Equipo de Pruebas',
            'created_by_id' => $this->user->id,
            'personal_team' => false,
        ]);
        $this->team->members()->syncWithoutDetaching([$this->user->id => ['role_id' => 1]]);

        $this->folderService = new LibraryFolderService();
    }

    public function test_user_can_view_library_browser_root(): void
    {
        $folder = $this->folderService->createFolder($this->team, $this->user, [
            'name' => 'Carpeta Principal',
            'color' => 'violet',
        ]);

        $doc = Activity::create([
            'title' => 'Documento en Raíz',
            'team_id' => $this->team->id,
            'created_by_id' => $this->user->id,
            'type' => 'document',
            'priority' => 'medium',
            'visibility' => 'public',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('teams.library', $this->team));

        $response->assertStatus(200);
        $response->assertSee('Carpeta Principal');
        $response->assertSee('Documento en Raíz');
    }

    public function test_user_can_create_subfolder_and_navigate_into_it(): void
    {
        $parentFolder = $this->folderService->createFolder($this->team, $this->user, [
            'name' => 'Padre',
        ]);

        $subfolder = $this->folderService->createFolder($this->team, $this->user, [
            'name' => 'Subcarpeta Hija',
            'parent_id' => $parentFolder->id,
            'color' => 'blue',
        ]);

        $docInside = Activity::create([
            'title' => 'Documento Anidado',
            'team_id' => $this->team->id,
            'created_by_id' => $this->user->id,
            'folder_id' => $parentFolder->id,
            'type' => 'document',
            'priority' => 'medium',
            'visibility' => 'public',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('teams.library', [$this->team, 'folder' => $parentFolder->id]));

        $response->assertStatus(200);
        $response->assertSee('Subcarpeta Hija');
        $response->assertSee('Documento Anidado');
        $response->assertSee('Padre'); // En breadcrumb
    }

    public function test_user_can_move_document_to_folder_and_back_to_root(): void
    {
        $folder = $this->folderService->createFolder($this->team, $this->user, [
            'name' => 'Destino',
        ]);

        $doc = Activity::create([
            'title' => 'Doc Movil',
            'team_id' => $this->team->id,
            'created_by_id' => $this->user->id,
            'type' => 'document',
            'priority' => 'medium',
            'visibility' => 'public',
        ]);

        $this->assertNull($doc->folder_id);

        // Mover a la carpeta
        $this->actingAs($this->user)
            ->postJson(route('teams.library.move', $this->team), [
                'type' => 'document',
                'id' => $doc->id,
                'target_folder_id' => $folder->id,
            ])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals($folder->id, $doc->fresh()->folder_id);

        // Mover de vuelta a la raíz
        $this->actingAs($this->user)
            ->postJson(route('teams.library.move', $this->team), [
                'type' => 'document',
                'id' => $doc->id,
                'target_folder_id' => null,
            ])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertNull($doc->fresh()->folder_id);
    }

    public function test_cycle_prevention_when_moving_folders(): void
    {
        $folderA = $this->folderService->createFolder($this->team, $this->user, ['name' => 'A']);
        $folderB = $this->folderService->createFolder($this->team, $this->user, ['name' => 'B', 'parent_id' => $folderA->id]);
        $folderC = $this->folderService->createFolder($this->team, $this->user, ['name' => 'C', 'parent_id' => $folderB->id]);

        // Intentar mover A dentro de C (crearía un ciclo)
        $this->expectException(InvalidArgumentException::class);
        $this->folderService->moveFolder($folderA, $folderC->id);
    }

    public function test_delete_folder_preserves_documents_by_moving_to_parent(): void
    {
        $folder = $this->folderService->createFolder($this->team, $this->user, ['name' => 'Temporal']);

        $doc = Activity::create([
            'title' => 'Documento Salvado',
            'team_id' => $this->team->id,
            'created_by_id' => $this->user->id,
            'folder_id' => $folder->id,
            'type' => 'document',
            'priority' => 'medium',
            'visibility' => 'public',
        ]);

        $this->actingAs($this->user)
            ->delete(route('teams.library.folders.destroy', [$this->team, $folder]), [
                'delete_contents' => '0',
            ])
            ->assertRedirect();

        $this->assertSoftDeleted('team_folders', ['id' => $folder->id]);
        $this->assertNull($doc->fresh()->folder_id); // Promovido a la raíz
        $this->assertFalse($doc->fresh()->trashed()); // Sigue vivo
    }

    public function test_delete_folder_cascade_deletes_documents(): void
    {
        $folder = $this->folderService->createFolder($this->team, $this->user, ['name' => 'Para Eliminar']);

        $doc = Activity::create([
            'title' => 'Documento Descartable',
            'team_id' => $this->team->id,
            'created_by_id' => $this->user->id,
            'folder_id' => $folder->id,
            'type' => 'document',
            'priority' => 'medium',
            'visibility' => 'public',
        ]);

        $this->actingAs($this->user)
            ->delete(route('teams.library.folders.destroy', [$this->team, $folder]), [
                'delete_contents' => '1',
            ])
            ->assertRedirect();

        $this->assertSoftDeleted('team_folders', ['id' => $folder->id]);
        $this->assertTrue($doc->fresh()->trashed()); // Eliminado también
    }
}

