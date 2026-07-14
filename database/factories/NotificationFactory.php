<?php

namespace Database\Factories;

use App\Enums\ScheduleType;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $scheduleType = $this->faker->randomElement(array_filter(
            ScheduleType::cases(),
            fn (ScheduleType $t) => ! in_array($t, [ScheduleType::AsNeeded, ScheduleType::EveryNDays]),
        ));

        return [
            'user_id' => User::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'schedule_type' => $scheduleType,
            'week_days' => $scheduleType === ScheduleType::WeekDays
                ? array_map(
                    fn ($d) => ['day' => $d, 'times' => ['09:00']],
                    $this->faker->randomElements([1, 2, 3, 4, 5, 6, 7], $this->faker->numberBetween(2, 5)),
                )
                : null,
            'specific_dates' => null,
            'every_n_days' => $scheduleType === ScheduleType::EveryNDays ? $this->faker->numberBetween(2, 14) : null,
            'cyclical_value' => $scheduleType === ScheduleType::Cyclical ? $this->faker->numberBetween(1, 6) : null,
            'cyclical_unit' => $scheduleType === ScheduleType::Cyclical ? $this->faker->randomElement(['weeks', 'months']) : null,
            'cyclical_week_days' => null,
            'cyclical_month_type' => null,
            'cyclical_month_days' => null,
            'cyclical_month_position' => null,
            'cyclical_month_weekday' => null,
            'cyclical_year_months' => null,
            'cyclical_year_use_weekday' => false,
            'cyclical_use_for' => null,
            'cyclical_pause_for' => null,
            'times' => ! in_array($scheduleType, [ScheduleType::AsNeeded, ScheduleType::SpecificDates]) ? ['09:00'] : null,
            'starts_at' => now(),
            'ends_at' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate the notification is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate the notification fires every day at a specific time.
     */
    public function daily(string $time = '09:00'): static
    {
        return $this->state(fn (array $attributes) => [
            'schedule_type' => ScheduleType::EveryDay,
            'week_days' => null,
            'every_n_days' => null,
            'cyclical_value' => null,
            'cyclical_unit' => null,
            'times' => [$time],
        ]);
    }

    /**
     * Indicate the notification fires on specific days of the week.
     *
     * @param  array<int>  $days  ISO weekday numbers (1 = Monday … 7 = Sunday)
     */
    /**
     * @param  array<int>  $days  ISO weekday numbers (1 = Monday … 7 = Sunday)
     * @param  array<int, string>|string  $times  Per-day time(s); a string is broadcast to all days
     */
    public function weekDays(array $days, array|string $times = '09:00'): static
    {
        $timesArray = is_string($times) ? [$times] : $times;

        return $this->state(fn (array $attributes) => [
            'schedule_type' => ScheduleType::WeekDays,
            'week_days' => array_map(fn ($d) => ['day' => $d, 'times' => $timesArray], $days),
            'every_n_days' => null,
            'cyclical_value' => null,
            'cyclical_unit' => null,
            'times' => null,
        ]);
    }

    /**
     * Indicate the notification fires every N days.
     */
    public function everyNDays(int $days, string $time = '09:00'): static
    {
        return $this->state(fn (array $attributes) => [
            'schedule_type' => ScheduleType::EveryNDays,
            'week_days' => null,
            'every_n_days' => $days,
            'cyclical_value' => null,
            'cyclical_unit' => null,
            'times' => [$time],
        ]);
    }

    /**
     * Indicate the notification fires on a cyclical schedule.
     */
    public function cyclical(int $value, string $unit = 'weeks'): static
    {
        return $this->state(fn (array $attributes) => [
            'schedule_type' => ScheduleType::Cyclical,
            'week_days' => null,
            'every_n_days' => null,
            'cyclical_value' => $value,
            'cyclical_unit' => $unit,
            'times' => ['09:00'],
        ]);
    }

    /**
     * Indicate the notification fires on specific explicit dates.
     *
     * @param  array<int, string>  $dates  Y-m-d formatted date strings
     */
    public function specificDates(array $dates, string $time = '09:00'): static
    {
        return $this->state(fn (array $attributes) => [
            'schedule_type' => ScheduleType::SpecificDates,
            'week_days' => null,
            'specific_dates' => array_map(fn ($date) => ['date' => $date, 'times' => [$time]], $dates),
            'every_n_days' => null,
            'cyclical_value' => null,
            'cyclical_unit' => null,
            'times' => null,
        ]);
    }
}
