<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('notifications')
                ->where('schedule_type', 'week_days')
                ->whereNotNull('week_days')
                ->get(['id', 'week_days', 'times'])
                ->each(function ($row): void {
                    $raw = json_decode($row->week_days, true);

                    if (empty($raw) || is_array($raw[0] ?? null)) {
                        return;
                    }

                    $times = json_decode($row->times ?? '[]', true) ?: ['09:00'];

                    $newValue = array_map(
                        fn ($d) => ['day' => (int) $d, 'times' => $times],
                        $raw,
                    );

                    DB::table('notifications')
                        ->where('id', $row->id)
                        ->update(['week_days' => json_encode($newValue)]);
                });
        });
    }

    public function down(): void
    {
        DB::table('notifications')
            ->where('schedule_type', 'week_days')
            ->whereNotNull('week_days')
            ->get(['id', 'week_days'])
            ->each(function ($row): void {
                $raw = json_decode($row->week_days, true);

                if (empty($raw) || ! is_array($raw[0] ?? null)) {
                    return;
                }

                $oldValue = array_map(fn ($e) => (int) ($e['day'] ?? 0), $raw);

                DB::table('notifications')
                    ->where('id', $row->id)
                    ->update(['week_days' => json_encode($oldValue)]);
            });
    }
};
