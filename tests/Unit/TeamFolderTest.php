<?php

namespace Tests\Unit;

use App\Models\TeamFolder;
use Tests\TestCase;

class TeamFolderTest extends TestCase
{
    public function test_get_color_styles_returns_expected_classes(): void
    {
        $folder = new TeamFolder(['color' => 'blue']);
        $styles = $folder->getColorStyles();

        $this->assertArrayHasKey('bg', $styles);
        $this->assertArrayHasKey('text', $styles);
        $this->assertArrayHasKey('border', $styles);
        $this->assertStringContainsString('blue', $styles['text']);
    }

    public function test_get_color_styles_fallback_to_violet(): void
    {
        $folder = new TeamFolder(['color' => 'non_existent_color']);
        $styles = $folder->getColorStyles();

        $this->assertStringContainsString('violet', $styles['text']);
    }

    public function test_can_move_to_prevents_moving_to_itself(): void
    {
        $folder = new TeamFolder(['team_id' => 1]);
        $folder->id = 10;

        $this->assertFalse($folder->canMoveTo($folder));
        $this->assertTrue($folder->canMoveTo(null)); // A la raíz siempre se puede
    }

    public function test_can_move_to_prevents_moving_to_different_team(): void
    {
        $folder1 = new TeamFolder(['team_id' => 1]);
        $folder1->id = 1;
        $folder2 = new TeamFolder(['team_id' => 2]);
        $folder2->id = 2;

        $this->assertFalse($folder1->canMoveTo($folder2));
    }

    public function test_can_move_to_valid_target_in_same_team(): void
    {
        $folder1 = new TeamFolder(['team_id' => 1]);
        $folder1->id = 1;
        $folder2 = new TeamFolder(['team_id' => 1]);
        $folder2->id = 2;
        $folder1->setRelation('children', collect());

        $this->assertTrue($folder1->canMoveTo($folder2));
    }

    public function test_folder_visibility_helpers(): void
    {
        $publicFolder = new TeamFolder(['visibility' => 'public']);
        $this->assertTrue($publicFolder->isPublic());
        $this->assertFalse($publicFolder->isPrivate());

        $privateFolder = new TeamFolder(['visibility' => 'private']);
        $this->assertTrue($privateFolder->isPrivate());
        $this->assertFalse($privateFolder->isPublic());
    }

    public function test_public_folder_is_visible_to_any_user(): void
    {
        $folder = new TeamFolder(['visibility' => 'public']);
        $user = new \App\Models\User();
        $user->id = 10;

        $this->assertTrue($folder->isVisibleTo($user));
        $this->assertTrue($folder->isVisibleTo(null));
    }

    public function test_private_folder_is_only_visible_to_creator_and_manager(): void
    {
        $folder = new TeamFolder(['visibility' => 'private', 'created_by_id' => 5]);

        $creator = new \App\Models\User();
        $creator->id = 5;

        $otherUser = new \App\Models\User();
        $otherUser->id = 99;

        // Creador siempre tiene acceso
        $this->assertTrue($folder->isVisibleTo($creator));

        // Otro usuario normal no tiene acceso
        $this->assertFalse($folder->isVisibleTo($otherUser, false));

        // Manager del equipo tiene acceso
        $this->assertTrue($folder->isVisibleTo($otherUser, true));

        // Usuario nulo no tiene acceso
        $this->assertFalse($folder->isVisibleTo(null));
    }

    public function test_policy_allows_team_owner_and_creator_to_update_and_delete(): void
    {
        $policy = new \App\Policies\TeamFolderPolicy();

        $owner = new \App\Models\User();
        $owner->id = 1;

        $team = new \App\Models\Team(['name' => 'Test Team']);
        $team->id = 10;
        $team->created_by_id = $owner->id;

        $publicFolder = new TeamFolder(['visibility' => 'public', 'created_by_id' => $owner->id]);
        $publicFolder->setRelation('team', $team);

        // Owner can update and delete
        $this->assertTrue($policy->update($owner, $publicFolder));
        $this->assertTrue($policy->delete($owner, $publicFolder));
        $this->assertTrue($policy->create($owner, $team));

        // Private folder can be updated by its creator
        $creator = new \App\Models\User();
        $creator->id = 2;
        $creatorFolder = new TeamFolder(['visibility' => 'private', 'created_by_id' => $creator->id]);
        $creatorFolder->setRelation('team', $team);

        // Admin can update
        $admin = new \App\Models\User();
        $admin->id = 99;
        $admin->is_admin = true;
        $this->assertTrue($policy->update($admin, $creatorFolder));
        $this->assertTrue($policy->delete($admin, $creatorFolder));
    }

    public function test_folder_links_relationship(): void
    {
        $folder = new TeamFolder();
        $relation = $folder->links();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $relation);
        $this->assertEquals('folder_id', $relation->getForeignKeyName());
    }
}

