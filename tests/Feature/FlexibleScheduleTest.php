<?php

namespace Tests\Feature;

use App\Enums\ScheduleType;
use App\Livewire\NotificationCreate;
use App\Livewire\NotificationEdit;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class FlexibleScheduleTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // SpecificDates schedule type
    // -------------------------------------------------------------------------

    public function test_create_specific_dates_notification(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('name', 'Duty shift')
            ->set('schedule_type', 'specific_dates')
            ->call('addSpecificDate', '2039-08-10')
            ->call('addSpecificDate', '2039-09-05')
            ->call('save')
            ->assertHasNoErrors();

        $notification = $user->reminders()->latest()->first();
        $this->assertEquals(ScheduleType::SpecificDates, $notification->schedule_type);
        $this->assertContains('2039-08-10', array_column($notification->specific_dates, 'date'));
        $this->assertContains('2039-09-05', array_column($notification->specific_dates, 'date'));
    }

    public function test_specific_dates_requires_at_least_one_date(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('name', 'No dates')
            ->set('schedule_type', 'specific_dates')
            ->call('save')
            ->assertHasErrors('specific_dates');
    }

    public function test_add_and_remove_specific_date(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('schedule_type', 'specific_dates')
            ->call('addSpecificDate', '2039-07-15')
            ->call('addSpecificDate', '2039-08-20');

        $this->assertCount(2, $component->get('specific_dates'));

        $component->call('removeSpecificDate', 0);

        $this->assertCount(1, $component->get('specific_dates'));
    }

    public function test_duplicate_specific_dates_are_ignored(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('schedule_type', 'specific_dates')
            ->call('addSpecificDate', '2039-07-15')
            ->call('addSpecificDate', '2039-07-15');

        $this->assertCount(1, $component->get('specific_dates'));
    }

    public function test_specific_dates_events_are_generated_for_future_dates(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        Carbon::setTestNow('2039-07-01 10:00:00');

        $notification = Notification::factory()->specificDates(
            ['2039-07-15', '2039-08-10', '2039-09-05'],
            '09:00',
        )->for($user)->create();

        $events = $notification->events()->orderBy('scheduled_at')->get();

        $this->assertCount(3, $events);
        $this->assertEquals('2039-07-15 09:00:00', $events[0]->scheduled_at->toDateTimeString());
        $this->assertEquals('2039-08-10 09:00:00', $events[1]->scheduled_at->toDateTimeString());
        $this->assertEquals('2039-09-05 09:00:00', $events[2]->scheduled_at->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_specific_dates_skips_past_dates(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        Carbon::setTestNow('2039-08-01 10:00:00');

        $notification = Notification::factory()->specificDates(
            ['2039-07-15', '2039-08-10', '2039-09-05'],
        )->for($user)->create();

        $events = $notification->events()->get();

        // July 15 is in the past, only Aug 10 and Sep 5 should be generated
        $this->assertCount(2, $events);

        Carbon::setTestNow();
    }

    // -------------------------------------------------------------------------
    // Cyclical Weekly with day selection
    // -------------------------------------------------------------------------

    public function test_create_cyclical_weekly_with_specific_days(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('name', 'Biweekly Mon+Wed')
            ->set('schedule_type', 'cyclical')
            ->set('cyclical_value', 2)
            ->set('cyclical_unit', 'weeks')
            ->set('cyclical_week_days', [1, 3])
            ->call('save')
            ->assertHasNoErrors();

        $notification = $user->reminders()->latest()->first();
        $this->assertEquals(ScheduleType::Cyclical, $notification->schedule_type);
        $this->assertEquals('weeks', $notification->cyclical_unit);
        $this->assertEquals(2, $notification->cyclical_value);
        $this->assertContains(1, $notification->cyclical_week_days);
        $this->assertContains(3, $notification->cyclical_week_days);
    }

    public function test_cyclical_weekly_events_fire_only_on_selected_days_in_active_weeks(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        // Start on a Monday (week 0 = active, week 1 = skip, week 2 = active…)
        Carbon::setTestNow('2039-07-04 06:00:00'); // Monday

        $notification = Notification::factory()
            ->cyclical(2, 'weeks')
            ->for($user)
            ->create([
                'cyclical_week_days' => [1, 3], // Mon + Wed
                'starts_at' => '2039-07-04',
                'times' => ['09:00'],
            ]);

        $events = $notification->events()->orderBy('scheduled_at')->take(4)->get();

        // Week 0 (Jul 4): Mon Jul 4, Wed Jul 6
        // Week 1 (Jul 11): skip
        // Week 2 (Jul 18): Mon Jul 18, Wed Jul 20
        $this->assertEquals('2039-07-04 09:00:00', $events[0]->scheduled_at->toDateTimeString());
        $this->assertEquals('2039-07-06 09:00:00', $events[1]->scheduled_at->toDateTimeString());
        $this->assertEquals('2039-07-18 09:00:00', $events[2]->scheduled_at->toDateTimeString());
        $this->assertEquals('2039-07-20 09:00:00', $events[3]->scheduled_at->toDateTimeString());

        Carbon::setTestNow();
    }

    // -------------------------------------------------------------------------
    // Cyclical Monthly with 'each' (specific day of month)
    // -------------------------------------------------------------------------

    public function test_create_cyclical_monthly_each(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('name', 'Monthly bill')
            ->set('schedule_type', 'cyclical')
            ->set('cyclical_value', 1)
            ->set('cyclical_unit', 'months')
            ->set('cyclical_month_type', 'each')
            ->set('cyclical_month_days', [15])
            ->call('save')
            ->assertHasNoErrors();

        $notification = $user->reminders()->latest()->first();
        $this->assertEquals('each', $notification->cyclical_month_type);
        $this->assertContains(15, $notification->cyclical_month_days);
    }

    public function test_cyclical_monthly_each_generates_on_correct_day(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        Carbon::setTestNow('2039-07-01 06:00:00');

        $notification = Notification::factory()
            ->cyclical(1, 'months')
            ->for($user)
            ->create([
                'cyclical_month_type' => 'each',
                'cyclical_month_days' => [15],
                'starts_at' => '2039-07-01',
                'times' => ['09:00'],
            ]);

        $events = $notification->events()->orderBy('scheduled_at')->take(3)->get();

        $this->assertEquals('2039-07-15 09:00:00', $events[0]->scheduled_at->toDateTimeString());
        $this->assertEquals('2039-08-15 09:00:00', $events[1]->scheduled_at->toDateTimeString());
        $this->assertEquals('2039-09-15 09:00:00', $events[2]->scheduled_at->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_cyclical_monthly_each_multi_day_generates_all_selected_days(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        Carbon::setTestNow('2039-07-01 06:00:00');

        $notification = Notification::factory()
            ->cyclical(1, 'months')
            ->for($user)
            ->create([
                'cyclical_month_type' => 'each',
                'cyclical_month_days' => [14, 15, 18],
                'starts_at' => '2039-07-01',
                'times' => ['09:00'],
            ]);

        $events = $notification->events()->orderBy('scheduled_at')->take(6)->get();

        $this->assertEquals('2039-07-14 09:00:00', $events[0]->scheduled_at->toDateTimeString());
        $this->assertEquals('2039-07-15 09:00:00', $events[1]->scheduled_at->toDateTimeString());
        $this->assertEquals('2039-07-18 09:00:00', $events[2]->scheduled_at->toDateTimeString());
        $this->assertEquals('2039-08-14 09:00:00', $events[3]->scheduled_at->toDateTimeString());
        $this->assertEquals('2039-08-15 09:00:00', $events[4]->scheduled_at->toDateTimeString());
        $this->assertEquals('2039-08-18 09:00:00', $events[5]->scheduled_at->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_cyclical_days_with_pause_cycle(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        Carbon::setTestNow('2039-07-01 06:00:00');

        $notification = Notification::factory()
            ->cyclical(1, 'days')
            ->for($user)
            ->create([
                'cyclical_use_for' => 3,
                'cyclical_pause_for' => 2,
                'starts_at' => '2039-07-01',
                'times' => ['09:00'],
            ]);

        $events = $notification->events()->orderBy('scheduled_at')->take(6)->get();

        // Cycle: use Jul 1,2,3 → pause Jul 4,5 → use Jul 6,7,8 …
        $this->assertEquals('2039-07-01 09:00:00', $events[0]->scheduled_at->toDateTimeString());
        $this->assertEquals('2039-07-02 09:00:00', $events[1]->scheduled_at->toDateTimeString());
        $this->assertEquals('2039-07-03 09:00:00', $events[2]->scheduled_at->toDateTimeString());
        $this->assertEquals('2039-07-06 09:00:00', $events[3]->scheduled_at->toDateTimeString());
        $this->assertEquals('2039-07-07 09:00:00', $events[4]->scheduled_at->toDateTimeString());
        $this->assertEquals('2039-07-08 09:00:00', $events[5]->scheduled_at->toDateTimeString());

        Carbon::setTestNow();
    }

    // -------------------------------------------------------------------------
    // Cyclical Monthly with 'on_the' (Nth weekday of month)
    // -------------------------------------------------------------------------

    public function test_create_cyclical_monthly_on_the(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('name', '3rd Tuesday')
            ->set('schedule_type', 'cyclical')
            ->set('cyclical_value', 1)
            ->set('cyclical_unit', 'months')
            ->set('cyclical_month_type', 'on_the')
            ->set('cyclical_month_position', 'third')
            ->set('cyclical_month_weekday', 2)
            ->call('save')
            ->assertHasNoErrors();

        $notification = $user->reminders()->latest()->first();
        $this->assertEquals('on_the', $notification->cyclical_month_type);
        $this->assertEquals('third', $notification->cyclical_month_position);
        $this->assertEquals(2, $notification->cyclical_month_weekday);
    }

    public function test_cyclical_monthly_on_the_generates_on_correct_weekday(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        Carbon::setTestNow('2039-07-01 06:00:00');

        $notification = Notification::factory()
            ->cyclical(1, 'months')
            ->for($user)
            ->create([
                'cyclical_month_type' => 'on_the',
                'cyclical_month_position' => 'third',
                'cyclical_month_weekday' => 2, // Tuesday
                'starts_at' => '2039-07-01',
                'times' => ['09:00'],
            ]);

        $events = $notification->events()->orderBy('scheduled_at')->take(2)->get();

        // 3rd Tuesday of July 2039 = July 19
        $this->assertEquals('2039-07-19 09:00:00', $events[0]->scheduled_at->toDateTimeString());
        // 3rd Tuesday of August 2039 = August 16
        $this->assertEquals('2039-08-16 09:00:00', $events[1]->scheduled_at->toDateTimeString());

        Carbon::setTestNow();
    }

    // -------------------------------------------------------------------------
    // Frequency labels
    // -------------------------------------------------------------------------

    public function test_specific_dates_frequency_label(): void
    {
        $notification = Notification::factory()->specificDates(['2039-08-10', '2039-09-05'])->make();

        // Label now shows the actual dates (e.g. "Aug 10, Sep 5") instead of a count
        $this->assertStringContainsString('Aug', $notification->frequency_label);
        $this->assertStringContainsString('Sep', $notification->frequency_label);
    }

    public function test_cyclical_weekly_with_days_frequency_label(): void
    {
        $notification = Notification::factory()->cyclical(2, 'weeks')->make([
            'cyclical_week_days' => [1, 3],
        ]);

        $label = $notification->frequency_label;
        $this->assertStringContainsString('2', $label);
        $this->assertStringContainsString('Mon', $label);
        $this->assertStringContainsString('Wed', $label);
    }

    public function test_cyclical_monthly_each_frequency_label(): void
    {
        $notification = Notification::factory()->cyclical(1, 'months')->make([
            'cyclical_month_type' => 'each',
            'cyclical_month_days' => [15],
        ]);

        $this->assertStringContainsString('15', $notification->frequency_label);
    }

    public function test_cyclical_monthly_on_the_frequency_label(): void
    {
        $notification = Notification::factory()->cyclical(1, 'months')->make([
            'cyclical_month_type' => 'on_the',
            'cyclical_month_position' => 'third',
            'cyclical_month_weekday' => 2,
        ]);

        $label = $notification->frequency_label;
        $this->assertStringContainsString('3rd', $label);
        $this->assertStringContainsString('Tue', $label);
    }

    // -------------------------------------------------------------------------
    // Edit
    // -------------------------------------------------------------------------

    public function test_duplicate_week_day_times_are_prevented(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('schedule_type', 'week_days')
            ->call('toggleWeekDay', 1);

        // First add: 09:00 (from toggleWeekDay seed) — add one more → 10:00
        $component->call('addWeekDayTime', 0);
        $times = $component->get('week_days')[0]['times'];
        $this->assertCount(2, $times);
        $this->assertContains('09:00', $times);
        $this->assertContains('10:00', $times);

        // Add again → 11:00, not a duplicate
        $component->call('addWeekDayTime', 0);
        $times = $component->get('week_days')[0]['times'];
        $this->assertCount(3, $times);
        $this->assertEquals(array_unique($times), $times);
    }

    public function test_duplicate_specific_date_times_are_prevented(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('schedule_type', 'specific_dates')
            ->call('addSpecificDate', '2039-08-10');

        // Default first time is 09:00; add one more → 10:00
        $component->call('addTimeToDate', 0);
        $times = $component->get('specific_dates')[0]['times'];
        $this->assertCount(2, $times);
        $this->assertContains('09:00', $times);
        $this->assertContains('10:00', $times);

        // Add again → 11:00
        $component->call('addTimeToDate', 0);
        $times = $component->get('specific_dates')[0]['times'];
        $this->assertCount(3, $times);
        $this->assertEquals(array_unique($times), $times);
    }

    public function test_add_time_prevents_duplicate_global_times(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('schedule_type', 'every_day');

        // Default is ['08:00']; add one more — should not be 08:00
        $component->call('addTime');
        $times = $component->get('times');
        $this->assertCount(2, $times);
        $this->assertEquals(count($times), count(array_unique($times)));

        // Add another
        $component->call('addTime');
        $times = $component->get('times');
        $this->assertCount(3, $times);
        $this->assertEquals(count($times), count(array_unique($times)));
    }

    public function test_direct_edit_duplicate_global_time_is_corrected(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('schedule_type', 'every_day')
            ->call('addTime'); // now ['08:00', '09:00']

        // User edits second field to match first
        $component->set('times.1', '08:00');
        $times = $component->get('times');
        $this->assertCount(2, $times);
        $this->assertEquals(count($times), count(array_unique($times)));
    }

    public function test_direct_edit_duplicate_week_day_time_is_corrected(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('schedule_type', 'week_days')
            ->call('toggleWeekDay', 1)
            ->call('addWeekDayTime', 0); // now has ['09:00', '10:00']

        // User manually edits second field to match first — should auto-correct to 11:00
        $component->set('week_days.0.times.1', '09:00');
        $times = $component->get('week_days')[0]['times'];
        $this->assertCount(2, $times);
        $this->assertContains('09:00', $times);
        $this->assertNotContains('09:00', array_slice($times, 1)); // no duplicate
        $this->assertEquals(count($times), count(array_unique($times)));
    }

    public function test_direct_edit_duplicate_specific_date_time_is_corrected(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('schedule_type', 'specific_dates')
            ->call('addSpecificDate', '2039-08-10')
            ->call('addTimeToDate', 0); // now has ['09:00', '10:00']

        // User manually edits second field to match first — should auto-correct
        $component->set('specific_dates.0.times.1', '09:00');
        $times = $component->get('specific_dates')[0]['times'];
        $this->assertCount(2, $times);
        $this->assertEquals(count($times), count(array_unique($times)));
    }

    public function test_specific_dates_respects_ends_at(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        Carbon::setTestNow('2039-07-01 10:00:00');

        $notification = Notification::factory()->specificDates(
            ['2039-07-15', '2039-08-10', '2039-09-05'],
            '09:00',
        )->for($user)->create(['ends_at' => '2039-08-31']);

        $events = $notification->events()->orderBy('scheduled_at')->get();

        $this->assertCount(2, $events);
        $this->assertEquals('2039-07-15 09:00:00', $events[0]->scheduled_at->toDateTimeString());
        $this->assertEquals('2039-08-10 09:00:00', $events[1]->scheduled_at->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_specific_dates_frequency_label_truncates_after_three(): void
    {
        $notification = Notification::factory()->specificDates([
            '2039-08-05', '2039-08-10', '2039-08-15', '2039-08-20',
        ])->make();

        $label = $notification->frequency_label;
        $this->assertStringContainsString('…', $label);
    }

    public function test_specific_dates_frequency_label_with_no_dates(): void
    {
        $notification = Notification::factory()->make([
            'schedule_type' => ScheduleType::SpecificDates,
            'specific_dates' => [],
        ]);

        $this->assertEquals(__('On specific dates'), $notification->frequency_label);
    }

    public function test_cyclical_pause_respects_ends_at(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        Carbon::setTestNow('2039-07-01 06:00:00');

        $notification = Notification::factory()
            ->cyclical(1, 'days')
            ->for($user)
            ->create([
                'cyclical_use_for' => 3,
                'cyclical_pause_for' => 2,
                'starts_at' => '2039-07-01',
                'ends_at' => '2039-07-04',
                'times' => ['09:00'],
            ]);

        // Only Jul 1,2,3 should be generated; Jul 4 is the last day of ends_at
        // but ends_at is stored as end-of-day (23:59:59), so Jul 4 at 09:00 is within it
        $events = $notification->events()->orderBy('scheduled_at')->get();
        $dates = $events->map(fn ($e) => $e->scheduled_at->format('Y-m-d'))->all();

        $this->assertContains('2039-07-01', $dates);
        $this->assertContains('2039-07-02', $dates);
        $this->assertContains('2039-07-03', $dates);
        $this->assertNotContains('2039-07-06', $dates);

        Carbon::setTestNow();
    }

    public function test_edit_loads_specific_dates_fields(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()
            ->specificDates(['2039-08-10', '2039-09-05'])
            ->for($user)
            ->create();

        $component = Livewire::actingAs($user)
            ->test(NotificationEdit::class, ['notification' => $notification]);

        $this->assertContains('2039-08-10', array_column($component->get('specific_dates'), 'date'));
        $this->assertContains('2039-09-05', array_column($component->get('specific_dates'), 'date'));
    }

    public function test_notification_edit_rejects_cross_user_mount(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();

        $notification = Notification::factory()
            ->for($owner)
            ->create(['name' => 'Owner notification']);

        Livewire::actingAs($attacker)
            ->test(NotificationEdit::class, ['notification' => $notification])
            ->assertForbidden();

        $this->assertDatabaseHas('notifications', ['id' => $notification->id, 'name' => 'Owner notification']);
    }
}
