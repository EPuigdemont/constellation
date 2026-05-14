<?php

declare(strict_types=1);

namespace Tests\Feature\Casts;

use App\Models\DiaryEntry;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserContentEncryptedTest extends TestCase
{
    use RefreshDatabase;

    public function test_diary_entry_title_and_body_are_encrypted_at_rest(): void
    {
        $user = User::factory()->create();
        $entry = DiaryEntry::factory()->create([
            'user_id' => $user->id,
            'title' => 'My private title',
            'body' => '<p>Sensitive</p>',
        ]);

        $row = DB::table('diary_entries')->where('id', $entry->id)->first();
        $this->assertNotSame('My private title', $row->title);
        $this->assertNotSame('<p>Sensitive</p>', $row->body);
        $this->assertGreaterThan(40, strlen((string) $row->title));

        $reloaded = DiaryEntry::find($entry->id);
        $this->assertSame('My private title', $reloaded->title);
        $this->assertSame('<p>Sensitive</p>', $reloaded->body);
    }

    public function test_each_user_uses_distinct_keys(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $aliceNote = Note::factory()->create([
            'user_id' => $alice->id,
            'title' => 'Shared title',
            'body' => 'Same content',
        ]);
        $bobNote = Note::factory()->create([
            'user_id' => $bob->id,
            'title' => 'Shared title',
            'body' => 'Same content',
        ]);

        $aliceRow = DB::table('notes')->where('id', $aliceNote->id)->first();
        $bobRow = DB::table('notes')->where('id', $bobNote->id)->first();

        $this->assertNotSame($aliceRow->title, $bobRow->title);
        $this->assertNotSame($aliceRow->body, $bobRow->body);
    }

    public function test_null_round_trips_as_null(): void
    {
        $user = User::factory()->create();
        $note = Note::factory()->create([
            'user_id' => $user->id,
            'title' => null,
            'body' => 'has body',
        ]);

        $row = DB::table('notes')->where('id', $note->id)->first();
        $this->assertNull($row->title);
        $this->assertNull(Note::find($note->id)->title);
    }

    public function test_creating_a_user_seeds_an_encryption_key(): void
    {
        $user = User::factory()->create();
        $this->assertNotEmpty(DB::table('users')->where('id', $user->id)->value('encryption_key'));
    }

    public function test_encryption_key_is_hidden_in_serialization(): void
    {
        $user = User::factory()->create();
        $this->assertArrayNotHasKey('encryption_key', $user->toArray());
    }
}
