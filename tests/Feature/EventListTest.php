<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Livewire\EventList;
use App\Models\Notification;
use App\Models\NotificationEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class EventListTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('events.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_history_page(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->assertOk();
    }

    public function test_computes_week_stats(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        NotificationEvent::factory()->for($user)->for($notification)->done()->create(['scheduled_at' => now()]);
        NotificationEvent::factory()->for($user)->for($notification)->done()->create(['scheduled_at' => now()->subDay()]);
        NotificationEvent::factory()->for($user)->for($notification)->cancelled()->create(['scheduled_at' => now()->subDay()]);

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->assertSet('weekDoneCount', 2)
            ->assertSet('weekMarksCount', 3)
            ->assertSet('weekCleanDays', 1);
    }

    public function test_recent_done_and_cancelled_events_span_expected_days(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        NotificationEvent::factory()->for($user)->for($notification)->done()->create(['scheduled_at' => now()]);
        NotificationEvent::factory()->for($user)->for($notification)->done()->create(['scheduled_at' => now()->subDay()]);

        $events = Livewire::actingAs($user)
            ->test(EventList::class)
            ->get('events');

        $this->assertCount(2, $events);
        $this->assertCount(2, $events->pluck('scheduled_at')->map(fn ($d) => $d->format('Y-m-d'))->unique());
    }

    public function test_future_pending_events_are_not_included_in_the_feed(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->addHour(),
            'status' => EventStatus::Pending,
        ]);

        $events = Livewire::actingAs($user)
            ->test(EventList::class)
            ->get('events');

        $this->assertCount(0, $events);
    }

    public function test_overdue_pending_events_are_included_in_the_feed_as_missed(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->subHour(),
            'status' => EventStatus::Pending,
        ]);

        $events = Livewire::actingAs($user)
            ->test(EventList::class)
            ->get('events');

        $this->assertCount(1, $events);
        $this->assertSame(EventStatus::Pending, $events->first()->status);
    }

    public function test_mark_done_updates_event_status(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->create();
        $event = NotificationEvent::factory()->for($user)->for($notification)->cancelled()->create();

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->call('markDone', $event->id);

        $this->assertDatabaseHas('notification_events', [
            'id' => $event->id,
            'status' => EventStatus::Done,
        ]);
    }

    public function test_mark_done_is_forbidden_for_other_users_events(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $other = User::factory()->create();
        $notification = Notification::factory()->for($other)->create();

        $event = NotificationEvent::factory()->for($other)->for($notification)->create([
            'scheduled_at' => now()->midDay(),
            'status' => EventStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->call('markDone', $event->id)
            ->assertForbidden();
    }

    public function test_mark_cancelled_updates_event_status(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->create();
        $event = NotificationEvent::factory()->for($user)->for($notification)->done()->create();

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->call('markCancelled', $event->id);

        $this->assertDatabaseHas('notification_events', [
            'id' => $event->id,
            'status' => EventStatus::Cancelled,
        ]);
    }

    public function test_clear_status_reverts_event_to_pending(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->create();
        $event = NotificationEvent::factory()->for($user)->for($notification)->done()->create();

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->call('clearStatus', $event->id);

        $this->assertDatabaseHas('notification_events', [
            'id' => $event->id,
            'status' => EventStatus::Pending,
            'completed_at' => null,
        ]);
    }

    public function test_events_from_other_users_are_not_visible(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $other = User::factory()->create();
        $notification = Notification::factory()->for($other)->create(['name' => 'Other User Event']);

        NotificationEvent::factory()->for($other)->for($notification)->done()->create();

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->assertSet('weekMarksCount', 0);
    }

    public function test_events_with_deleted_notification_are_excluded(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->create();

        NotificationEvent::factory()->for($user)->for($notification)->done()->create();

        $notification->forceDelete();

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->assertSet('weekMarksCount', 0);
    }

    public function test_has_any_history_is_false_with_no_marks(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->assertSet('hasAnyHistory', false);
    }

    public function test_has_any_history_is_true_once_a_mark_exists(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->create();
        NotificationEvent::factory()->for($user)->for($notification)->done()->create();

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->assertSet('hasAnyHistory', true);
    }

    public function test_set_period_switches_to_month_and_computes_month_stats(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->create();

        NotificationEvent::factory()->for($user)->for($notification)->done()->create(['scheduled_at' => now()->startOfMonth()->addDays(2)]);
        NotificationEvent::factory()->for($user)->for($notification)->cancelled()->create(['scheduled_at' => now()->startOfMonth()->addDays(3)]);

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->call('setPeriod', 'month')
            ->assertSet('period', 'month')
            ->assertSet('monthDoneCount', 1)
            ->assertSet('monthMarksCount', 2);
    }

    public function test_prev_period_moves_the_week_back_seven_days(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->create();

        NotificationEvent::factory()->for($user)->for($notification)->done()->create(['scheduled_at' => now()->subDays(10)]);

        $component = Livewire::actingAs($user)
            ->test(EventList::class)
            ->assertSet('weekDoneCount', 0)
            ->call('prevPeriod')
            ->assertSet('weekDoneCount', 1);

        $this->assertTrue($component->get('weekCanGoNext'));
    }

    public function test_next_period_is_a_no_op_on_the_current_week(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        $component = Livewire::actingAs($user)
            ->test(EventList::class)
            ->assertSet('weekCanGoNext', false)
            ->call('nextPeriod');

        $this->assertNull($component->get('anchorDate'));
    }

    public function test_select_day_scopes_the_feed_to_that_single_day(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->create();

        $target = now()->subDays(2);
        NotificationEvent::factory()->for($user)->for($notification)->done()->create(['scheduled_at' => $target]);
        NotificationEvent::factory()->for($user)->for($notification)->done()->create(['scheduled_at' => now()->subDay()]);

        $events = Livewire::actingAs($user)
            ->test(EventList::class)
            ->call('selectDay', $target->format('Y-m-d'))
            ->get('events');

        $this->assertCount(1, $events);
    }

    public function test_select_day_twice_clears_the_selection(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $date = now()->format('Y-m-d');

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->call('selectDay', $date)
            ->assertSet('selectedDay', $date)
            ->call('selectDay', $date)
            ->assertSet('selectedDay', null);
    }

    public function test_clear_selected_day_resets_the_feed_to_the_full_period(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->create();

        NotificationEvent::factory()->for($user)->for($notification)->done()->create(['scheduled_at' => now()]);
        NotificationEvent::factory()->for($user)->for($notification)->done()->create(['scheduled_at' => now()->subDay()]);

        $events = Livewire::actingAs($user)
            ->test(EventList::class)
            ->call('selectDay', now()->format('Y-m-d'))
            ->call('clearSelectedDay')
            ->get('events');

        $this->assertCount(2, $events);
    }

    public function test_mount_preselects_the_day_from_a_query_parameter(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $date = now()->subDays(2)->format('Y-m-d');

        Livewire::actingAs($user)
            ->test(EventList::class, ['day' => $date])
            ->assertSet('selectedDay', $date);
    }

    public function test_mount_ignores_a_malformed_day_query_parameter(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        Livewire::actingAs($user)
            ->test(EventList::class, ['day' => 'not-a-date'])
            ->assertSet('selectedDay', null);
    }

    public function test_mount_rejects_a_calendar_invalid_day(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        Livewire::actingAs($user)
            ->test(EventList::class, ['day' => '2026-13-45'])
            ->assertSet('selectedDay', null);
    }

    public function test_select_day_rejects_a_malformed_date(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->call('selectDay', 'garbage')
            ->assertSet('selectedDay', null)
            ->assertOk();
    }

    public function test_period_property_cannot_be_set_directly(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->set('period', 'month');
    }

    public function test_anchor_date_property_cannot_be_set_directly(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->set('anchorDate', '2026-01-01');
    }
}
