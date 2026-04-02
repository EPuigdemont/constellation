<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Models\EntityShare;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_their_note(): void
    {
        $user = User::factory()->create();
        $note = Note::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('view', $note));
    }

    public function test_non_owner_cannot_view_unshared_note(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $note = Note::factory()->create(['user_id' => $owner->id]);

        $this->assertFalse($other->can('view', $note));
    }

    public function test_non_owner_can_view_shared_note(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $note = Note::factory()->create(['user_id' => $owner->id]);

        EntityShare::create([
            'owner_id' => $owner->id,
            'friend_id' => $other->id,
            'entity_id' => $note->id,
            'entity_type' => 'note',
        ]);

        $this->assertTrue($other->can('view', $note));
    }

    public function test_owner_can_update_their_note(): void
    {
        $user = User::factory()->create();
        $note = Note::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('update', $note));
    }

    public function test_non_owner_cannot_update_note(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $note = Note::factory()->create(['user_id' => $owner->id]);

        $this->assertFalse($other->can('update', $note));
    }

    public function test_owner_can_delete_their_note(): void
    {
        $user = User::factory()->create();
        $note = Note::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('delete', $note));
    }

    public function test_non_owner_cannot_delete_note(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $note = Note::factory()->create(['user_id' => $owner->id]);

        $this->assertFalse($other->can('delete', $note));
    }

    public function test_any_auth_user_can_create(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->can('create', Note::class));
    }
}
