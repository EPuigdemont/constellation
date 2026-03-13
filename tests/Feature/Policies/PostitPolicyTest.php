<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Models\Postit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostitPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_their_postit(): void
    {
        $user = User::factory()->create();
        $postit = Postit::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('view', $postit));
    }

    public function test_non_owner_cannot_view_private_postit(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $postit = Postit::factory()->create(['user_id' => $owner->id, 'is_public' => false]);

        $this->assertFalse($other->can('view', $postit));
    }

    public function test_non_owner_can_view_public_postit(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $postit = Postit::factory()->create(['user_id' => $owner->id, 'is_public' => true]);

        $this->assertTrue($other->can('view', $postit));
    }

    public function test_owner_can_update_their_postit(): void
    {
        $user = User::factory()->create();
        $postit = Postit::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('update', $postit));
    }

    public function test_non_owner_cannot_update_postit(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $postit = Postit::factory()->create(['user_id' => $owner->id]);

        $this->assertFalse($other->can('update', $postit));
    }

    public function test_owner_can_delete_their_postit(): void
    {
        $user = User::factory()->create();
        $postit = Postit::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('delete', $postit));
    }

    public function test_non_owner_cannot_delete_postit(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $postit = Postit::factory()->create(['user_id' => $owner->id]);

        $this->assertFalse($other->can('delete', $postit));
    }

    public function test_any_auth_user_can_create(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->can('create', Postit::class));
    }
}
