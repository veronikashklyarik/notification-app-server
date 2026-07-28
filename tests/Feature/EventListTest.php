<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Livewire\EventList;
use App\Models\Notification;
use App\Models\NotificationEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EventListTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('events.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_events_page(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->assertOk();
    }

    public function test_shows_todays_pending_events(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->midDay(),
            'status' => EventStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->assertSet('todayTotal', 1);
    }

    public function test_shows_upcoming_pending_events(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->addDays(3),
            'status' => EventStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->assertSet('upcomingTotal', 1);
    }

    public function test_shows_recent_completed_events(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->create();

        NotificationEvent::factory()->for($user)->for($notification)->done()->create();
        NotificationEvent::factory()->for($user)->for($notification)->cancelled()->create();

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->assertSet('recentTotal', 2);
    }

    public function test_mark_done_updates_event_status(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->create();

        $event = NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->midDay(),
            'status' => EventStatus::Pending,
        ]);

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

        $event = NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->midDay(),
            'status' => EventStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->call('markCancelled', $event->id);

        $this->assertDatabaseHas('notification_events', [
            'id' => $event->id,
            'status' => EventStatus::Cancelled,
        ]);
    }

    public function test_load_more_today_increases_per_page(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->assertSet('todayPerPage', 10)
            ->call('loadMoreToday')
            ->assertSet('todayPerPage', 20);
    }

    public function test_load_more_upcoming_increases_per_page(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->assertSet('upcomingPerPage', 10)
            ->call('loadMoreUpcoming')
            ->assertSet('upcomingPerPage', 20);
    }

    public function test_refresh_resets_all_pagination(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->call('loadMoreToday')
            ->call('loadMoreUpcoming')
            ->call('loadMoreRecent')
            ->call('refresh')
            ->assertSet('todayPerPage', 10)
            ->assertSet('upcomingPerPage', 10)
            ->assertSet('recentPerPage', 10);
    }

    public function test_events_from_other_users_are_not_visible(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $other = User::factory()->create();
        $notification = Notification::factory()->for($other)->create(['name' => 'Other User Event']);

        NotificationEvent::factory()->for($other)->for($notification)->create([
            'scheduled_at' => now()->midDay(),
            'status' => EventStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->assertSet('todayTotal', 0);
    }

    public function test_recent_events_with_deleted_notification_are_excluded(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->create();

        NotificationEvent::factory()->for($user)->for($notification)->done()->create();

        $notification->forceDelete();

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->assertSet('recentTotal', 0);
    }

    public function test_pending_events_with_deleted_notification_are_excluded(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->create();

        NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->midDay(),
            'status' => EventStatus::Pending,
        ]);

        $notification->forceDelete();

        Livewire::actingAs($user)
            ->test(EventList::class)
            ->assertSet('todayTotal', 0);
    }
}
