<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Livewire\EventShow;
use App\Models\Notification;
use App\Models\NotificationEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EventShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create();
        $event = NotificationEvent::factory()->for($user)->for($notification)->create();

        $this->get(route('events.show', $event))->assertRedirect(route('login'));
    }

    public function test_user_cannot_view_another_users_event(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $notification = Notification::factory()->for($owner)->create();
        $event = NotificationEvent::factory()->for($owner)->for($notification)->create();

        Livewire::actingAs($other)
            ->test(EventShow::class, ['event' => $event])
            ->assertForbidden();
    }

    public function test_shows_event_with_active_notification(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create(['name' => 'Doctor Appointment']);
        $event = NotificationEvent::factory()->for($user)->for($notification)->create([
            'status' => EventStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(EventShow::class, ['event' => $event])
            ->assertOk()
            ->assertSet('event.id', $event->id);
    }

    public function test_shows_deleted_reminder_label_when_notification_is_purged(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create();
        $event = NotificationEvent::factory()->for($user)->for($notification)->done()->create();

        $notification->forceDelete();

        Livewire::actingAs($user)
            ->test(EventShow::class, ['event' => $event])
            ->assertOk()
            ->assertSee('Deleted reminder');
    }

    public function test_mark_done_updates_status_and_redirects(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create();
        $event = NotificationEvent::factory()->for($user)->for($notification)->create([
            'status' => EventStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(EventShow::class, ['event' => $event])
            ->call('markDone')
            ->assertRedirect();

        $this->assertDatabaseHas('notification_events', [
            'id' => $event->id,
            'status' => EventStatus::Done,
        ]);
    }

    public function test_mark_cancelled_updates_status_and_redirects(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create();
        $event = NotificationEvent::factory()->for($user)->for($notification)->create([
            'status' => EventStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(EventShow::class, ['event' => $event])
            ->call('markCancelled')
            ->assertRedirect();

        $this->assertDatabaseHas('notification_events', [
            'id' => $event->id,
            'status' => EventStatus::Cancelled,
        ]);
    }

    public function test_revert_to_pending_restores_status(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create();
        $event = NotificationEvent::factory()->for($user)->for($notification)->done()->create();

        Livewire::actingAs($user)
            ->test(EventShow::class, ['event' => $event])
            ->call('revertToPending')
            ->assertRedirect();

        $this->assertDatabaseHas('notification_events', [
            'id' => $event->id,
            'status' => EventStatus::Pending,
            'completed_at' => null,
        ]);
    }

    public function test_update_with_postponed_status_requires_postpone_date(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create();
        $event = NotificationEvent::factory()->for($user)->for($notification)->create([
            'status' => EventStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(EventShow::class, ['event' => $event])
            ->set('status', 'postponed')
            ->call('update')
            ->assertHasErrors('postponed_until');
    }
}
