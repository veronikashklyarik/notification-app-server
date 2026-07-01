<?php

namespace Tests\Feature;

use App\Livewire\Profile;
use App\Models\User;
use App\Notifications\WebEmailVerificationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function test_update_profile_saves_reminder_interval(): void
    {
        $user = User::factory()->create(['reminder_interval' => null]);

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('profileName', $user->name)
            ->set('timezone', 'UTC')
            ->set('reminderInterval', 60)
            ->call('updateProfile')
            ->assertHasNoErrors();

        $this->assertSame(60, $user->fresh()->reminder_interval);
    }

    public function test_update_profile_clears_reminder_interval_when_empty(): void
    {
        $user = User::factory()->create(['reminder_interval' => 60]);

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('profileName', $user->name)
            ->set('timezone', 'UTC')
            ->set('reminderInterval', null)
            ->call('updateProfile')
            ->assertHasNoErrors();

        $this->assertNull($user->fresh()->reminder_interval);
    }

    public function test_update_profile_rejects_invalid_reminder_interval(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('profileName', $user->name)
            ->set('timezone', 'UTC')
            ->set('reminderInterval', 45)
            ->call('updateProfile')
            ->assertHasErrors(['reminderInterval']);
    }

    public function test_mount_populates_reminder_interval_from_user(): void
    {
        $user = User::factory()->create(['reminder_interval' => 120]);

        $component = Livewire::actingAs($user)->test(Profile::class);

        $this->assertSame(120, $component->get('reminderInterval'));
    }
}
