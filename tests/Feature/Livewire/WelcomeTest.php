<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Welcome;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WelcomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_renders_for_new_user(): void
    {
        $user = User::factory()->create(['first_login_at' => null]);

        Livewire::actingAs($user)
            ->test(Welcome::class)
            ->assertStatus(200)
            ->assertSet('name', $user->name);
    }

    public function test_mount_redirects_if_user_already_completed_welcome(): void
    {
        $user = User::factory()->create(['first_login_at' => now()->subDay()]);

        Livewire::actingAs($user)
            ->test(Welcome::class)
            ->assertRedirect(route('canvas'));
    }

    public function test_start_sets_first_login_at(): void
    {
        $user = User::factory()->create(['first_login_at' => null]);

        Livewire::actingAs($user)
            ->test(Welcome::class)
            ->call('start');

        $this->assertNotNull($user->fresh()->first_login_at);
    }

    public function test_start_redirects_to_canvas(): void
    {
        $user = User::factory()->create(['first_login_at' => null]);

        Livewire::actingAs($user)
            ->test(Welcome::class)
            ->call('start')
            ->assertRedirect(route('canvas'));
    }

    public function test_guest_gets_redirected_to_login(): void
    {
        $this->get(route('welcome.show'))
            ->assertRedirect(route('login'));
    }
}
