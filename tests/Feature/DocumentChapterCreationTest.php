<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentChapterCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_activity_created_with_first_chapter(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);

        $response = $this->actingAs($user)
            ->post(route('teams.activities.store', $team), [
                'type' => 'document',
                'title' => 'Mi Documento',
                'description' => 'Descripción',
                'visibility' => 'private',
                'priority' => 'medium',
                'metadata' => [
                    'chapter_title' => 'Introducción',
                    'chapter_content' => 'Este es el contenido del primer capítulo',
                    'version' => '1.0.0',
                ],
            ]);

        $response->assertRedirect();

        $activity = \App\Models\Activity::where('team_id', $team->id)->first();
        $this->assertNotNull($activity);
        $this->assertEquals('document', $activity->type);
        $this->assertEquals('Mi Documento', $activity->title);

        // Verificar que el primer capítulo se guardó
        $chapters = $activity->metadata['chapters'] ?? [];
        $this->assertCount(1, $chapters);
        $this->assertEquals('Introducción', $chapters[0]['title']);
        $this->assertEquals('Este es el contenido del primer capítulo', $chapters[0]['content']);
    }

    public function test_document_activity_created_without_chapter(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);

        $response = $this->actingAs($user)
            ->post(route('teams.activities.store', $team), [
                'type' => 'document',
                'title' => 'Mi Documento Sin Capítulos',
                'description' => 'Descripción',
                'visibility' => 'private',
                'priority' => 'medium',
                'metadata' => [
                    'version' => '1.0.0',
                ],
            ]);

        $response->assertRedirect();

        $activity = \App\Models\Activity::where('team_id', $team->id)->first();
        $this->assertNotNull($activity);
        $chapters = $activity->metadata['chapters'] ?? [];
        $this->assertCount(0, $chapters);
    }
}
