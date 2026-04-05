<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Enums\Tier;
use App\Livewire\Reminders;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RemindersLimitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_basic_user_cannot_create_more_than_daily_reminder_limit(): void
    {
        $user = User::factory()->create(['tier' => Tier::Basic->value]);
        Reminder::factory()->count(5)->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Reminders::class)
            ->set('reminderTitle', 'Blocked reminder')
            ->set('reminderAt', now()->addDay()->format('Y-m-d\TH:i'))
            ->call('saveReminder')
            ->assertSet('limitError', 'You have reached your reminder limit for today. Remaining: 0.');

        $this->assertDatabaseMissing('reminders', [
            'user_id' => $user->id,
            'title' => 'Blocked reminder',
        ]);
    }
}

