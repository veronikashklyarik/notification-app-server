<?php

namespace Tests\Feature;

use App\Livewire\Profile;
use App\Models\User;
use App\Notifications\WebEmailVerificationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_verification_email_dispatches_web_notification(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->call('sendVerificationEmail');

        Notification::assertSentTo($user, WebEmailVerificationNotification::class);
    }

    public function test_send_verification_email_does_not_send_when_already_verified(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->call('sendVerificationEmail');

        Notification::assertNothingSent();
    }

    public function test_updated_avatar_stores_webp_and_redirects(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('avatar', $file)
            ->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertNotNull($user->avatar);
        $this->assertStringEndsWith('.webp', $user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }

    public function test_updated_avatar_deletes_old_avatar_when_updating(): void
    {
        Storage::fake('public');

        $oldAvatarPath = 'avatars/old-avatar.webp';
        Storage::disk('public')->put($oldAvatarPath, 'fake-content');

        $user = User::factory()->create(['avatar' => $oldAvatarPath]);
        $file = UploadedFile::fake()->image('new-photo.jpg', 100, 100);

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('avatar', $file)
            ->assertRedirect(route('profile.edit'));

        Storage::disk('public')->assertMissing($oldAvatarPath);
    }

    public function test_updated_avatar_validates_file_extension(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('avatar', $file)
            ->assertHasErrors(['avatar']);
    }

    public function test_updated_avatar_validates_file_size(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('large.jpg')->size(11000); // 11MB, over 10MB limit

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('avatar', $file)
            ->assertHasErrors(['avatar']);
    }

    // --- Change password ---

    public function test_regular_user_can_change_password_with_correct_current_password(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('current_password', 'password')
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('changePassword')
            ->assertHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_regular_user_cannot_change_password_to_a_letters_only_password(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('current_password', 'password')
            ->set('password', 'onlyletters')
            ->set('password_confirmation', 'onlyletters')
            ->call('changePassword')
            ->assertHasErrors(['password']);
    }

    public function test_regular_user_cannot_change_password_without_current_password(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('changePassword')
            ->assertHasErrors(['current_password']);
    }

    public function test_regular_user_cannot_change_password_with_wrong_current_password(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('current_password', 'wrongpassword')
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('changePassword')
            ->assertHasErrors(['current_password']);
    }

    public function test_google_user_can_set_password_without_current_password(): void
    {
        $user = User::factory()->create(['google_id' => '123', 'password' => null]);

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('changePassword')
            ->assertHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_google_user_cannot_set_password_when_confirmation_does_not_match(): void
    {
        $user = User::factory()->create(['google_id' => '123', 'password' => null]);

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'differentpassword')
            ->call('changePassword')
            ->assertHasErrors(['password']);
    }

    // --- Delete account ---

    public function test_regular_user_can_delete_account_with_correct_password(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('deletePassword', 'password')
            ->call('deleteAccount')
            ->assertRedirect(route('login'));

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_regular_user_cannot_delete_account_without_password(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->call('deleteAccount')
            ->assertHasErrors(['deletePassword']);

        $this->assertModelExists($user);
    }

    public function test_regular_user_cannot_delete_account_with_wrong_password(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('deletePassword', 'wrongpassword')
            ->call('deleteAccount')
            ->assertHasErrors(['deletePassword']);

        $this->assertModelExists($user);
    }

    public function test_google_user_can_delete_account_without_password(): void
    {
        $user = User::factory()->create(['google_id' => '123', 'password' => null]);

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->call('deleteAccount')
            ->assertHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_confirm_delete_opens_the_dialog_without_requiring_a_password_upfront(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->call('confirmDeleteAccount')
            ->assertHasNoErrors()
            ->assertSet('confirmingDeleteAccount', true);
    }

    public function test_cancel_delete_account_closes_the_dialog_and_clears_the_password(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->call('confirmDeleteAccount')
            ->set('deletePassword', 'something')
            ->call('cancelDeleteAccount')
            ->assertSet('confirmingDeleteAccount', false)
            ->assertSet('deletePassword', '');
    }

    public function test_changing_email_updates_it_resets_verification_and_sends_a_new_link(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'old@example.com']);

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->call('startEditingEmail')
            ->set('newEmail', 'new@example.com')
            ->set('emailChangePassword', 'password')
            ->call('changeEmail')
            ->assertHasNoErrors()
            ->assertSet('editingEmail', false);

        $user->refresh();
        $this->assertSame('new@example.com', $user->email);
        $this->assertNull($user->email_verified_at);

        Notification::assertSentTo($user, WebEmailVerificationNotification::class);
    }

    public function test_changing_email_requires_the_correct_password(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'old@example.com']);

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->call('startEditingEmail')
            ->set('newEmail', 'new@example.com')
            ->set('emailChangePassword', 'wrong-password')
            ->call('changeEmail')
            ->assertHasErrors(['emailChangePassword']);

        $this->assertSame('old@example.com', $user->fresh()->email);
        Notification::assertNothingSent();
    }

    public function test_changing_email_rejects_an_address_already_in_use(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);
        User::factory()->create(['email' => 'taken@example.com']);

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->call('startEditingEmail')
            ->set('newEmail', 'taken@example.com')
            ->set('emailChangePassword', 'password')
            ->call('changeEmail')
            ->assertHasErrors(['newEmail']);

        $this->assertSame('old@example.com', $user->fresh()->email);
    }

    public function test_google_user_can_change_email_without_a_password(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'old@example.com', 'google_id' => '123', 'password' => null]);

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->call('startEditingEmail')
            ->set('newEmail', 'new@example.com')
            ->call('changeEmail')
            ->assertHasNoErrors();

        $this->assertSame('new@example.com', $user->fresh()->email);
    }

    public function test_cancel_editing_email_clears_the_form(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->call('startEditingEmail')
            ->set('newEmail', 'new@example.com')
            ->call('cancelEditingEmail')
            ->assertSet('editingEmail', false)
            ->assertSet('newEmail', '');

        $this->assertNotSame('new@example.com', $user->fresh()->email);
    }
}
