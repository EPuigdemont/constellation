<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Enums\Tier;
use App\Models\DiaryEntry;
use App\Models\Image;
use App\Models\Note;
use App\Models\Postit;
use App\Models\Reminder;
use App\Models\User;
use App\Services\LimitCheckerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LimitCheckerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_basic_user_has_configured_daily_and_total_limits(): void
    {
        $user = User::factory()->create(['tier' => Tier::Basic->value]);

        Note::factory()->count(10)->create(['user_id' => $user->id]);
        Postit::factory()->count(20)->create(['user_id' => $user->id]);
        DiaryEntry::factory()->count(5)->create(['user_id' => $user->id]);
        Reminder::factory()->count(5)->create(['user_id' => $user->id]);
        Image::factory()->count(20)->create(['user_id' => $user->id]);

        $service = app(LimitCheckerService::class);

        $this->assertFalse($service->canCreateEntity($user, 'note'));
        $this->assertFalse($service->canCreateEntity($user, 'postit'));
        $this->assertFalse($service->canCreateEntity($user, 'diary_entry'));
        $this->assertFalse($service->canCreateEntity($user, 'reminder'));
        $this->assertFalse($service->canCreateEntity($user, 'image'));
    }

    public function test_premium_user_has_only_total_image_limit(): void
    {
        $user = User::factory()->create(['tier' => Tier::Premium->value]);

        Note::factory()->count(50)->create(['user_id' => $user->id]);
        Reminder::factory()->count(50)->create(['user_id' => $user->id]);
        Image::factory()->count(200)->create(['user_id' => $user->id]);

        $service = app(LimitCheckerService::class);

        $this->assertTrue($service->canCreateEntity($user, 'note'));
        $this->assertTrue($service->canCreateEntity($user, 'reminder'));
        $this->assertFalse($service->canCreateEntity($user, 'image'));
    }

    public function test_vip_user_has_no_limits(): void
    {
        $user = User::factory()->create(['tier' => Tier::VIP->value]);

        Image::factory()->count(250)->create(['user_id' => $user->id]);

        $service = app(LimitCheckerService::class);

        $this->assertTrue($service->canCreateEntity($user, 'note'));
        $this->assertTrue($service->canCreateEntity($user, 'postit'));
        $this->assertTrue($service->canCreateEntity($user, 'diary_entry'));
        $this->assertTrue($service->canCreateEntity($user, 'reminder'));
        $this->assertTrue($service->canCreateEntity($user, 'image'));
    }

    public function test_soft_deleted_entities_still_count_towards_limits(): void
    {
        $user = User::factory()->create(['tier' => Tier::Basic->value]);

        $image = Image::factory()->create(['user_id' => $user->id]);
        $image->delete();

        Note::factory()->count(10)->create(['user_id' => $user->id]);
        $deletedNote = Note::factory()->create(['user_id' => $user->id]);
        $deletedNote->delete();

        $service = app(LimitCheckerService::class);

        $this->assertFalse($service->canCreateEntity($user, 'note'));
        $this->assertSame(19, $service->getRemainingCount($user, 'image'));
    }
}

