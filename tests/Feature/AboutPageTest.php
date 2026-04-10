<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AboutPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_page_is_publicly_accessible(): void
    {
        $response = $this->get(route('about'));

        $response
            ->assertOk()
            ->assertSeeText('About Constellation')
            ->assertSeeText('closed-source software');
    }

    public function test_about_link_is_visible_on_auth_entry_screens(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSeeText('About Constellation');

        $this->get(route('register'))
            ->assertOk()
            ->assertSeeText('About Constellation');
    }

    public function test_about_link_is_visible_in_authenticated_user_menu(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('diary'))
            ->assertOk()
            ->assertSeeText('About Constellation');
    }
}
