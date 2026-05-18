<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Models\EntityShare;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntitySharePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_delete_their_share(): void
    {
        $owner = User::factory()->create();
        $friend = User::factory()->create();
        $note = Note::factory()->create(['user_id' => $owner->id]);

        $share = EntityShare::create([
            'owner_id' => $owner->id,
            'friend_id' => $friend->id,
            'entity_id' => $note->id,
            'entity_type' => 'note',
        ]);

        $this->assertTrue($owner->can('delete', $share));
    }

    public function test_recipient_can_delete_share_they_received(): void
    {
        $owner = User::factory()->create();
        $friend = User::factory()->create();
        $note = Note::factory()->create(['user_id' => $owner->id]);

        $share = EntityShare::create([
            'owner_id' => $owner->id,
            'friend_id' => $friend->id,
            'entity_id' => $note->id,
            'entity_type' => 'note',
        ]);

        $this->assertTrue($friend->can('delete', $share));
    }

    public function test_unrelated_user_cannot_delete_share(): void
    {
        $owner = User::factory()->create();
        $friend = User::factory()->create();
        $other = User::factory()->create();
        $note = Note::factory()->create(['user_id' => $owner->id]);

        $share = EntityShare::create([
            'owner_id' => $owner->id,
            'friend_id' => $friend->id,
            'entity_id' => $note->id,
            'entity_type' => 'note',
        ]);

        $this->assertFalse($other->can('delete', $share));
    }

    public function test_guest_user_cannot_delete_share(): void
    {
        $owner = User::factory()->create();
        $guest = User::factory()->create([
            'tier' => 'guest',
            'guest_created_at' => now(),
            'guest_expires_at' => now()->addDay(),
        ]);
        $note = Note::factory()->create(['user_id' => $owner->id]);

        $share = EntityShare::create([
            'owner_id' => $owner->id,
            'friend_id' => $guest->id,
            'entity_id' => $note->id,
            'entity_type' => 'note',
        ]);

        $this->assertFalse($guest->can('delete', $share));
    }

    public function test_guest_user_cannot_create_share(): void
    {
        $guest = User::factory()->create([
            'tier' => 'guest',
            'guest_created_at' => now(),
            'guest_expires_at' => now()->addDay(),
        ]);

        $this->assertFalse($guest->can('create', EntityShare::class));
    }

    public function test_non_guest_can_create_share(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->can('create', EntityShare::class));
    }
}
