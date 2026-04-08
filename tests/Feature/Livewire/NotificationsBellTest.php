<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\NotificationsBell;
use App\Models\ImportantDate;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationsBellTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_renders(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationsBell::class)
            ->assertStatus(200);
    }

    public function test_count_is_zero_with_no_notifications(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test(NotificationsBell::class);

        $this->assertSame(0, $component->instance()->getNotificationCount());
    }

    public function test_count_includes_due_reminders(): void
    {
        $user = User::factory()->create();
        Reminder::factory()->create([
            'user_id' => $user->id,
            'remind_at' => now()->subHour(),
            'is_completed' => false,
        ]);

        $component = Livewire::actingAs($user)->test(NotificationsBell::class);

        $this->assertSame(1, $component->instance()->getNotificationCount());
    }

    public function test_count_excludes_completed_reminders(): void
    {
        $user = User::factory()->create();
        Reminder::factory()->create([
            'user_id' => $user->id,
            'remind_at' => now()->subHour(),
            'is_completed' => true,
        ]);

        $component = Livewire::actingAs($user)->test(NotificationsBell::class);

        $this->assertSame(0, $component->instance()->getNotificationCount());
    }

    public function test_count_includes_todays_important_dates(): void
    {
        $user = User::factory()->create();
        ImportantDate::factory()->create([
            'user_id' => $user->id,
            'date' => today()->toDateString(),
            'is_done' => false,
            'recurs_annually' => false,
        ]);

        $component = Livewire::actingAs($user)->test(NotificationsBell::class);

        $this->assertSame(1, $component->instance()->getNotificationCount());
    }

    public function test_count_excludes_done_important_dates(): void
    {
        $user = User::factory()->create();
        ImportantDate::factory()->create([
            'user_id' => $user->id,
            'date' => today()->toDateString(),
            'is_done' => true,
            'recurs_annually' => false,
        ]);

        $component = Livewire::actingAs($user)->test(NotificationsBell::class);

        $this->assertSame(0, $component->instance()->getNotificationCount());
    }

    public function test_count_sums_reminders_and_dates(): void
    {
        $user = User::factory()->create();
        Reminder::factory()->create(['user_id' => $user->id, 'remind_at' => now()->subMinute(), 'is_completed' => false]);
        ImportantDate::factory()->create(['user_id' => $user->id, 'date' => today()->toDateString(), 'is_done' => false]);

        $component = Livewire::actingAs($user)->test(NotificationsBell::class);

        $this->assertSame(2, $component->instance()->getNotificationCount());
    }

    public function test_count_excludes_other_users_data(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Reminder::factory()->create(['user_id' => $other->id, 'remind_at' => now()->subHour(), 'is_completed' => false]);

        $component = Livewire::actingAs($user)->test(NotificationsBell::class);

        $this->assertSame(0, $component->instance()->getNotificationCount());
    }
}
