<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\User;
use App\Services\UnverifiedUserCleanupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnverifiedUserCleanupServiceTest extends TestCase
{
    use RefreshDatabase;

    private UnverifiedUserCleanupService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new UnverifiedUserCleanupService();
    }

    public function test_purge_older_than_hours_deletes_only_stale_unverified_users(): void
    {
        $staleUnverified = User::factory()->unverified()->create([
            'created_at' => now()->subDays(4),
            'updated_at' => now()->subDays(4),
        ]);

        $freshUnverified = User::factory()->unverified()->create([
            'created_at' => now()->subHours(6),
            'updated_at' => now()->subHours(6),
        ]);

        $verified = User::factory()->create([
            'email_verified_at' => now()->subDay(),
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        $deleted = $this->service->purgeOlderThanHours(72);

        $this->assertSame(1, $deleted);
        $this->assertModelMissing($staleUnverified);
        $this->assertModelExists($freshUnverified);
        $this->assertModelExists($verified);
    }
}

