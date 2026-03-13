<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_any_user_can_view_system_tag(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->system()->create();

        $this->assertTrue($user->can('view', $tag));
    }

    public function test_any_user_can_view_user_tag(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($other->can('view', $tag));
    }

    public function test_system_tag_cannot_be_updated(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->system()->create();

        $this->assertFalse($user->can('update', $tag));
    }

    public function test_system_tag_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->system()->create();

        $this->assertFalse($user->can('delete', $tag));
    }

    public function test_owner_can_update_their_tag(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('update', $tag));
    }

    public function test_owner_can_delete_their_tag(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('delete', $tag));
    }

    public function test_non_owner_cannot_update_user_tag(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $owner->id]);

        $this->assertFalse($other->can('update', $tag));
    }

    public function test_non_owner_cannot_delete_user_tag(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $owner->id]);

        $this->assertFalse($other->can('delete', $tag));
    }

    public function test_any_auth_user_can_create(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->can('create', Tag::class));
    }
}
