<?php

namespace Tests\Feature\Auth;

use App\Enums\EventStatus;
use App\Enums\RestoreMethod;
use App\Livewire\Profile;
use App\Models\Notification;
use App\Models\NotificationEvent;
use App\Models\User;
use App\Notifications\AccountRestoredNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Password;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Livewire\Livewire;
use Tests\TestCase;

class RestoreDeletedAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_signing_in_within_30_days_restores_the_account_and_its_reminders(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);
        $reminder = Notification::factory()->daily()->create(['user_id' => $user->id]);
        $this->deleteAccount($user);
        $this->travel(10)->days();

        $response = $this->post(route('login'), ['email' => 'jane@example.com', 'password' => 'password']);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user->fresh());
        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
        $this->assertNotSoftDeleted('notifications', ['id' => $reminder->id]);
    }

    public function test_restoring_drops_occurrences_that_fell_while_the_account_was_deleted(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);
        $reminder = Notification::factory()->daily()->create(['user_id' => $user->id]);
        $missedBeforeDeletion = NotificationEvent::factory()->create([
            'user_id' => $user->id,
            'notification_id' => $reminder->id,
            'scheduled_at' => now()->subDay(),
        ]);
        $this->deleteAccount($user);
        $whileDeleted = NotificationEvent::factory()->create([
            'user_id' => $user->id,
            'notification_id' => $reminder->id,
            'scheduled_at' => now()->addDays(2),
        ]);
        $this->travel(5)->days();

        $this->post(route('login'), ['email' => 'jane@example.com', 'password' => 'password']);

        $this->assertDatabaseHas('notification_events', ['id' => $missedBeforeDeletion->id]);
        $this->assertDatabaseMissing('notification_events', ['id' => $whileDeleted->id]);
        $this->assertTrue($reminder->events()->where('status', EventStatus::Pending)->where('scheduled_at', '>', now())->exists());
    }

    public function test_reminder_deleted_before_the_account_stays_deleted(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);
        $removedEarlier = Notification::factory()->create(['user_id' => $user->id]);
        $removedEarlier->delete();
        $this->travel(1)->hour();
        $this->deleteAccount($user);

        $this->post(route('login'), ['email' => 'jane@example.com', 'password' => 'password']);

        $this->assertSoftDeleted('notifications', ['id' => $removedEarlier->id]);
    }

    public function test_wrong_password_does_not_restore(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);
        $this->deleteAccount($user);

        $this->post(route('login'), ['email' => 'jane@example.com', 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_after_30_days_sign_in_fails_and_the_account_is_erased(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);
        $this->deleteAccount($user);
        $this->travel(31)->days();

        $this->post(route('login'), ['email' => 'jane@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_continue_with_google_restores_a_recently_deleted_account(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com', 'google_id' => '123']);
        $this->deleteAccount($user);
        $this->mockSocialiteUser(id: '123', email: 'jane@example.com');

        $this->get(route('auth.google.callback'))->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user->fresh());
        $this->assertSame(1, User::withTrashed()->count());
    }

    public function test_continue_with_google_restores_by_email_and_links_the_google_account(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);
        $this->deleteAccount($user);
        $this->mockSocialiteUser(id: '456', email: 'jane@example.com');

        $this->get(route('auth.google.callback'))->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user->fresh());
        $this->assertSame('456', $user->fresh()->google_id);
    }

    public function test_continue_with_google_after_30_days_starts_a_new_account(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com', 'google_id' => '123']);
        $this->deleteAccount($user);
        $this->travel(31)->days();
        $this->mockSocialiteUser(id: '123', email: 'jane@example.com');

        $this->get(route('auth.google.callback'))->assertRedirect(route('home'));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertSame(1, User::withTrashed()->where('email', 'jane@example.com')->count());
        $this->assertAuthenticated();
    }

    public function test_registering_with_a_recently_deleted_email_asks_to_sign_in(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);
        $this->deleteAccount($user);

        $this->post(route('register'), [
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'password' => 'password123',
        ])->assertSessionHasErrors(['email' => 'This account was deleted recently. Sign in to restore it.']);
    }

    public function test_registering_after_30_days_replaces_the_old_account(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);
        $this->deleteAccount($user);
        $this->travel(31)->days();

        $this->post(route('register'), [
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'password' => 'password123',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertAuthenticated();
    }

    public function test_api_sign_in_restores_a_recently_deleted_account(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);
        $this->deleteAccount($user);

        $this->postJson(route('api.v1.auth.login'), [
            'email' => 'jane@example.com',
            'password' => 'password',
            'device_name' => 'phone',
        ])->assertOk()->assertJsonStructure(['token', 'refresh_token']);

        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_restoring_by_password_emails_the_owner(): void
    {
        NotificationFacade::fake();
        $user = User::factory()->create(['email' => 'jane@example.com']);
        $this->deleteAccount($user);

        $this->post(route('login'), ['email' => 'jane@example.com', 'password' => 'password']);

        NotificationFacade::assertSentTo($user, AccountRestoredNotification::class, fn ($notification) => $notification->method === RestoreMethod::Password);
    }

    public function test_restoring_with_google_emails_the_owner(): void
    {
        NotificationFacade::fake();
        $user = User::factory()->create(['email' => 'jane@example.com', 'google_id' => '123']);
        $this->deleteAccount($user);
        $this->mockSocialiteUser(id: '123', email: 'jane@example.com');

        $this->get(route('auth.google.callback'));

        NotificationFacade::assertSentTo($user, AccountRestoredNotification::class, fn ($notification) => $notification->method === RestoreMethod::Google);
    }

    public function test_signing_in_to_an_active_account_sends_no_restore_email(): void
    {
        NotificationFacade::fake();
        User::factory()->create(['email' => 'jane@example.com']);

        $this->post(route('login'), ['email' => 'jane@example.com', 'password' => 'password']);

        NotificationFacade::assertNothingSentTo(User::first(), AccountRestoredNotification::class);
    }

    public function test_restore_email_says_how_and_where_to_get_help(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Warsaw']);

        $mail = (new AccountRestoredNotification(RestoreMethod::Google, now()))->toMail($user);

        $this->assertSame('Your Notifyr account was restored', $mail->subject);
        $this->assertStringContainsString('signing in with Google', implode(' ', $mail->introLines));
        $this->assertStringContainsString(config('mail.from.address'), implode(' ', $mail->outroLines));
    }

    public function test_forgot_password_sends_a_link_for_a_recently_deleted_account(): void
    {
        NotificationFacade::fake();
        $user = User::factory()->create(['email' => 'jane@example.com']);
        $this->deleteAccount($user);

        $this->post(route('password.email'), ['email' => 'jane@example.com'])
            ->assertSessionHas('status', 'If an account with that email exists, a password reset link has been sent.');

        NotificationFacade::assertSentTo($user, ResetPassword::class);
    }

    public function test_forgot_password_sends_nothing_after_30_days(): void
    {
        NotificationFacade::fake();
        $user = User::factory()->create(['email' => 'jane@example.com']);
        $this->deleteAccount($user);
        $this->travel(31)->days();

        $this->post(route('password.email'), ['email' => 'jane@example.com'])
            ->assertSessionHas('status', 'If an account with that email exists, a password reset link has been sent.');

        NotificationFacade::assertNothingSent();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_resetting_the_password_restores_a_recently_deleted_account(): void
    {
        NotificationFacade::fake();
        $user = User::factory()->create(['email' => 'jane@example.com']);
        $reminder = Notification::factory()->daily()->create(['user_id' => $user->id]);
        $this->deleteAccount($user);
        $token = Password::createToken($user->fresh());

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => 'jane@example.com',
            'password' => 'new-password-123',
        ])->assertRedirect(route('login'));

        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
        $this->assertNotSoftDeleted('notifications', ['id' => $reminder->id]);
        NotificationFacade::assertSentTo($user, AccountRestoredNotification::class, fn ($notification) => $notification->method === RestoreMethod::PasswordReset);

        $this->post(route('login'), ['email' => 'jane@example.com', 'password' => 'new-password-123']);
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_reset_with_a_wrong_token_does_not_restore(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);
        $this->deleteAccount($user);

        $this->post(route('password.store'), [
            'token' => 'not-a-real-token',
            'email' => 'jane@example.com',
            'password' => 'new-password-123',
        ])->assertSessionHasErrors('email');

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_sign_in_page_links_to_the_landing_page(): void
    {
        $this->get(route('login'))
            ->assertSee('What is Notifyr?')
            ->assertSee('href="'.route('landing').'"', false);
    }

    private function deleteAccount(User $user): void
    {
        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('deletePassword', 'password')
            ->call('deleteAccount');

        auth()->logout();
    }

    private function mockSocialiteUser(string $id, string $email): void
    {
        $socialiteUser = $this->createMock(SocialiteUser::class);
        $socialiteUser->method('getId')->willReturn($id);
        $socialiteUser->method('getName')->willReturn('Jane');
        $socialiteUser->method('getEmail')->willReturn($email);

        Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);
    }
}
