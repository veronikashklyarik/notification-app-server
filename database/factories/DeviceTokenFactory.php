<?php

namespace Database\Factories;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DeviceToken>
 */
class DeviceTokenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token' => Str::random(163),
            'platform' => $this->faker->randomElement(['ios', 'android']),
            'device_name' => $this->faker->word().' '.$this->faker->randomElement(['iPhone', 'Pixel', 'Galaxy']),
        ];
    }
}
