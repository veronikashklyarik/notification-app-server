<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ScheduledNotificationCommandsTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function pushCommands(): array
    {
        return [
            'first push' => ['app:send-pending-notifications'],
            'repeat if not marked' => ['app:send-reminder-notifications'],
            'snooze expiry' => ['app:send-postponed-notifications'],
        ];
    }

    #[DataProvider('pushCommands')]
    public function test_push_command_runs_every_minute_without_overlapping(string $command): void
    {
        $event = $this->scheduledEvent($command);

        $this->assertSame('* * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
    }

    #[DataProvider('pushCommands')]
    public function test_overlap_lock_expires_within_minutes_not_a_day(string $command): void
    {
        $event = $this->scheduledEvent($command);

        $this->assertSame(10, $event->expiresAt);
    }

    #[DataProvider('pushCommands')]
    public function test_lock_left_by_a_killed_run_is_released_after_ten_minutes(string $command): void
    {
        $event = $this->scheduledEvent($command);

        // A run killed mid-way (OOM, reboot) never releases its lock
        $event->mutex->create($event);
        $this->assertTrue($event->mutex->exists($event));

        $this->travel(9)->minutes();
        $this->assertTrue($event->mutex->exists($event), 'A live lock must still prevent overlapping runs.');

        $this->travel(2)->minutes();
        $this->assertFalse($event->mutex->exists($event), 'A stale lock must not block the command for long.');
    }

    private function scheduledEvent(string $command): Event
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn (Event $event): bool => str_contains((string) $event->command, $command));

        $this->assertNotNull($event, "{$command} is not scheduled.");

        return $event;
    }
}
