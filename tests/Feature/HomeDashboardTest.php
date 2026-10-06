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

    public function test_home_computes_todays_totals_and_done_count(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->midDay(),
            'status' => EventStatus::Pending,
        ]);
        NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->startOfDay()->addHours(2),
            'status' => EventStatus::Done,
            'completed_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(Home::class)
            ->assertSet('todayTotalCount', 2)
            ->assertSet('todayDoneCount', 1);
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

    public function test_mark_postponed_snoozes_the_event_for_15_minutes(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        $event = NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->midDay(),
            'status' => EventStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(Home::class)
            ->call('markPostponed', $event->id);

        $event->refresh();
        $this->assertSame(EventStatus::Postponed, $event->status);
        $this->assertNotNull($event->postponed_until);
        $this->assertTrue($event->postponed_until->between(now()->addMinutes(14), now()->addMinutes(16)));
        $this->assertNotNull($event->postpone_history);
        $this->assertCount(1, $event->postpone_history);
    }

    public function test_postponed_events_stay_visible_on_today_while_snoozed(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        $event = NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->midDay(),
            'status' => EventStatus::Postponed,
            'postponed_until' => now()->addMinutes(15),
        ]);

        $component = Livewire::actingAs($user)->test(Home::class);

        $this->assertTrue($component->get('todayEvents')->contains('id', $event->id));
    }

    public function test_restore_status_clears_postponed_until(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        $event = NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->midDay(),
            'status' => EventStatus::Postponed,
            'postponed_until' => now()->addMinutes(15),
        ]);

        Livewire::actingAs($user)
            ->test(Home::class)
            ->call('restoreStatus', $event->id, EventStatus::Pending->value);

        $this->assertDatabaseHas('notification_events', [
            'id' => $event->id,
            'status' => EventStatus::Pending,
            'postponed_until' => null,
        ]);
    }

    public function test_clear_status_reverts_event_to_pending(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        $event = NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->midDay(),
            'status' => EventStatus::Done,
            'completed_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(Home::class)
            ->call('clearStatus', $event->id);

        $this->assertDatabaseHas('notification_events', [
            'id' => $event->id,
            'status' => EventStatus::Pending,
            'completed_at' => null,
        ]);
    }

    public function test_clear_status_is_forbidden_for_other_users_events(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $other = User::factory()->create();
        $notification = Notification::factory()->for($other)->inactive()->create();

        $event = NotificationEvent::factory()->for($other)->for($notification)->create([
            'scheduled_at' => now()->midDay(),
            'status' => EventStatus::Done,
        ]);

        Livewire::actingAs($user)
            ->test(Home::class)
            ->call('clearStatus', $event->id)
            ->assertForbidden();
    }

    public function test_restore_status_undoes_a_mark_back_to_pending(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        $event = NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->midDay(),
            'status' => EventStatus::Done,
            'completed_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(Home::class)
            ->call('restoreStatus', $event->id, EventStatus::Pending->value);

        $this->assertDatabaseHas('notification_events', [
            'id' => $event->id,
            'status' => EventStatus::Pending,
            'completed_at' => null,
        ]);
    }

    public function test_restore_status_ignores_an_invalid_status(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        $event = NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->midDay(),
            'status' => EventStatus::Done,
        ]);

        Livewire::actingAs($user)
            ->test(Home::class)
            ->call('restoreStatus', $event->id, 'not-a-real-status');

        $this->assertDatabaseHas('notification_events', [
            'id' => $event->id,
            'status' => EventStatus::Done,
        ]);
    }

    public function test_week_chart_is_hidden_for_a_brand_new_account_with_no_marks(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        Livewire::actingAs($user)
            ->test(Home::class)
            ->assertSet('weekMarksCount', 0)
            ->assertDontSee(__('Last 7 days'));
    }

    public function test_week_chart_is_shown_once_the_account_has_marks(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->midDay(),
            'status' => EventStatus::Done,
            'completed_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(Home::class)
            ->assertSet('weekMarksCount', 1)
            ->assertSee(__('Last 7 days'));
    }

    public function test_refresh_recomputes_todays_totals(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->midDay(),
            'status' => EventStatus::Pending,
        ]);

        $component = Livewire::actingAs($user)
            ->test(Home::class)
            ->assertSet('todayTotalCount', 1);

        NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->midDay(),
            'status' => EventStatus::Pending,
        ]);

        $component->call('refresh')->assertSet('todayTotalCount', 2);
    }
}
