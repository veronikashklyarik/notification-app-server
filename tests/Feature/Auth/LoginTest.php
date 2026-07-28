<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_view_login_page(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_authenticated_users_are_redirected_away_from_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('login'))->assertRedirect();
    }

    public function test_login_redirects_to_home_on_success(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('home'));
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        $this->post(route('login'), [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
    }

    public function test_login_requires_email(): void
    {
        $this->post(route('login'), [
            'password' => 'password',
        ])->assertSessionHasErrors('email');
    }

    public function test_login_requires_password(): void
    {
        $this->post(route('login'), [
            'email' => 'user@example.com',
        ])->assertSessionHasErrors('password');
    }

    public function test_remember_me_sets_persistent_session(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
            'remember' => '1',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_login_without_remember_me_does_not_set_remember_token(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_guests_cannot_access_home(): void
    {
        $this->get(route('home'))->assertRedirect(route('login'));
    }
}
