<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\Tier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GuestModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_user_can_be_created_and_logged_in(): void
    {
        $response = $this->post(route('guest.store'));

        $response->assertRedirect(route('loading', absolute: false));

        /** @var User $user */
        $user = auth()->user();

        $this->assertAuthenticated();
        $this->assertNotNull($user);
        $this->assertTrue($user->isGuest());
        $this->assertSame(Tier::Guest, $user->tier);
        $this->assertNotNull($user->guest_created_at);
        $this->assertNotNull($user->guest_expires_at);
    }

    public function test_guest_user_cannot_access_friends_page(): void
    {
        $guest = User::factory()->create([
            'tier' => Tier::Guest->value,
            'guest_created_at' => now(),
            'guest_expires_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($guest)->get(route('friends'));

        $response->assertRedirect(route('canvas', absolute: false));
    }

    public function test_guest_user_cannot_access_data_export(): void
    {
        $guest = User::factory()->create([
            'tier' => Tier::Guest->value,
            'guest_created_at' => now(),
            'guest_expires_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($guest)->get(route('data.export'));

        $response->assertRedirect(route('profile.edit', absolute: false));
    }

    public function test_guest_user_cannot_update_profile_information(): void
    {
        $guest = User::factory()->create([
            'tier' => Tier::Guest->value,
            'guest_created_at' => now(),
            'guest_expires_at' => now()->addDay(),
        ]);

        Livewire::actingAs($guest)
            ->test('pages::settings.profile')
            ->set('name', 'New Name')
            ->call('updateProfileInformation')
            ->assertHasErrors(['name']);

        $this->assertSame($guest->name, $guest->fresh()->name);
    }

    public function test_guest_user_can_convert_account_to_basic(): void
    {
        $guest = User::factory()->create([
            'tier' => Tier::Guest->value,
            'guest_created_at' => now(),
            'guest_expires_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($guest)->post(route('guest.convert.store'), [
            'name' => 'Converted User',
            'username' => 'converted-user',
            'email' => 'converted@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('diary', absolute: false));

        $guest->refresh();

        $this->assertSame(Tier::Basic, $guest->tier);
        $this->assertNull($guest->guest_created_at);
        $this->assertNull($guest->guest_expires_at);
        $this->assertSame('converted-user', $guest->username);
    }
}
