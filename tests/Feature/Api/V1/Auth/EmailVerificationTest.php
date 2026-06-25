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
}
