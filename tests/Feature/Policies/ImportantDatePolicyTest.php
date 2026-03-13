<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Models\ImportantDate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportantDatePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_their_date(): void
    {
        $user = User::factory()->create();
        $date = ImportantDate::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('view', $date));
    }

    public function test_non_owner_cannot_view_date(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $date = ImportantDate::factory()->create(['user_id' => $owner->id]);

        $this->assertFalse($other->can('view', $date));
    }

    public function test_owner_can_update_their_date(): void
    {
        $user = User::factory()->create();
        $date = ImportantDate::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('update', $date));
    }

    public function test_non_owner_cannot_update_date(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $date = ImportantDate::factory()->create(['user_id' => $owner->id]);

        $this->assertFalse($other->can('update', $date));
    }

    public function test_owner_can_delete_their_date(): void
    {
        $user = User::factory()->create();
        $date = ImportantDate::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('delete', $date));
    }

    public function test_non_owner_cannot_delete_date(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $date = ImportantDate::factory()->create(['user_id' => $owner->id]);

        $this->assertFalse($other->can('delete', $date));
    }

    public function test_any_auth_user_can_create(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->can('create', ImportantDate::class));
    }
}
