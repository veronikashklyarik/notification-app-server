<?php

namespace Tests\Feature;

use App\Livewire\Settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('settings'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_settings_page(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->assertOk();
    }

    public function test_update_timezone_saves_the_users_timezone(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->set('timezone', 'Europe/Warsaw')
            ->call('updateTimezone');

        $this->assertSame('Europe/Warsaw', $user->fresh()->timezone);
    }

    public function test_update_timezone_rejects_an_invalid_timezone(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->set('timezone', 'Not/AZone')
            ->call('updateTimezone')
            ->assertHasErrors(['timezone']);

        $this->assertSame('UTC', $user->fresh()->timezone);
    }

    public function test_logout_ends_the_session(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->call('logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_page_groups_timezones_by_region(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings'))
            ->assertOk()
            ->assertSee('Europe')
            ->assertSee('Americas')
            ->assertSee('Asia and Pacific')
            ->assertSee('Africa')
            ->assertSee('Warsaw', false);
    }
}
