<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\DiaryEntry;
use App\Models\ImportantDate;
use App\Models\Reminder;
use App\Models\Tag;
use App\Models\User;
use App\Services\ReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReminderServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReminderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReminderService;
    }

    // ── getTodayNotifications ──

    public function test_get_today_notifications_returns_due_reminders(): void
    {
        $user = User::factory()->create();
        Reminder::factory()->create([
            'user_id' => $user->id,
            'title' => 'Past Reminder',
            'remind_at' => now()->subHour(),
            'is_completed' => false,
        ]);

        $notifications = $this->service->getTodayNotifications($user);

        $this->assertCount(1, $notifications);
        $this->assertSame('reminder', $notifications->first()['type']);
        $this->assertSame('Past Reminder', $notifications->first()['title']);
    }

    public function test_get_today_notifications_excludes_completed_reminders(): void
    {
        $user = User::factory()->create();
        Reminder::factory()->create([
            'user_id' => $user->id,
            'remind_at' => now()->subHour(),
            'is_completed' => true,
        ]);

        $notifications = $this->service->getTodayNotifications($user);

        $this->assertEmpty($notifications);
    }

    public function test_get_today_notifications_excludes_future_reminders(): void
    {
        $user = User::factory()->create();
        Reminder::factory()->create([
            'user_id' => $user->id,
            'remind_at' => now()->addDay(),
            'is_completed' => false,
        ]);

        $notifications = $this->service->getTodayNotifications($user);

        $this->assertEmpty($notifications);
    }

    public function test_get_today_notifications_returns_matching_important_dates(): void
    {
        $user = User::factory()->create();
        ImportantDate::factory()->create([
            'user_id' => $user->id,
            'label' => 'Birthday',
            'date' => today()->toDateString(),
            'recurs_annually' => false,
        ]);

        $notifications = $this->service->getTodayNotifications($user);

        $important = $notifications->firstWhere('type', 'important_date');
        $this->assertNotNull($important);
        $this->assertSame('Birthday', $important['title']);
    }

    public function test_get_today_notifications_returns_annually_recurring_dates(): void
    {
        $user = User::factory()->create();
        ImportantDate::factory()->create([
            'user_id' => $user->id,
            'label' => 'Anniversary',
            'date' => today()->subYears(3)->toDateString(),
            'recurs_annually' => true,
        ]);

        $notifications = $this->service->getTodayNotifications($user);

        $important = $notifications->firstWhere('type', 'important_date');
        $this->assertNotNull($important);
        $this->assertSame('Anniversary', $important['title']);
    }

    public function test_get_today_notifications_excludes_past_non_recurring_dates(): void
    {
        $user = User::factory()->create();
        ImportantDate::factory()->create([
            'user_id' => $user->id,
            'date' => today()->subYear()->toDateString(),
            'recurs_annually' => false,
        ]);

        $notifications = $this->service->getTodayNotifications($user);

        $this->assertEmpty($notifications->filter(fn ($n) => $n['type'] === 'important_date'));
    }

    public function test_get_today_notifications_excludes_other_users(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Reminder::factory()->create(['user_id' => $other->id, 'remind_at' => now()->subHour(), 'is_completed' => false]);

        $notifications = $this->service->getTodayNotifications($user);

        $this->assertEmpty($notifications);
    }

    // ── findUpliftingEntry ──

    public function test_find_uplifting_entry_returns_entry_with_happy_tag(): void
    {
        $user = User::factory()->create();
        $happyTag = Tag::factory()->create(['user_id' => $user->id, 'name' => 'happy']);
        $entry = DiaryEntry::factory()->create(['user_id' => $user->id]);
        $entry->tags()->attach($happyTag);

        $result = $this->service->findUpliftingEntry($user);

        $this->assertNotNull($result);
        $this->assertSame($entry->id, $result->id);
    }

    public function test_find_uplifting_entry_returns_null_when_no_happy_entries(): void
    {
        $user = User::factory()->create();
        DiaryEntry::factory()->create(['user_id' => $user->id]);

        $result = $this->service->findUpliftingEntry($user);

        $this->assertNull($result);
    }

    public function test_find_uplifting_entry_accepts_grateful_tag(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id, 'name' => 'grateful']);
        $entry = DiaryEntry::factory()->create(['user_id' => $user->id]);
        $entry->tags()->attach($tag);

        $result = $this->service->findUpliftingEntry($user);

        $this->assertNotNull($result);
    }

    public function test_find_uplifting_entry_excludes_other_users(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $other->id, 'name' => 'happy']);
        $entry = DiaryEntry::factory()->create(['user_id' => $other->id]);
        $entry->tags()->attach($tag);

        $result = $this->service->findUpliftingEntry($user);

        $this->assertNull($result);
    }
}
