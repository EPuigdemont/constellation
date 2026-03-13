<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Models\Image;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImagePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_their_image(): void
    {
        $user = User::factory()->create();
        $image = Image::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('view', $image));
    }

    public function test_non_owner_cannot_view_private_image(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $image = Image::factory()->create(['user_id' => $owner->id, 'is_public' => false]);

        $this->assertFalse($other->can('view', $image));
    }

    public function test_non_owner_can_view_public_image(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $image = Image::factory()->create(['user_id' => $owner->id, 'is_public' => true]);

        $this->assertTrue($other->can('view', $image));
    }

    public function test_owner_can_update_their_image(): void
    {
        $user = User::factory()->create();
        $image = Image::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('update', $image));
    }

    public function test_non_owner_cannot_update_image(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $image = Image::factory()->create(['user_id' => $owner->id]);

        $this->assertFalse($other->can('update', $image));
    }

    public function test_owner_can_delete_their_image(): void
    {
        $user = User::factory()->create();
        $image = Image::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('delete', $image));
    }

    public function test_non_owner_cannot_delete_image(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $image = Image::factory()->create(['user_id' => $owner->id]);

        $this->assertFalse($other->can('delete', $image));
    }

    public function test_any_auth_user_can_create(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->can('create', Image::class));
    }
}
