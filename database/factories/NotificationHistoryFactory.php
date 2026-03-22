<?php

namespace Database\Factories;

use App\Enums\HistoryAction;
use App\Models\Notification;
use App\Models\NotificationHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationHistory>
 */
class NotificationHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $action = fake()->randomElement(HistoryAction::cases());

        return [
            'notification_id' => Notification::factory(),
            'user_id' => User::factory(),
            'action' => $action,
            'comment' => fake()->optional()->sentence(),
            'postponed_until' => $action === HistoryAction::Postponed
                ? fake()->dateTimeBetween('now', '+7 days')
                : null,
            'due_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Indicate the action was done.
     */
    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => HistoryAction::Done,
            'postponed_until' => null,
        ]);
    }

    /**
     * Indicate the action was postponed.
     */
    public function postponed(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => HistoryAction::Postponed,
            'postponed_until' => fake()->dateTimeBetween('now', '+7 days'),
        ]);
    }
}
