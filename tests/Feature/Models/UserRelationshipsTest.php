<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\DiaryEntry;
use App\Models\EntityPosition;
use App\Models\Image;
use App\Models\ImportantDate;
use App\Models\Note;
use App\Models\Postit;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_many_diary_entries(): void
    {
        $user = User::factory()->create();
        DiaryEntry::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->diaryEntries);
    }

    public function test_user_has_many_notes(): void
    {
        $user = User::factory()->create();
        Note::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->notes);
    }

    public function test_user_has_many_postits(): void
    {
        $user = User::factory()->create();
        Postit::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->postits);
    }

    public function test_user_has_many_images(): void
    {
        $user = User::factory()->create();
        Image::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->images);
    }

    public function test_user_has_many_tags(): void
    {
        $user = User::factory()->create();
        Tag::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->tags);
    }

    public function test_user_has_many_important_dates(): void
    {
        $user = User::factory()->create();
        ImportantDate::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->importantDates);
    }

    public function test_user_has_many_entity_positions(): void
    {
        $user = User::factory()->create();
        $entry = DiaryEntry::factory()->create(['user_id' => $user->id]);

        EntityPosition::create([
            'user_id' => $user->id,
            'entity_id' => $entry->id,
            'entity_type' => 'diary_entry',
            'x' => 100.5,
            'y' => 200.3,
            'z_index' => 1,
        ]);

        $this->assertCount(1, $user->entityPositions);
    }
}
