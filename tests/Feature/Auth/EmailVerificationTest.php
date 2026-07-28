<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_already_verified_user_is_redirected_to_profile_on_verification_link(): void
    {
        $user = User::factory()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->actingAs($user)
            ->get($verificationUrl)
            ->assertRedirect(route('profile.edit'));
    }

    public function test_unverified_user_can_verify_email_and_is_redirected_to_profile(): void
    {
        Event::fake();

        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->actingAs($user)
            ->get($verificationUrl)
            ->assertRedirect(route('profile.edit'));

        $this->assertNotNull($user->fresh()->email_verified_at);
        Event::assertDispatched(Verified::class);
    }

    public function test_email_verification_with_wrong_hash_fails(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => 'wrong-hash']
        );

        $this->actingAs($user)
            ->get($verificationUrl)
            ->assertForbidden();
    }

    public function test_already_verified_user_is_redirected_to_home_from_verification_notice(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertRedirect(route('home'));
    }

    public function test_unverified_user_sees_verification_notice_page(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertViewIs('auth.verify-email');
    }

    public function test_already_verified_user_is_redirected_to_home_on_resend(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect(route('home'));
    }

    public function test_unverified_user_can_resend_verification_email(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect();
    }

    public function test_guests_cannot_access_verification_routes(): void
    {
        $this->get(route('verification.notice'))->assertRedirect(route('login'));
        $this->post(route('verification.send'))->assertRedirect(route('login'));
    }
}
