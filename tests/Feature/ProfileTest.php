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

    public function test_google_user_confirm_delete_dispatches_event_without_password(): void
    {
        $user = User::factory()->create(['google_id' => '123', 'password' => null]);

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->call('confirmDeleteAccount')
            ->assertHasNoErrors()
            ->assertDispatched('show-delete-account-confirmation');
    }

    public function test_regular_user_confirm_delete_requires_password(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->call('confirmDeleteAccount')
            ->assertHasErrors(['deletePassword']);
    }

    public function test_regular_user_confirm_delete_dispatches_event_with_correct_password(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('deletePassword', 'password')
            ->call('confirmDeleteAccount')
            ->assertHasNoErrors()
            ->assertDispatched('show-delete-account-confirmation');
    }
}
