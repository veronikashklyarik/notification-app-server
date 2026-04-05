<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Notification;
use App\Models\NotificationEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationEvent>
 */
class NotificationEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'notification_id' => Notification::factory(),
            'user_id' => User::factory(),
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+30 days'),
            'status' => EventStatus::Pending,
            'postponed_until' => null,
            'postpone_history' => null,
            'comment' => null,
            'completed_at' => null,
        ];
    }

    /**
     * Indicate the event is done.
     */
    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EventStatus::Done,
            'completed_at' => now(),
        ]);
    }

    /**
     * Indicate the event is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EventStatus::Cancelled,
            'completed_at' => now(),
        ]);
    }

    /**
     * Indicate the event is postponed.
     */
    public function postponed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EventStatus::Postponed,
            'postponed_until' => $this->faker->dateTimeBetween('now', '+7 days'),
            'completed_at' => now(),
        ]);
    }
}
