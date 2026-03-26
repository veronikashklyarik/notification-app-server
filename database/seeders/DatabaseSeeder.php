<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Notification::factory()->daily('08:00')->create([
            'user_id' => $user->id,
            'name' => 'Take vitamins',
            'description' => 'Morning vitamins and supplements.',
            'times' => ['08:00', '20:00'],
        ]);

        Notification::factory()->weekDays([1, 3, 5], '10:00')->create([
            'user_id' => $user->id,
            'name' => 'Exercise',
            'description' => 'Mon, Wed, Fri workout.',
        ]);

        Notification::factory()->cyclical(1, 'months')->create([
            'user_id' => $user->id,
            'name' => 'Pay rent',
            'times' => ['09:00'],
        ]);

        Notification::factory()->everyNDays(3)->create([
            'user_id' => $user->id,
            'name' => 'Water plants',
        ]);
    }
}
