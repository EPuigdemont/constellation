<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\DiaryEntry;
use App\Models\ImportantDate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BackfillEncryptionKeysTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_encrypts_plaintext_rows(): void
    {
        $user = User::factory()->create();

        $entryId = Str::uuid()->toString();
        $dateId = Str::uuid()->toString();

        DB::table('diary_entries')->insert([
            'id' => $entryId,
            'user_id' => $user->id,
            'title' => 'Plaintext title',
            'body' => 'Plaintext body',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('important_dates')->insert([
            'id' => $dateId,
            'user_id' => $user->id,
            'label' => 'Plaintext label',
            'date' => now()->toDateString(),
            'recurs_annually' => false,
            'is_done' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('users:backfill-encryption-keys')->assertExitCode(0);

        $rawEntry = DB::table('diary_entries')->where('id', $entryId)->first();
        $this->assertNotSame('Plaintext title', $rawEntry->title);
        $this->assertNotSame('Plaintext body', $rawEntry->body);

        $entry = DiaryEntry::find($entryId);
        $this->assertSame('Plaintext title', $entry->title);
        $this->assertSame('Plaintext body', $entry->body);

        $date = ImportantDate::find($dateId);
        $this->assertSame('Plaintext label', $date->label);
    }

    public function test_command_is_idempotent(): void
    {
        $user = User::factory()->create();
        $entry = DiaryEntry::factory()->create([
            'user_id' => $user->id,
            'title' => 'Already encrypted',
            'body' => 'Body',
        ]);

        $beforeRow = DB::table('diary_entries')->where('id', $entry->id)->first();

        $this->artisan('users:backfill-encryption-keys')->assertExitCode(0);
        $this->artisan('users:backfill-encryption-keys')->assertExitCode(0);

        $afterRow = DB::table('diary_entries')->where('id', $entry->id)->first();
        $this->assertSame($beforeRow->title, $afterRow->title);
        $this->assertSame($beforeRow->body, $afterRow->body);
        $this->assertSame('Already encrypted', DiaryEntry::find($entry->id)->title);
    }
}
