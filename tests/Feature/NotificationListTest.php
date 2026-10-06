<?php

namespace Tests\Feature;

use App\Livewire\NotificationList;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationListTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_reminders_list(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationList::class)
            ->assertOk();
    }

    public function test_computes_filter_chip_counts(): void
    {
        $user = User::factory()->create();

        Notification::factory()->for($user)->create(['is_active' => true]);
        Notification::factory()->for($user)->inactive()->create();
        Notification::factory()->for($user)->create([
            'is_active' => true,
            'ends_at' => now()->subDays(2),
        ]);

        Livewire::actingAs($user)
            ->test(NotificationList::class)
            ->assertSet('allCount', 3)
            ->assertSet('activeCount', 1)
            ->assertSet('pausedCount', 1)
            ->assertSet('endedCount', 1);
    }

    public function test_reminders_from_other_users_are_not_visible(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Notification::factory()->for($other)->create();

        Livewire::actingAs($user)
            ->test(NotificationList::class)
            ->assertSet('allCount', 0);
    }

    public function test_toggle_active_pauses_an_active_reminder(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create(['is_active' => true]);

        Livewire::actingAs($user)
            ->test(NotificationList::class)
            ->call('toggleActive', $notification->id);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'is_active' => false,
        ]);
    }

    public function test_toggle_active_resumes_a_paused_reminder(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->inactive()->create();

        Livewire::actingAs($user)
            ->test(NotificationList::class)
            ->call('toggleActive', $notification->id);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'is_active' => true,
        ]);
    }

    public function test_toggle_active_does_not_affect_other_users_reminders(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $notification = Notification::factory()->for($other)->create(['is_active' => true]);

        Livewire::actingAs($user)
            ->test(NotificationList::class)
            ->call('toggleActive', $notification->id);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'is_active' => true,
        ]);
    }

    public function test_confirm_delete_sets_pending_delete_id(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test(NotificationList::class)
            ->call('confirmDelete', $notification->id)
            ->assertSet('pendingDeleteId', $notification->id);
    }

    public function test_delete_removes_the_reminder(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test(NotificationList::class)
            ->call('confirmDelete', $notification->id)
            ->call('delete');

        $this->assertSoftDeleted('notifications', ['id' => $notification->id]);
    }

    public function test_delete_is_forbidden_for_other_users_reminders(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $notification = Notification::factory()->for($other)->create();

        Livewire::actingAs($user)
            ->test(NotificationList::class)
            ->call('confirmDelete', $notification->id)
            ->call('delete');

        $this->assertDatabaseHas('notifications', ['id' => $notification->id, 'deleted_at' => null]);
    }

    public function test_mount_picks_up_created_reminder_flash_as_undo_state(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create(['name' => 'Vitamins']);

        session()->flash('createdReminder', ['id' => $notification->id, 'name' => $notification->name]);

        Livewire::actingAs($user)
            ->test(NotificationList::class)
            ->assertSet('undoState.notificationId', $notification->id)
            ->assertSet('undoState.label', 'Vitamins created');
    }

    public function test_undo_create_deletes_the_notification_and_clears_undo_state(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create();

        session()->flash('createdReminder', ['id' => $notification->id, 'name' => $notification->name]);

        Livewire::actingAs($user)
            ->test(NotificationList::class)
            ->call('undoCreate')
            ->assertSet('undoState', null);

        $this->assertSoftDeleted('notifications', ['id' => $notification->id]);
    }

    public function test_undo_create_does_not_affect_other_users_reminders(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $notification = Notification::factory()->for($other)->create();

        Livewire::actingAs($user)
            ->test(NotificationList::class)
            ->set('undoState', ['notificationId' => $notification->id, 'label' => 'x'])
            ->call('undoCreate');

        $this->assertDatabaseHas('notifications', ['id' => $notification->id, 'deleted_at' => null]);
    }

    public function test_open_create_sheet_opens_the_embedded_create_form(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationList::class)
            ->call('openCreateSheet', 'vitamins')
            ->assertSet('sheetOpen', true)
            ->assertSet('sheetMode', 'create')
            ->assertSet('sheetTemplate', 'vitamins')
            ->assertSee(__('New reminder'));
    }

    public function test_open_edit_sheet_opens_the_embedded_edit_form(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create(['name' => 'Vitamins']);

        Livewire::actingAs($user)
            ->test(NotificationList::class)
            ->call('openEditSheet', $notification->id)
            ->assertSet('sheetOpen', true)
            ->assertSet('sheetMode', 'edit')
            ->assertSet('sheetNotificationId', $notification->id)
            ->assertSee(__('Edit reminder'));
    }

    public function test_open_edit_sheet_refuses_another_users_notification(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $notification = Notification::factory()->for($other)->create();

        Livewire::actingAs($user)
            ->test(NotificationList::class)
            ->call('openEditSheet', $notification->id)
            ->assertSet('sheetOpen', false);
    }

    public function test_open_edit_sheet_refuses_a_nonexistent_notification(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationList::class)
            ->call('openEditSheet', 999999)
            ->assertSet('sheetOpen', false);
    }

    public function test_reminder_created_event_closes_the_sheet_and_sets_undo_state(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create(['name' => 'Vitamins']);

        Livewire::actingAs($user)
            ->test(NotificationList::class)
            ->call('openCreateSheet')
            ->dispatch('reminder-created', id: $notification->id, name: 'Vitamins')
            ->assertSet('sheetOpen', false)
            ->assertSet('undoState.notificationId', $notification->id)
            ->assertSet('undoState.label', 'Vitamins created');
    }

    public function test_sheet_closed_event_closes_the_sheet(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationList::class)
            ->call('openCreateSheet')
            ->dispatch('sheet-closed')
            ->assertSet('sheetOpen', false);
    }

    public function test_reminder_updated_event_closes_the_sheet(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test(NotificationList::class)
            ->call('openEditSheet', $notification->id)
            ->dispatch('reminder-updated', id: $notification->id, name: $notification->name)
            ->assertSet('sheetOpen', false);
    }
}
