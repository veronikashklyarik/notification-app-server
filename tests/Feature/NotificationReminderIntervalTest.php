<?php

namespace Tests\Feature;

use App\Livewire\NotificationCreate;
use App\Livewire\NotificationEdit;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationReminderIntervalTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // NotificationCreate
    // -------------------------------------------------------------------------

    public function test_create_saves_reminder_interval_when_provided(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('name', 'With reminder')
            ->set('schedule_type', 'every_day')
            ->set('reminderInterval', 60)
            ->call('save')
            ->assertHasNoErrors(['reminderInterval']);

        $this->assertSame(60, $user->reminders()->latest()->first()->reminder_interval);
    }

    public function test_create_saves_null_reminder_interval_by_default(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('name', 'No reminder')
            ->set('schedule_type', 'every_day')
            ->call('save')
            ->assertHasNoErrors(['reminderInterval']);

        $this->assertNull($user->reminders()->latest()->first()->reminder_interval);
    }

    public function test_create_rejects_invalid_reminder_interval(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('name', 'Bad interval')
            ->set('schedule_type', 'every_day')
            ->set('reminderInterval', 45)
            ->call('save')
            ->assertHasErrors(['reminderInterval']);
    }

    // -------------------------------------------------------------------------
    // NotificationEdit
    // -------------------------------------------------------------------------

    public function test_edit_populates_reminder_interval_on_mount(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create([
            'user_id' => $user->id,
            'reminder_interval' => 120,
        ]);

        $component = Livewire::actingAs($user)->test(NotificationEdit::class, ['notification' => $notification]);

        $this->assertSame(120, $component->get('reminderInterval'));
    }

    public function test_edit_populates_null_when_no_reminder_interval(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create([
            'user_id' => $user->id,
            'reminder_interval' => null,
        ]);

        $component = Livewire::actingAs($user)->test(NotificationEdit::class, ['notification' => $notification]);

        $this->assertNull($component->get('reminderInterval'));
    }

    public function test_edit_updates_reminder_interval(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->daily()->create([
            'user_id' => $user->id,
            'reminder_interval' => null,
        ]);

        Livewire::actingAs($user)
            ->test(NotificationEdit::class, ['notification' => $notification])
            ->set('reminderInterval', 240)
            ->call('save')
            ->assertHasNoErrors(['reminderInterval']);

        $this->assertSame(240, $notification->fresh()->reminder_interval);
    }

    public function test_edit_clears_reminder_interval_when_set_to_null(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->daily()->create([
            'user_id' => $user->id,
            'reminder_interval' => 60,
        ]);

        Livewire::actingAs($user)
            ->test(NotificationEdit::class, ['notification' => $notification])
            ->set('reminderInterval', null)
            ->call('save')
            ->assertHasNoErrors(['reminderInterval']);

        $this->assertNull($notification->fresh()->reminder_interval);
    }

    public function test_edit_rejects_invalid_reminder_interval(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(NotificationEdit::class, ['notification' => $notification])
            ->set('reminderInterval', 45)
            ->call('save')
            ->assertHasErrors(['reminderInterval']);
    }

    public function test_form_shows_the_selected_reminder_interval_label(): void
    {
        $user = User::factory()->create();

        $html = Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('reminderInterval', 60)
            ->html();

        $this->assertMatchesRegularExpression('/text-brand">\s*Every hour\s*<\/span>/', $html);
    }

    public function test_form_shows_off_when_no_reminder_interval_is_selected(): void
    {
        $user = User::factory()->create();

        $html = Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->html();

        $this->assertMatchesRegularExpression('/text-brand">\s*Off\s*<\/span>/', $html);
    }
}
