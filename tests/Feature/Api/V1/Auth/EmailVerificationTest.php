<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use App\Notifications\ApiEmailVerificationNotification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_dispatches_api_email_verification_notification(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/email/verify/send')
            ->assertOk()
            ->assertJson(['message' => 'Verification email sent.']);

        Notification::assertSentTo($user, ApiEmailVerificationNotification::class);
        Notification::assertNotSentTo($user, VerifyEmail::class);
    }

    public function test_send_returns_already_verified_when_email_is_verified(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/email/verify/send')
            ->assertOk()
            ->assertJson(['message' => 'Email already verified.']);

        Notification::assertNothingSent();
    }

    public function test_send_requires_authentication(): void
    {
        $this->postJson('/api/v1/auth/email/verify/send')
            ->assertUnauthorized();
    }

    public function test_verify_marks_email_as_verified_and_redirects_to_deep_link(): void
    {
        $user = User::factory()->unverified()->create();
        $id = $user->id;
        $hash = sha1($user->email);

        $url = URL::temporarySignedRoute(
            'api.v1.auth.email.verify',
            now()->addMinutes(60),
            ['id' => $id, 'hash' => $hash]
        );

        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

        $this->get($path.'?'.$query)->assertRedirect();

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_verify_returns_forbidden_when_signature_is_missing(): void
    {
        $user = User::factory()->unverified()->create();

        $this->get("/api/v1/auth/email/verify/{$user->id}/badhash")
            ->assertForbidden();
    }

    public function test_verify_with_source_web_marks_email_as_verified_and_redirects_to_profile(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'api.v1.auth.email.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email), 'source' => 'web']
        );

        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

        $this->get($path.'?'.$query)
            ->assertRedirect(route('profile.edit'));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_verify_with_source_web_and_expired_signature_returns_forbidden(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'api.v1.auth.email.verify',
            now()->subMinutes(1),
            ['id' => $user->id, 'hash' => sha1($user->email), 'source' => 'web']
        );

        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

        // The ValidateSignature middleware intercepts expired signatures and returns 403
        // before the controller runs.
        $this->get($path.'?'.$query)->assertForbidden();
    }

    public function test_verify_with_source_web_and_stale_hash_redirects_to_profile_with_error(): void
    {
        // The hash becomes stale when a user's email changes after the link was sent.
        // The signature is valid (signed with old hash), but the current email no longer matches.
        $user = User::factory()->unverified()->create(['email' => 'original@example.com']);
        $staleHash = sha1('original@example.com');

        $url = URL::temporarySignedRoute(
            'api.v1.auth.email.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => $staleHash, 'source' => 'web']
        );

        // Simulate the user changing their email after the link was sent
        $user->update(['email' => 'changed@example.com']);

        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

        $this->get($path.'?'.$query)
            ->assertRedirect(route('profile.edit'));

        $this->assertNull($user->fresh()->email_verified_at);
    }
}
