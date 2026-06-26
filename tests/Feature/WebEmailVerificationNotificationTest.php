<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\WebEmailVerificationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebEmailVerificationNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_mail_contains_source_web_in_verification_url(): void
    {
        $user = User::factory()->unverified()->create();
        $notification = new WebEmailVerificationNotification;
        $mail = $notification->toMail($user);

        $this->assertStringContainsString('source=web', $mail->actionUrl);
        $this->assertStringContainsString((string) $user->id, $mail->actionUrl);
        $this->assertStringContainsString(sha1($user->email), $mail->actionUrl);
    }

    public function test_notification_mail_has_correct_subject(): void
    {
        $user = User::factory()->unverified()->create();
        $notification = new WebEmailVerificationNotification;
        $mail = $notification->toMail($user);

        $this->assertSame('Verify Email Address', $mail->subject);
    }

    public function test_notification_is_sent_via_mail_channel(): void
    {
        $user = User::factory()->unverified()->create();
        $notification = new WebEmailVerificationNotification;

        $this->assertSame(['mail'], $notification->via($user));
    }

    public function test_notification_url_points_to_api_verification_route(): void
    {
        $user = User::factory()->unverified()->create();
        $notification = new WebEmailVerificationNotification;
        $mail = $notification->toMail($user);

        $this->assertStringContainsString('api/v1/auth/email/verify', $mail->actionUrl);
    }
}
