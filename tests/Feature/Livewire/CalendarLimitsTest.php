<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Enums\Tier;
use App\Livewire\Calendar;
use App\Models\DiaryEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CalendarLimitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_quick_create_respects_basic_diary_daily_limit(): void
    {
        $user = User::factory()->create(['tier' => Tier::Basic->value]);
        DiaryEntry::factory()->count(5)->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Calendar::class)
            ->set('createType', 'diary')
            ->set('createTitle', 'Blocked from calendar')
            ->set('createBody', 'Body')
            ->call('saveNewEntity')
            ->assertSet('limitError', 'You have reached your diary entry limit. Remaining: 0.');

        $this->assertDatabaseMissing('diary_entries', [
            'user_id' => $user->id,
            'title' => 'Blocked from calendar',
        ]);
    }
}

