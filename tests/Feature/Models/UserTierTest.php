<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\Tier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTierTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_defaults_to_basic_tier(): void
    {
        $user = User::factory()->create();

        $this->assertSame(Tier::Basic, $user->tier);
    }
}

