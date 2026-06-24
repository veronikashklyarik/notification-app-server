<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Livewire\Home;
use App\Models\Notification;
use App\Models\NotificationEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HomeDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('home'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_home_dashboard(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Home::class)
            ->assertOk();
    }

    public function test_home_shows_active_notification_count(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(2)->for($user)->create(['is_active' => true]);
        Notification::factory()->for($user)->create(['is_active' => false]);

        Livewire::actingAs($user)
            ->test(Home::class)
            ->assertSet('stats.active_notifications', 2);
    }

    public function test_home_shows_todays_pending_events(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->midDay(),
            'status' => EventStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(Home::class)
            ->assertSet('todayTotal', 1);
    }

    public function test_home_shows_missed_events_from_previous_days(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->subDays(2),
            'status' => EventStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(Home::class)
            ->assertSet('missedTotal', 1);
    }

    public function test_mark_done_updates_event_status(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        $event = NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->midDay(),
            'status' => EventStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(Home::class)
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
            'scheduled_at' => now()->midDay(),
            'status' => EventStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(Home::class)
            ->call('markDone', $event->id)
            ->assertForbidden();
    }

    public function test_mark_cancelled_updates_event_status(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        $event = NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->midDay(),
            'status' => EventStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(Home::class)
            ->call('markCancelled', $event->id);

        $this->assertDatabaseHas('notification_events', [
            'id' => $event->id,
            'status' => EventStatus::Cancelled,
        ]);
    }

    public function test_complete_all_missed_marks_past_pending_events_as_done(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        NotificationEvent::factory()->count(3)->for($user)->for($notification)->create([
            'scheduled_at' => now()->subDays(3),
            'status' => EventStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(Home::class)
            ->call('completeAllMissed');

        $this->assertDatabaseCount('notification_events', 3);
        $this->assertDatabaseMissing('notification_events', ['status' => EventStatus::Pending]);
        $this->assertDatabaseHas('notification_events', ['status' => EventStatus::Done]);
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
            ->test(Home::class)
            ->call('skipAllMissed');

        $this->assertDatabaseMissing('notification_events', ['status' => EventStatus::Pending]);
        $this->assertDatabaseHas('notification_events', ['status' => EventStatus::Cancelled]);
    }

    public function test_load_more_increases_today_per_page(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        Livewire::actingAs($user)
            ->test(Home::class)
            ->assertSet('todayPerPage', 10)
            ->call('loadMore')
            ->assertSet('todayPerPage', 20);
    }

    public function test_refresh_resets_pagination(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        Livewire::actingAs($user)
            ->test(Home::class)
            ->call('loadMore')
            ->assertSet('todayPerPage', 20)
            ->call('refresh')
            ->assertSet('todayPerPage', 10);
    }
}
