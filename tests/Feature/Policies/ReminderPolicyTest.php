<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Models\EntityShare;
use App\Models\Reminder;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReminderPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_their_reminder(): void
    {
        $user = User::factory()->create();
        $reminder = $this->createReminder($user);

        $this->assertTrue($user->can('view', $reminder));
    }

    public function test_non_owner_cannot_view_unshared_reminder(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $reminder = $this->createReminder($owner);

        $this->assertFalse($other->can('view', $reminder));
    }

    public function test_non_owner_can_view_shared_reminder(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $reminder = $this->createReminder($owner);

        EntityShare::create([
            'owner_id' => $owner->id,
            'friend_id' => $other->id,
            'entity_id' => $reminder->id,
            'entity_type' => 'reminder',
        ]);

        $this->assertTrue($other->can('view', $reminder));
    }

    private function createReminder(User $user): Reminder
    {
        return Reminder::create([
            'user_id' => $user->id,
            'title' => 'Reminder',
            'body' => 'Test reminder body',
            'remind_at' => CarbonImmutable::parse('2026-04-02 10:00:00'),
            'reminder_type' => 'general',
        ]);
    }
}


