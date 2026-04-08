<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Enums\Tier;
use App\Livewire\Diary;
use App\Models\DiaryEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DiaryLimitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_basic_user_cannot_create_more_than_daily_diary_limit(): void
    {
        $user = User::factory()->create(['tier' => Tier::Basic->value]);
        DiaryEntry::factory()->count(5)->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Diary::class)
            ->set('newTitle', 'Blocked entry')
            ->set('newBody', 'Body')
            ->call('createEntry')
            ->assertSet('limitError', 'You have reached your diary entry limit for today. Remaining: 0.');

        $this->assertDatabaseMissing('diary_entries', [
            'user_id' => $user->id,
            'title' => 'Blocked entry',
        ]);
    }
}
