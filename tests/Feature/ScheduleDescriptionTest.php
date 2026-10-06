<?php

namespace Tests\Feature;

use App\Livewire\NotificationCreate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Covers every scheduleDescription() branch in HasScheduleDescription — the
 * sentence shown in the schedule sheet's tint block. NotificationCreateTest
 * only exercised the SpecificDates/inactive branch; this fills the rest.
 */
class ScheduleDescriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_day_description(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('schedule_type', 'every_day')
            ->set('times', ['08:00', '20:00'])
            ->set('starts_at', now()->format('Y-m-d'));

        $this->assertSame(
            'Every day at 08:00 and 20:00, from today.',
            $component->get('scheduleDescription')
        );
    }

    public function test_every_day_description_with_reminder_interval(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('schedule_type', 'every_day')
            ->set('times', ['08:00'])
            ->set('starts_at', now()->format('Y-m-d'))
            ->set('reminderInterval', 30);

        $this->assertSame(
            'Every day at 08:00, from today. If you don’t mark it, it repeats every 30 minutes.',
            $component->get('scheduleDescription')
        );
    }

    public function test_week_days_description_groups_days_sharing_the_same_times(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('schedule_type', 'week_days')
            ->set('week_days', [
                ['day' => 1, 'times' => ['14:30']],
                ['day' => 3, 'times' => ['14:30']],
                ['day' => 5, 'times' => ['14:30', '19:00']],
            ]);

        $this->assertSame(
            'Mondays and Wednesdays at 14:30, Fridays at 14:30 and 19:00.',
            $component->get('scheduleDescription')
        );
    }

    public function test_week_days_description_with_no_days_selected(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('schedule_type', 'week_days')
            ->set('week_days', []);

        $this->assertSame(
            'Choose days of the week below',
            $component->get('scheduleDescription')
        );
    }

    public function test_cyclical_on_off_days_description(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('schedule_type', 'cyclical')
            ->set('cyclical_unit', 'days')
            ->set('cyclical_use_for', 14)
            ->set('cyclical_pause_for', 7)
            ->set('times', ['09:00'])
            ->set('starts_at', now()->format('Y-m-d'));

        $this->assertSame(
            'Every day at 09:00 for 14 days, then 7 days off, and again — starting today.',
            $component->get('scheduleDescription')
        );
    }

    public function test_cyclical_plain_every_n_days_description(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('schedule_type', 'cyclical')
            ->set('cyclical_unit', 'days')
            ->set('cyclical_value', 3)
            ->set('times', ['19:00']);

        $this->assertSame(
            'Every 3 days at 19:00.',
            $component->get('scheduleDescription')
        );
    }

    public function test_cyclical_weeks_description_with_weekdays(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('schedule_type', 'cyclical')
            ->set('cyclical_unit', 'weeks')
            ->set('cyclical_value', 2)
            ->set('cyclical_week_days', [1, 3])
            ->set('times', ['09:00']);

        $this->assertSame(
            'Every 2 weeks on Mon, Wed at 09:00.',
            $component->get('scheduleDescription')
        );
    }

    public function test_cyclical_months_each_description(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('schedule_type', 'cyclical')
            ->set('cyclical_unit', 'months')
            ->set('cyclical_month_type', 'each')
            ->set('cyclical_month_days', [1])
            ->set('times', ['10:00']);

        $this->assertSame(
            'On the 1st of every month at 10:00.',
            $component->get('scheduleDescription')
        );
    }

    public function test_cyclical_months_on_the_description(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('schedule_type', 'cyclical')
            ->set('cyclical_unit', 'months')
            ->set('cyclical_month_type', 'on_the')
            ->set('cyclical_month_position', 'first')
            ->set('cyclical_month_weekday', 1)
            ->set('times', ['09:00']);

        $this->assertSame(
            'Every 1 month on the 1st Mon at 09:00.',
            $component->get('scheduleDescription')
        );
    }

    public function test_cyclical_years_description_with_fixed_day(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('schedule_type', 'cyclical')
            ->set('cyclical_unit', 'years')
            ->set('cyclical_year_months', [1])
            ->set('cyclical_year_use_weekday', false)
            ->set('cyclical_year_day', 1)
            ->set('times', ['09:00']);

        $this->assertSame(
            'Every 1 year in Jan on the 1st at 09:00.',
            $component->get('scheduleDescription')
        );
    }

    public function test_specific_dates_description_while_active(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('schedule_type', 'specific_dates')
            ->set('specific_dates', [
                ['date' => '2026-10-13', 'times' => ['09:00']],
            ])
            ->set('is_active', true);

        $this->assertSame(
            'On Tue 13 Oct at 09:00. After that it ends.',
            $component->get('scheduleDescription')
        );
    }

    public function test_trailing_clause_prioritizes_inactive_over_reminder_interval(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('schedule_type', 'every_day')
            ->set('times', ['08:00'])
            ->set('starts_at', now()->format('Y-m-d'))
            ->set('is_active', false)
            ->set('reminderInterval', 30);

        $this->assertSame(
            'Every day at 08:00, from today. Notifications are off — it stays in the list, silent.',
            $component->get('scheduleDescription')
        );
    }

    public function test_cyclical_months_each_description_in_polish_has_no_double_space(): void
    {
        $user = User::factory()->create();
        app()->setLocale('pl');

        $component = Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('schedule_type', 'cyclical')
            ->set('cyclical_unit', 'months')
            ->set('cyclical_month_type', 'each')
            ->set('cyclical_month_days', [1])
            ->set('times', ['10:00']);

        $this->assertSame(
            '1 każdego miesiąca o 10:00.',
            $component->get('scheduleDescription')
        );

        app()->setLocale('en');
    }
}
