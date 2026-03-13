<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Models\DiaryEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiaryEntryPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_their_entry(): void
    {
        $user = User::factory()->create();
        $entry = DiaryEntry::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('view', $entry));
    }

    public function test_non_owner_cannot_view_private_entry(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $entry = DiaryEntry::factory()->create(['user_id' => $owner->id, 'is_public' => false]);

        $this->assertFalse($other->can('view', $entry));
    }

    public function test_non_owner_can_view_public_entry(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $entry = DiaryEntry::factory()->create(['user_id' => $owner->id, 'is_public' => true]);

        $this->assertTrue($other->can('view', $entry));
    }

    public function test_owner_can_update_their_entry(): void
    {
        $user = User::factory()->create();
        $entry = DiaryEntry::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('update', $entry));
    }

    public function test_non_owner_cannot_update_entry(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $entry = DiaryEntry::factory()->create(['user_id' => $owner->id]);

        $this->assertFalse($other->can('update', $entry));
    }

    public function test_owner_can_delete_their_entry(): void
    {
        $user = User::factory()->create();
        $entry = DiaryEntry::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('delete', $entry));
    }

    public function test_non_owner_cannot_delete_entry(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $entry = DiaryEntry::factory()->create(['user_id' => $owner->id]);

        $this->assertFalse($other->can('delete', $entry));
    }

    public function test_any_auth_user_can_create(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->can('create', DiaryEntry::class));
    }
}
