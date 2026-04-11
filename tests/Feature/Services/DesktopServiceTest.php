<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Enums\Tier;
use App\Models\DiaryEntry;
use App\Models\EntityPosition;
use App\Models\EntityShare;
use App\Models\Image;
use App\Models\Note;
use App\Models\Postit;
use App\Models\User;
use App\Services\DesktopService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesktopServiceTest extends TestCase
{
    use RefreshDatabase;

    private DesktopService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DesktopService;
    }

    public function test_load_cards_returns_user_entities_with_positions(): void
    {
        $user = User::factory()->create();
        $diary = DiaryEntry::factory()->create(['user_id' => $user->id]);

        EntityPosition::create([
            'user_id' => $user->id,
            'entity_id' => $diary->id,
            'entity_type' => 'diary_entry',
            'x' => 100.0,
            'y' => 200.0,
            'z_index' => 1,
        ]);

        $cards = $this->service->loadCards($user);

        $this->assertNotEmpty($cards);

        $diaryCard = collect($cards)->firstWhere('id', $diary->id);
        $this->assertNotNull($diaryCard);
        $this->assertEquals('diary_entry', $diaryCard['type']);
        $this->assertEquals(100.0, $diaryCard['x']);
        $this->assertEquals(200.0, $diaryCard['y']);
        $this->assertEquals(1, $diaryCard['z_index']);
    }

    public function test_load_cards_includes_shared_entities(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $sharedNote = Note::factory()->create([
            'user_id' => $other->id,
        ]);

        // Share the note with the user
        EntityShare::create([
            'owner_id' => $other->id,
            'friend_id' => $user->id,
            'entity_id' => $sharedNote->id,
            'entity_type' => 'note',
        ]);

        $cards = $this->service->loadCards($user);

        $found = collect($cards)->firstWhere('id', $sharedNote->id);
        $this->assertNotNull($found);
        $this->assertSame($other->id, $found['owner_id']);
    }

    public function test_load_cards_excludes_unshared_entities_from_other_users(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $unsharedNote = Note::factory()->create([
            'user_id' => $other->id,
        ]);

        $cards = $this->service->loadCards($user);

        $found = collect($cards)->firstWhere('id', $unsharedNote->id);
        $this->assertNull($found);
    }

    public function test_load_cards_does_not_duplicate_shared_entities_owned_by_user(): void
    {
        $user = User::factory()->create();
        $note = Note::factory()->create([
            'user_id' => $user->id,
        ]);

        EntityShare::create([
            'owner_id' => $user->id,
            'friend_id' => $user->id,
            'entity_id' => $note->id,
            'entity_type' => 'note',
        ]);

        $cards = $this->service->loadCards($user);

        $matches = collect($cards)->where('id', $note->id);

        $this->assertCount(1, $matches);
    }

    public function test_save_position_creates_entity_position(): void
    {
        $user = User::factory()->create();
        $postit = Postit::factory()->create(['user_id' => $user->id]);

        $this->service->savePosition($user, $postit->id, 'postit', 50.0, 75.0, 3);

        $this->assertDatabaseHas('entity_positions', [
            'user_id' => $user->id,
            'entity_id' => $postit->id,
            'entity_type' => 'postit',
            'x' => 50.0,
            'y' => 75.0,
            'z_index' => 3,
        ]);
    }

    public function test_save_position_updates_existing_position(): void
    {
        $user = User::factory()->create();
        $postit = Postit::factory()->create(['user_id' => $user->id]);

        $this->service->savePosition($user, $postit->id, 'postit', 50.0, 75.0, 1);
        $this->service->savePosition($user, $postit->id, 'postit', 150.0, 275.0, 5);

        $this->assertDatabaseCount('entity_positions', 1);
        $this->assertDatabaseHas('entity_positions', [
            'entity_id' => $postit->id,
            'x' => 150.0,
            'y' => 275.0,
            'z_index' => 5,
        ]);
    }

    public function test_next_z_index_returns_max_plus_one(): void
    {
        $user = User::factory()->create();
        $diary = DiaryEntry::factory()->create(['user_id' => $user->id]);

        EntityPosition::create([
            'user_id' => $user->id,
            'entity_id' => $diary->id,
            'entity_type' => 'diary_entry',
            'x' => 0,
            'y' => 0,
            'z_index' => 10,
        ]);

        $this->assertEquals(11, $this->service->nextZIndex($user));
    }

    public function test_next_z_index_returns_one_when_no_positions(): void
    {
        $user = User::factory()->create();

        $this->assertEquals(1, $this->service->nextZIndex($user));
    }

    public function test_save_zoom_updates_user_record(): void
    {
        $user = User::factory()->create(['desktop_zoom' => 1.0]);

        $this->service->saveZoom($user, 1.5);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'desktop_zoom' => 1.5,
        ]);
    }

    public function test_assign_default_position_creates_staggered_position(): void
    {
        $user = User::factory()->create();
        $diary = DiaryEntry::factory()->create(['user_id' => $user->id]);

        $position = $this->service->assignDefaultPosition($user, $diary->id, 'diary_entry');

        $this->assertInstanceOf(EntityPosition::class, $position);
        $this->assertEquals($user->id, $position->user_id);
        $this->assertEquals($diary->id, $position->entity_id);
        $this->assertGreaterThan(0, $position->x);
        $this->assertGreaterThan(0, $position->y);
    }

    public function test_load_cards_appends_guest_demo_images_for_guest_users(): void
    {
        $guest = User::factory()->create([
            'tier' => Tier::Guest->value,
            'guest_created_at' => now(),
            'guest_expires_at' => now()->addDay(),
        ]);

        $cards = $this->service->loadCards($guest);

        $demoCards = collect($cards)
            ->where('type', 'image')
            ->filter(fn (array $card): bool => (bool) ($card['is_demo'] ?? false));

        $this->assertCount(4, $demoCards);
        $this->assertSame('guest-demo-memory-wall', $demoCards->first()['id']);
    }

    public function test_load_cards_assigns_safe_default_size_for_large_images(): void
    {
        $user = User::factory()->create();

        $image = Image::factory()->create([
            'user_id' => $user->id,
            'image_width' => 4200,
            'image_height' => 2800,
        ]);

        $cards = $this->service->loadCards($user);
        $imageCard = collect($cards)->firstWhere('id', $image->id);

        $this->assertNotNull($imageCard);
        $this->assertSame('image', $imageCard['type']);
        $this->assertIsInt($imageCard['width']);
        $this->assertIsInt($imageCard['height']);
        $this->assertLessThanOrEqual(400, $imageCard['width']);
        $this->assertLessThanOrEqual(350, $imageCard['height']);
    }
}
