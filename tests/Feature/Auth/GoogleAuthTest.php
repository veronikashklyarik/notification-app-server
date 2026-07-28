<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_redirect_route_redirects_to_google(): void
    {
        $response = $this->get(route('auth.google'));

        $response->assertRedirectContains('accounts.google.com');
    }

    public function test_new_user_is_created_on_first_google_login(): void
    {
        $this->mockSocialiteUser(id: '123', name: 'Jane Doe', email: 'jane@example.com');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'google_id' => '123',
        ]);
    }

    public function test_timezone_from_session_is_stored_for_new_user(): void
    {
        $this->mockSocialiteUser(id: '999', name: 'Tz User', email: 'tz@example.com');

        $this->withSession(['google_timezone' => 'Europe/Warsaw'])
            ->get(route('auth.google.callback'));

        $this->assertDatabaseHas('users', [
            'email' => 'tz@example.com',
            'timezone' => 'Europe/Warsaw',
        ]);
    }

    public function test_locale_from_cookie_is_stored_for_new_user(): void
    {
        $this->mockSocialiteUser(id: '888', name: 'Locale User', email: 'locale@example.com');

        $this->withCookie('app_locale', 'pl')
            ->get(route('auth.google.callback'));

        $this->assertDatabaseHas('users', [
            'email' => 'locale@example.com',
            'locale' => 'pl',
        ]);
    }

    public function test_existing_google_user_can_log_in(): void
    {
        $user = User::factory()->create(['google_id' => '123', 'email' => 'jane@example.com']);
        $this->mockSocialiteUser(id: '123', name: $user->name, email: $user->email);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_existing_email_user_gets_google_id_linked(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com', 'google_id' => null]);
        $this->mockSocialiteUser(id: '456', name: $user->name, email: 'jane@example.com');

        $this->get(route('auth.google.callback'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('users', ['email' => 'jane@example.com', 'google_id' => '456']);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_google_auth_failure_redirects_to_login_with_error(): void
    {
        Socialite::shouldReceive('driver->user')->andThrow(new \Exception('OAuth error'));

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_new_google_user_has_verified_email(): void
    {
        $this->mockSocialiteUser(id: '789', name: 'New User', email: 'new@example.com');

        $this->get(route('auth.google.callback'));

        $user = User::query()->where('email', 'new@example.com')->firstOrFail();
        $this->assertTrue($user->hasVerifiedEmail());
    }

    /**
     * @param  non-empty-string  $id
     * @param  non-empty-string  $name
     * @param  non-empty-string  $email
     */
    private function mockSocialiteUser(string $id, string $name, string $email): void
    {
        $socialiteUser = $this->createMock(SocialiteUser::class);
        $socialiteUser->method('getId')->willReturn($id);
        $socialiteUser->method('getName')->willReturn($name);
        $socialiteUser->method('getEmail')->willReturn($email);

        Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);
    }
}
