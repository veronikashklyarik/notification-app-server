<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Livewire\EventList;
use App\Livewire\Home;
use App\Models\Notification;
use App\Models\NotificationEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HomeDashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * An active reminder whose rolling window of future events isn't generated, so a test controls every event itself.
     */
    private function activeNotificationWithoutGeneratedEvents(User $user): Notification
    {
        $notification = Notification::factory()->for($user)->inactive()->create();
        $notification->is_active = true;
        $notification->saveQuietly();

        return $notification;
    }

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
        $notification = $this->activeNotificationWithoutGeneratedEvents($user);

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
        $notification = $this->activeNotificationWithoutGeneratedEvents($user);

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

    public function test_week_chart_is_hidden_for_a_brand_new_account_with_no_reminders(): void
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

    public function test_week_chart_shows_an_empty_state_when_nothing_was_due(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        Notification::factory()->for($user)->inactive()->create();

        Livewire::actingAs($user)
            ->test(Home::class)
            ->assertSet('weekMarksCount', 0)
            ->assertSee(__('Last 7 days'))
            ->assertSee(__('Nothing was due'));
    }

    public function test_week_chart_stays_visible_after_the_only_mark_is_undone(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();
        $event = NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->addHour(),
            'status' => EventStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(Home::class)
            ->call('markDone', $event->id)
            ->assertSee(__('Last 7 days'))
            ->call('restoreStatus', $event->id, EventStatus::Pending->value)
            ->assertSee(__('Last 7 days'));
    }

    public function test_week_stats_count_overdue_unmarked_events_as_missed(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        NotificationEvent::factory()->for($user)->for($notification)->done()->create(['scheduled_at' => now()->subDays(2)]);
        NotificationEvent::factory()->for($user)->for($notification)->create(['scheduled_at' => now()->subDays(2), 'status' => EventStatus::Pending]);
        NotificationEvent::factory()->for($user)->for($notification)->cancelled()->create(['scheduled_at' => now()->subDays(3)]);
        NotificationEvent::factory()->for($user)->for($notification)->done()->create(['scheduled_at' => now()->subDays(4)]);

        Livewire::actingAs($user)
            ->test(Home::class)
            ->assertSet('weekDoneCount', 2)
            ->assertSet('weekMarksCount', 4)
            ->assertSet('weekCleanDays', 1);
    }

    public function test_week_summary_spells_out_what_the_total_is_made_of(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        NotificationEvent::factory()->for($user)->for($notification)->done()->create(['scheduled_at' => now()->subDays(2)]);
        NotificationEvent::factory()->for($user)->for($notification)->cancelled()->create(['scheduled_at' => now()->subDays(2)]);
        NotificationEvent::factory()->for($user)->for($notification)->count(2)->create(['scheduled_at' => now()->subDays(3), 'status' => EventStatus::Pending]);

        Livewire::actingAs($user)
            ->test(Home::class)
            ->assertSet('weekSkippedCount', 1)
            ->assertSet('weekMissedCount', 2)
            ->assertSeeHtml('25%</span> · 1 of 4 done')
            ->assertSee('1 skipped · 2 not marked')
            ->assertDontSee('of 4 marks');
    }

    public function test_week_summary_omits_the_breakdown_when_everything_was_done(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();
        NotificationEvent::factory()->for($user)->for($notification)->done()->create(['scheduled_at' => now()->subDays(2)]);

        Livewire::actingAs($user)
            ->test(Home::class)
            ->assertDontSee('1 skipped')
            ->assertDontSee('not marked');
    }

    public function test_week_stats_ignore_snoozed_and_not_yet_due_events(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();

        NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->subMinutes(5),
            'status' => EventStatus::Postponed,
            'postponed_until' => now()->addMinutes(10),
        ]);
        NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->addMinutes(5),
            'status' => EventStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(Home::class)
            ->assertSet('weekMarksCount', 0)
            ->assertSet('weekCleanDays', 0);
    }

    public function test_week_chart_marks_a_day_with_nothing_done_as_missed(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();
        NotificationEvent::factory()->for($user)->for($notification)->create(['scheduled_at' => now()->subDays(2), 'status' => EventStatus::Pending]);
        NotificationEvent::factory()->for($user)->for($notification)->cancelled()->create(['scheduled_at' => now()->subDays(2)]);

        Livewire::actingAs($user)
            ->test(Home::class)
            ->assertSeeHtml('rounded-tag bg-warning-bar " style="height: 16px"')
            ->assertSee(__('Missed'))
            ->assertDontSee(__('No reminders'));
    }

    public function test_week_chart_draws_a_day_at_the_same_height_on_today_and_in_history(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();
        NotificationEvent::factory()->for($user)->for($notification)->done()->create(['scheduled_at' => now()->subDays(2)]);
        NotificationEvent::factory()->for($user)->for($notification)->cancelled()->create(['scheduled_at' => now()->subDays(2)]);
        $partlyDoneBar = 'rounded-tag bg-indigo-light-bar " style="height: 40px"';

        $todayHtml = Livewire::actingAs($user)->test(Home::class)->html();
        $historyHtml = Livewire::actingAs($user)->test(EventList::class)->html();

        $this->assertSame(2, substr_count($todayHtml, $partlyDoneBar), 'Mobile and desktop Today charts must use the same scale.');
        $this->assertSame(1, substr_count($historyHtml, $partlyDoneBar), 'History must use the same scale as Today.');
    }

    public function test_week_chart_legend_is_shown_on_both_mobile_and_desktop(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        Notification::factory()->for($user)->inactive()->create();

        $html = Livewire::actingAs($user)->test(Home::class)->html();

        $this->assertSame(2, substr_count($html, 'bg-warning-bar"></span>'.__('Missed')));
        $this->assertSame(2, substr_count($html, __('Partly done')));
        $this->assertSame(2, substr_count($html, __('All done').'</span>'));
    }

    public function test_week_chart_draws_days_with_nothing_due_as_a_thin_stub(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        Notification::factory()->for($user)->inactive()->create();

        Livewire::actingAs($user)
            ->test(Home::class)
            ->assertSeeHtml('rounded-tag bg-avatar-bg " style="height: 4px"')
            ->assertDontSeeHtml('bg-warning-bar "');
    }

    public function test_unmarked_events_of_a_paused_reminder_are_hidden_from_today(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create(['name' => 'Paused one']);
        NotificationEvent::factory()->for($user)->for($notification)->create(['scheduled_at' => now()->startOfDay()->addMinute(), 'status' => EventStatus::Pending]);
        NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->startOfDay()->addMinutes(2),
            'status' => EventStatus::Postponed,
            'postponed_until' => now()->addMinutes(10),
        ]);

        Livewire::actingAs($user)
            ->test(Home::class)
            ->assertSet('todayEvents', fn ($events): bool => $events->isEmpty())
            ->assertSet('todayTotalCount', 0)
            ->assertDontSee('Paused one');
    }

    public function test_marked_events_of_a_paused_reminder_stay_on_today(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();
        $done = NotificationEvent::factory()->for($user)->for($notification)->done()->create(['scheduled_at' => now()->startOfDay()->addMinute()]);
        $skipped = NotificationEvent::factory()->for($user)->for($notification)->cancelled()->create(['scheduled_at' => now()->startOfDay()->addMinutes(2)]);

        Livewire::actingAs($user)
            ->test(Home::class)
            ->assertSet('todayEvents', fn ($events): bool => $events->pluck('id')->sort()->values()->all() === collect([$done->id, $skipped->id])->sort()->values()->all())
            ->assertSet('todayDoneCount', 1)
            ->assertSet('todaySkippedCount', 1);
    }

    public function test_refresh_recomputes_todays_totals(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = $this->activeNotificationWithoutGeneratedEvents($user);

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

    public function test_opening_a_push_for_a_missed_event_redirects_to_catch_up(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();
        $event = NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->subDays(2),
            'status' => EventStatus::Pending,
        ]);

        Livewire::withQueryParams(['event' => $event->id])
            ->actingAs($user)
            ->test(Home::class)
            ->assertRedirect(route('catch-up', ['event' => $event->id]));
    }

    public function test_opening_a_push_for_todays_event_stays_on_home(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();
        $event = NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now(),
            'status' => EventStatus::Pending,
        ]);

        Livewire::withQueryParams(['event' => $event->id])
            ->actingAs($user)
            ->test(Home::class)
            ->assertNoRedirect()
            ->assertSet('highlightEventId', $event->id);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function markActions(): array
    {
        return [
            'done' => ['markDone'],
            'snooze' => ['markPostponed'],
            'skip' => ['markCancelled'],
        ];
    }

    #[DataProvider('markActions')]
    public function test_marking_the_highlighted_event_removes_the_highlight(string $action): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = $this->activeNotificationWithoutGeneratedEvents($user);
        $event = NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now(),
            'status' => EventStatus::Pending,
        ]);

        Livewire::withQueryParams(['event' => $event->id])
            ->actingAs($user)
            ->test(Home::class)
            ->call($action, $event->id)
            ->assertSet('highlightEventId', null)
            ->assertDontSeeHtml('border-brand ring-2 ring-brand/20');
    }

    public function test_marking_another_event_keeps_the_push_highlight(): void
    {
        $this->travelTo(Carbon::parse('2026-10-23 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = $this->activeNotificationWithoutGeneratedEvents($user);
        $highlighted = NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->addHours(2),
            'status' => EventStatus::Pending,
        ]);
        NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->addHour(),
            'status' => EventStatus::Pending,
        ]);
        $other = NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now(),
            'status' => EventStatus::Pending,
        ]);

        Livewire::withQueryParams(['event' => $highlighted->id])
            ->actingAs($user)
            ->test(Home::class)
            ->call('markDone', $other->id)
            ->assertSet('highlightEventId', $highlighted->id)
            ->assertSeeHtml('border-brand ring-2 ring-brand/20');
    }

    public function test_opening_a_push_for_an_already_marked_old_event_stays_on_home(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($user)->inactive()->create();
        $event = NotificationEvent::factory()->for($user)->for($notification)->done()->create([
            'scheduled_at' => now()->subDays(2),
        ]);

        Livewire::withQueryParams(['event' => $event->id])
            ->actingAs($user)
            ->test(Home::class)
            ->assertNoRedirect();
    }

    public function test_opening_a_push_for_another_users_missed_event_stays_on_home(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $other = User::factory()->create(['timezone' => 'UTC']);
        $notification = Notification::factory()->for($other)->inactive()->create();
        $event = NotificationEvent::factory()->for($other)->for($notification)->create([
            'scheduled_at' => now()->subDays(2),
            'status' => EventStatus::Pending,
        ]);

        Livewire::withQueryParams(['event' => $event->id])
            ->actingAs($user)
            ->test(Home::class)
            ->assertNoRedirect();
    }

    /**
     * @return array<string, array{int, string, bool}>
     */
    public static function countdownCases(): array
    {
        return [
            'last seconds before round up to a minute' => [59, 'Next up · in 1 min', false],
            'partial minutes round up' => [200, 'Next up · in 4 min', false],
            'exact minutes stay as they are' => [300, 'Next up · in 5 min', false],
            'over an hour' => [3_900, 'Next up · in 1 h 5 min', false],
            'at the scheduled moment' => [0, 'Next up · now', false],
            'first minute after' => [-59, 'Next up · now', false],
            'one minute after' => [-60, 'Overdue · 1 min ago', true],
            'partial minutes overdue round down' => [-330, 'Overdue · 5 min ago', true],
        ];
    }

    #[DataProvider('countdownCases')]
    public function test_next_up_countdown_label(int $secondsUntil, string $expectedLabel, bool $expectedOverdue): void
    {
        Carbon::setTestNow('2026-10-23 18:00:00');
        $user = User::factory()->create(['timezone' => 'UTC']);

        $countdown = Livewire::actingAs($user)
            ->test(Home::class)
            ->instance()
            ->countdownLabel(now()->addSeconds($secondsUntil));

        $this->assertSame(['label' => $expectedLabel, 'isOverdue' => $expectedOverdue], $countdown);
    }

    public function test_next_up_card_renders_the_countdown_caption(): void
    {
        Carbon::setTestNow('2026-10-23 18:00:00');
        $user = User::factory()->create(['timezone' => 'UTC']);
        $notification = $this->activeNotificationWithoutGeneratedEvents($user);
        NotificationEvent::factory()->for($user)->for($notification)->create([
            'scheduled_at' => now()->addSeconds(30),
            'status' => EventStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(Home::class)
            ->assertSee('Next up · in 1 min')
            ->assertDontSee('in 0 min');
    }
}
