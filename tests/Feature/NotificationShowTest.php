<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\ScheduleType;
use App\Livewire\NotificationShow;
use App\Models\Notification;
use App\Models\NotificationEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $notification = Notification::factory()->create();

        $this->get(route('notifications.show', $notification))->assertRedirect(route('login'));
    }

    public function test_viewing_another_users_reminder_is_forbidden(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $notification = Notification::factory()->for($other)->create();

        Livewire::actingAs($user)
            ->test(NotificationShow::class, ['notification' => $notification])
            ->assertForbidden();
    }

    public function test_computes_done_skipped_and_missed_counts(): void
    {
        $user = User::factory()->create();
        // Ended (past starts_at/ends_at) so NotificationEventService doesn't
        // regenerate a rolling window of future pending events on save,
        // which would otherwise inflate totalCount for this assertion.
        $notification = Notification::factory()->for($user)->create([
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDay(),
        ]);

        NotificationEvent::factory()->for($user)->for($notification)->done()->create();
        NotificationEvent::factory()->for($user)->for($notification)->done()->create(['reminded_at' => now()]);
        NotificationEvent::factory()->for($user)->for($notification)->cancelled()->create();
        NotificationEvent::factory()->for($user)->for($notification)->create(['scheduled_at' => now()->subDay(), 'status' => EventStatus::Pending]);

        Livewire::actingAs($user)
            ->test(NotificationShow::class, ['notification' => $notification])
            ->assertSet('doneCount', 2)
            ->assertSet('skippedCount', 1)
            ->assertSet('missedCount', 1)
            ->assertSet('totalCount', 4);
    }

    public function test_toggle_active_pauses_the_reminder(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create(['is_active' => true]);

        Livewire::actingAs($user)
            ->test(NotificationShow::class, ['notification' => $notification])
            ->call('toggleActive');

        $this->assertDatabaseHas('notifications', ['id' => $notification->id, 'is_active' => false]);
    }

    public function test_mark_done_updates_the_event(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create();
        $event = NotificationEvent::factory()->for($user)->for($notification)->cancelled()->create();

        Livewire::actingAs($user)
            ->test(NotificationShow::class, ['notification' => $notification])
            ->call('markDone', $event->id);

        $this->assertDatabaseHas('notification_events', ['id' => $event->id, 'status' => EventStatus::Done]);
    }

    public function test_mark_cancelled_updates_the_event(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create();
        $event = NotificationEvent::factory()->for($user)->for($notification)->done()->create();

        Livewire::actingAs($user)
            ->test(NotificationShow::class, ['notification' => $notification])
            ->call('markCancelled', $event->id);

        $this->assertDatabaseHas('notification_events', ['id' => $event->id, 'status' => EventStatus::Cancelled]);
    }

    public function test_clear_status_reverts_event_to_pending(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create();
        $event = NotificationEvent::factory()->for($user)->for($notification)->done()->create();

        Livewire::actingAs($user)
            ->test(NotificationShow::class, ['notification' => $notification])
            ->call('clearStatus', $event->id);

        $this->assertDatabaseHas('notification_events', [
            'id' => $event->id,
            'status' => EventStatus::Pending,
            'completed_at' => null,
        ]);
    }

    public function test_mark_done_is_forbidden_for_an_event_belonging_to_another_notification(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create();
        $otherNotification = Notification::factory()->for($user)->create();
        $event = NotificationEvent::factory()->for($user)->for($otherNotification)->create();

        Livewire::actingAs($user)
            ->test(NotificationShow::class, ['notification' => $notification])
            ->call('markDone', $event->id)
            ->assertStatus(404);
    }

    public function test_repeat_course_creates_a_new_notification_preserving_schedule(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create([
            'name' => 'Antibiotic course',
            'schedule_type' => ScheduleType::EveryDay,
            'times' => ['08:00', '20:00'],
            'starts_at' => now()->subDays(14),
            'ends_at' => now()->subDay(),
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(NotificationShow::class, ['notification' => $notification])
            ->call('repeatCourse');

        $this->assertDatabaseHas('notifications', [
            'name' => 'Antibiotic course',
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $clone = Notification::where('name', 'Antibiotic course')
            ->where('id', '!=', $notification->id)
            ->first();

        $this->assertNotNull($clone);
        $this->assertEquals(['08:00', '20:00'], $clone->times);
        $this->assertEquals(now()->startOfDay()->toDateString(), $clone->starts_at->toDateString());
        $this->assertEquals(now()->startOfDay()->addDays(13)->toDateString(), $clone->ends_at->toDateString());

        // The original reminder and its history are untouched.
        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
    }

    public function test_repeat_course_leaves_the_clone_open_ended_when_the_original_had_no_end_date(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create([
            'name' => 'Open course',
            'schedule_type' => ScheduleType::EveryDay,
            'times' => ['08:00'],
            'starts_at' => now()->subDays(14),
            'ends_at' => null,
            'is_active' => false,
        ]);

        Livewire::actingAs($user)
            ->test(NotificationShow::class, ['notification' => $notification])
            ->call('repeatCourse');

        $clone = Notification::where('name', 'Open course')
            ->where('id', '!=', $notification->id)
            ->first();

        $this->assertNotNull($clone);
        $this->assertNull($clone->ends_at);
    }

    public function test_mark_now_logs_an_immediate_done_event_for_an_as_needed_reminder(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create([
            'schedule_type' => ScheduleType::AsNeeded,
            'times' => null,
        ]);

        Livewire::actingAs($user)
            ->test(NotificationShow::class, ['notification' => $notification])
            ->call('markNow')
            ->assertSet('doneCount', 1);

        $this->assertDatabaseHas('notification_events', [
            'notification_id' => $notification->id,
            'user_id' => $user->id,
            'status' => EventStatus::Done,
        ]);
    }

    public function test_delete_removes_the_reminder(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test(NotificationShow::class, ['notification' => $notification])
            ->call('confirmDelete')
            ->call('delete');

        $this->assertSoftDeleted('notifications', ['id' => $notification->id]);
    }
}
