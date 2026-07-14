<?php

use App\Enums\ScheduleType;
use App\Models\Notification;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Notification::where('schedule_type', ScheduleType::WeekDays)
            ->whereNotNull('week_days')
            ->get()
            ->each(function (Notification $notification): void {
                $raw = $notification->week_days ?? [];

                if (empty($raw) || is_array($raw[0] ?? null)) {
                    return;
                }

                $times = $notification->times ?? ['09:00'];
                if (empty($times)) {
                    $times = ['09:00'];
                }

                $notification->week_days = array_map(
                    fn ($d) => ['day' => (int) $d, 'times' => $times],
                    $raw,
                );
                $notification->save();
            });
    }

    public function down(): void
    {
        Notification::where('schedule_type', ScheduleType::WeekDays)
            ->whereNotNull('week_days')
            ->get()
            ->each(function (Notification $notification): void {
                $raw = $notification->week_days ?? [];

                if (empty($raw) || ! is_array($raw[0] ?? null)) {
                    return;
                }

                $notification->week_days = array_map(fn ($e) => (int) ($e['day'] ?? 0), $raw);
                $notification->save();
            });
    }
};
