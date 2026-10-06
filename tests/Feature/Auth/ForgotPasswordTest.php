<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_renders(): void
    {
        $this->get(route('password.request'))->assertOk();
    }

    public function test_submitting_a_known_email_sends_a_reset_link_and_flashes_the_email_back(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'veronika@example.com']);

        $response = $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'veronika@example.com']);

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHas('status');
        $response->assertSessionHas('_old_input.email', 'veronika@example.com');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_submitting_an_unknown_email_still_shows_the_generic_confirmation(): void
    {
        Notification::fake();

        $response = $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'nobody@example.com']);

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHas('status');

        Notification::assertNothingSent();
    }
}
