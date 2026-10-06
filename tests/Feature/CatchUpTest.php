<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Livewire\CatchUp;
use App\Models\Notification;
use App\Models\NotificationEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatchUpTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('catch-up'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_catch_up(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CatchUp::class)
            ->assertOk();
    }

    public function test_it_lists_missed_events_from_previous_days(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->subDays(2),
            'status' => EventStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(CatchUp::class)
            ->assertSet('missedTotal', 1);
    }

    public function test_mark_done_updates_event_status(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        $event = NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->subDays(2),
            'status' => EventStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(CatchUp::class)
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
        $notification = Notification::factory()->for($other)->inactive()->create();

        $event = NotificationEvent::factory()->for($other)->for($notification)->create([
            'scheduled_at' => now()->subDays(2),
            'status' => EventStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(CatchUp::class)
            ->call('markDone', $event->id)
            ->assertForbidden();
    }

    public function test_mark_cancelled_updates_event_status(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        $event = NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->subDays(2),
            'status' => EventStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(CatchUp::class)
            ->call('markCancelled', $event->id);

        $this->assertDatabaseHas('notification_events', [
            'id' => $event->id,
            'status' => EventStatus::Cancelled,
        ]);
    }

    public function test_skip_all_missed_marks_past_pending_events_as_cancelled(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        NotificationEvent::factory()->count(2)->for($user)->for($notification)->create([
            'scheduled_at' => now()->subDays(1),
            'status' => EventStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(CatchUp::class)
            ->call('skipAllMissed');

        $this->assertDatabaseMissing('notification_events', ['status' => EventStatus::Pending]);
        $this->assertDatabaseHas('notification_events', ['status' => EventStatus::Cancelled]);
    }

    public function test_confirm_skip_all_opens_the_dialog_without_skipping_anything(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->subDays(1),
            'status' => EventStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(CatchUp::class)
            ->call('confirmSkipAll')
            ->assertSet('confirmingSkipAll', true);

        $this->assertDatabaseHas('notification_events', ['status' => EventStatus::Pending]);
    }

    public function test_cancel_skip_all_closes_the_dialog(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        Livewire::actingAs($user)
            ->test(CatchUp::class)
            ->call('confirmSkipAll')
            ->call('cancelSkipAll')
            ->assertSet('confirmingSkipAll', false);
    }

    public function test_skip_all_missed_raises_a_bulk_undo_state(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        $events = NotificationEvent::factory()->count(2)->for($user)->for($notification)->create([
            'scheduled_at' => now()->subDays(1),
            'status' => EventStatus::Pending,
        ]);

        $component = Livewire::actingAs($user)
            ->test(CatchUp::class)
            ->call('skipAllMissed');

        $undoState = $component->get('undoState');
        $this->assertNotNull($undoState);
        $this->assertEqualsCanonicalizing($events->pluck('id')->map(fn ($id) => (string) $id)->all(), array_map('strval', $undoState['eventIds']));
        $this->assertSame(EventStatus::Pending->value, $undoState['previousStatus']);
    }

    public function test_restore_bulk_puts_events_back_to_their_previous_status(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        $events = NotificationEvent::factory()->count(2)->for($user)->for($notification)->create([
            'scheduled_at' => now()->subDays(1),
            'status' => EventStatus::Cancelled,
            'completed_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(CatchUp::class)
            ->call('restoreBulk', $events->pluck('id')->all(), EventStatus::Pending->value);

        $this->assertDatabaseMissing('notification_events', ['status' => EventStatus::Cancelled]);
        foreach ($events as $event) {
            $this->assertDatabaseHas('notification_events', ['id' => $event->id, 'status' => EventStatus::Pending, 'completed_at' => null]);
        }
    }

    public function test_restore_bulk_is_forbidden_for_other_users_events(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $other = User::factory()->create();
        $notification = Notification::factory()->for($other)->inactive()->create();

        $event = NotificationEvent::factory()->for($other)->for($notification)->create([
            'status' => EventStatus::Cancelled,
            'completed_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(CatchUp::class)
            ->call('restoreBulk', [$event->id], EventStatus::Pending->value)
            ->assertForbidden();
    }

    public function test_mark_day_done_marks_only_that_days_pending_events(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        $dayOne = now()->subDays(2)->startOfDay()->addHours(9);
        $dayTwo = now()->subDays(1)->startOfDay()->addHours(9);

        $dayOneEvents = NotificationEvent::factory()->count(2)->for($user)->for($notification)->create([
            'scheduled_at' => $dayOne,
            'status' => EventStatus::Pending,
        ]);

        $dayTwoEvent = NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => $dayTwo,
            'status' => EventStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(CatchUp::class)
            ->call('markDayDone', $dayOne->format('Y-m-d'));

        foreach ($dayOneEvents as $event) {
            $this->assertDatabaseHas('notification_events', ['id' => $event->id, 'status' => EventStatus::Done]);
        }
        $this->assertDatabaseHas('notification_events', ['id' => $dayTwoEvent->id, 'status' => EventStatus::Pending]);
    }

    public function test_load_more_missed_increases_missed_per_page(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        Livewire::actingAs($user)
            ->test(CatchUp::class)
            ->assertSet('missedPerPage', 10)
            ->call('loadMoreMissed')
            ->assertSet('missedPerPage', 20);
    }
}
