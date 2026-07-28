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

class PerDayTimesTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_week_days_stores_per_day_format(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('name', 'Workout')
            ->set('schedule_type', 'week_days')
            ->call('toggleWeekDay', 1)
            ->call('toggleWeekDay', 3)
            ->call('save')
            ->assertHasNoErrors();

        $notification = $user->reminders()->latest()->first();
        $this->assertEquals(ScheduleType::WeekDays, $notification->schedule_type);

        $weekDays = $notification->week_days;
        $this->assertCount(2, $weekDays);

        $days = array_column($weekDays, 'day');
        $this->assertContains(1, $days);
        $this->assertContains(3, $days);

        foreach ($weekDays as $entry) {
            $this->assertArrayHasKey('day', $entry);
            $this->assertArrayHasKey('times', $entry);
            $this->assertNotEmpty($entry['times']);
        }
    }

    public function test_week_days_events_use_per_day_times(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        Carbon::setTestNow('2039-01-01 06:00:00'); // Saturday (day 6)

        $notification = Notification::factory()
            ->weekDays([1, 3], '09:00')
            ->for($user)
            ->create([
                'week_days' => [
                    ['day' => 1, 'times' => ['07:00']],
                    ['day' => 3, 'times' => ['15:00', '20:00']],
                ],
                'starts_at' => '2039-01-01',
            ]);

        $events = $notification->events()->orderBy('scheduled_at')->take(5)->get();

        // 2039-01-03 is Monday (day 1) → 07:00
        $this->assertEquals('2039-01-03 07:00:00', $events[0]->scheduled_at->toDateTimeString());
        // 2039-01-05 is Wednesday (day 3) → 15:00 and 20:00
        $this->assertEquals('2039-01-05 15:00:00', $events[1]->scheduled_at->toDateTimeString());
        $this->assertEquals('2039-01-05 20:00:00', $events[2]->scheduled_at->toDateTimeString());
        // Next Monday → 07:00
        $this->assertEquals('2039-01-10 07:00:00', $events[3]->scheduled_at->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_week_days_frequency_label_lists_day_names(): void
    {
        $notification = Notification::factory()
            ->weekDays([1, 3, 5])
            ->make();

        $label = $notification->frequency_label;

        $this->assertStringContainsString('Mon', $label);
        $this->assertStringContainsString('Wed', $label);
        $this->assertStringContainsString('Fri', $label);
    }

    public function test_toggle_week_day_adds_entry(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('schedule_type', 'week_days')
            ->call('toggleWeekDay', 2)
            ->assertSet('week_days', [['day' => 2, 'times' => ['09:00']]]);
    }

    public function test_toggle_week_day_removes_existing_entry(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('schedule_type', 'week_days')
            ->call('toggleWeekDay', 2)
            ->call('toggleWeekDay', 2)
            ->assertSet('week_days', []);
    }

    public function test_add_week_day_time_appends_time(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('schedule_type', 'week_days')
            ->call('toggleWeekDay', 5)
            ->call('addWeekDayTime', 0)
            ->assertSet('week_days.0.times', ['09:00', '10:00']);
    }

    public function test_remove_week_day_time_does_not_remove_last(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('schedule_type', 'week_days')
            ->call('toggleWeekDay', 4)
            ->call('removeWeekDayTime', 0, 0)
            ->assertSet('week_days.0.times', ['09:00']);
    }

    public function test_week_days_sorted_by_day_number_after_toggle(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('schedule_type', 'week_days')
            ->call('toggleWeekDay', 5)
            ->call('toggleWeekDay', 2)
            ->assertSet('week_days.0.day', 2)
            ->assertSet('week_days.1.day', 5);
    }

    public function test_save_requires_at_least_one_day_for_week_days(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('name', 'Missing days')
            ->set('schedule_type', 'week_days')
            ->set('week_days', [])
            ->call('save')
            ->assertHasErrors('week_days');
    }
}
