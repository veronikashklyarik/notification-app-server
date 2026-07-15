<?php

namespace Tests\Feature;

use App\Enums\ScheduleType;
use App\Livewire\NotificationCreate;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class YearlyScheduleDayTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_yearly_notification_with_explicit_day(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('name', 'Annual review')
            ->set('schedule_type', 'cyclical')
            ->set('cyclical_value', 1)
            ->set('cyclical_unit', 'years')
            ->set('cyclical_year_months', [10])
            ->set('cyclical_year_day', 7)
            ->call('save')
            ->assertHasNoErrors();

        $notification = $user->reminders()->latest()->first();
        $this->assertEquals(ScheduleType::Cyclical, $notification->schedule_type);
        $this->assertEquals(7, $notification->cyclical_year_day);
        $this->assertContains(10, $notification->cyclical_year_months);
    }

    public function test_yearly_events_fire_on_explicit_day(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        Carbon::setTestNow('2039-01-01 06:00:00');

        $notification = Notification::factory()
            ->cyclical(1, 'years')
            ->for($user)
            ->create([
                'cyclical_year_months' => [10],
                'cyclical_year_day' => 7,
                'starts_at' => '2039-01-01',
                'times' => ['09:00'],
            ]);

        $events = $notification->events()->orderBy('scheduled_at')->take(2)->get();

        $this->assertEquals('2039-10-07 09:00:00', $events[0]->scheduled_at->toDateTimeString());
        $this->assertEquals('2040-10-07 09:00:00', $events[1]->scheduled_at->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_yearly_explicit_day_clamps_to_month_end(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        Carbon::setTestNow('2039-01-01 06:00:00');

        // Day 31 in February — should clamp to Feb 28
        $notification = Notification::factory()
            ->cyclical(1, 'years')
            ->for($user)
            ->create([
                'cyclical_year_months' => [2],
                'cyclical_year_day' => 31,
                'starts_at' => '2039-01-01',
                'times' => ['09:00'],
            ]);

        $events = $notification->events()->orderBy('scheduled_at')->take(1)->get();

        $this->assertEquals('2039-02-28 09:00:00', $events[0]->scheduled_at->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_yearly_without_explicit_day_falls_back_to_anchor_day(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        Carbon::setTestNow('2039-03-15 06:00:00');

        // No cyclical_year_day — should use starts_at day (15)
        $notification = Notification::factory()
            ->cyclical(1, 'years')
            ->for($user)
            ->create([
                'cyclical_year_months' => [10],
                'cyclical_year_day' => null,
                'starts_at' => '2039-03-15',
                'times' => ['09:00'],
            ]);

        $events = $notification->events()->orderBy('scheduled_at')->take(1)->get();

        $this->assertEquals('2039-10-15 09:00:00', $events[0]->scheduled_at->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_yearly_frequency_label_includes_day(): void
    {
        $notification = Notification::factory()
            ->cyclical(1, 'years')
            ->make([
                'cyclical_year_months' => [10],
                'cyclical_year_day' => 7,
            ]);

        $label = $notification->frequency_label;

        $this->assertStringContainsString('Oct', $label);
        $this->assertStringContainsString('7', $label);
    }

    public function test_yearly_with_explicit_day_does_not_fire_on_other_days_in_target_month(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        // Simulates regeneration mid-month when today is in the target month but not the target day
        Carbon::setTestNow('2039-10-01 06:00:00');

        $notification = Notification::factory()
            ->cyclical(1, 'years')
            ->for($user)
            ->create([
                'cyclical_year_months' => [10],
                'cyclical_year_day' => 7,
                'starts_at' => '2039-01-01',
                'times' => ['09:00'],
            ]);

        $events = $notification->events()->orderBy('scheduled_at')->take(3)->get();

        // Should only fire on Oct 7, not Oct 1
        $this->assertEquals('2039-10-07 09:00:00', $events[0]->scheduled_at->toDateTimeString());
        $this->assertEquals('2040-10-07 09:00:00', $events[1]->scheduled_at->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_yearly_explicit_day_validation(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('name', 'Bad day')
            ->set('schedule_type', 'cyclical')
            ->set('cyclical_unit', 'years')
            ->set('cyclical_year_months', [10])
            ->set('cyclical_year_day', 32)
            ->call('save')
            ->assertHasErrors('cyclical_year_day');
    }
}
