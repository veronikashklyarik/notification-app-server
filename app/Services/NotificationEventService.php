<?php

namespace App\Services;

use App\Enums\EventStatus;
use App\Enums\ScheduleType;
use App\Models\Notification;
use App\Models\NotificationEvent;
use Illuminate\Support\Carbon;

class NotificationEventService
{
    private const int MAX_PENDING_EVENTS = 30;

    private const int MAX_HISTORY_EVENTS = 90;

    /**
     * Regenerate pending events for a notification.
     * Deletes existing pending events and creates new ones.
     */
    public function regenerateEvents(Notification $notification): void
    {
        $notification->events()
            ->where('status', EventStatus::Pending)
            ->delete();

        if (! $notification->is_active || $notification->schedule_type === ScheduleType::AsNeeded) {
            return;
        }

        $this->generatePendingEvents($notification);
    }

    /**
     * Top up pending events so there are up to MAX_PENDING_EVENTS.
     */
    public function topUpEvents(Notification $notification): void
    {
        if (! $notification->is_active || $notification->schedule_type === ScheduleType::AsNeeded) {
            return;
        }

        $existingCount = $notification->events()
            ->where('status', EventStatus::Pending)
            ->count();

        if ($existingCount >= self::MAX_PENDING_EVENTS) {
            return;
        }

        $remaining = self::MAX_PENDING_EVENTS - $existingCount;

        $lastPending = $notification->events()
            ->where('status', EventStatus::Pending)
            ->orderByDesc('scheduled_at')
            ->first();

        $timezone = $notification->user?->timezone ?? 'UTC';
        $startFrom = $lastPending
            ? $lastPending->scheduled_at->copy()->setTimezone($timezone)
            : now($timezone);

        $this->generatePendingEvents($notification, $remaining, $startFrom);
    }

    /**
     * Prune non-pending events beyond MAX_HISTORY_EVENTS per notification.
     */
    public function pruneHistory(Notification $notification): void
    {
        $nonPendingCount = $notification->events()
            ->where('status', '!=', EventStatus::Pending)
            ->count();

        if ($nonPendingCount <= self::MAX_HISTORY_EVENTS) {
            return;
        }

        $cutoffEvent = $notification->events()
            ->where('status', '!=', EventStatus::Pending)
            ->orderByDesc('completed_at')
            ->offset(self::MAX_HISTORY_EVENTS)
            ->limit(1)
            ->first();

        if ($cutoffEvent) {
            $notification->events()
                ->where('status', '!=', EventStatus::Pending)
                ->where('completed_at', '<', $cutoffEvent->completed_at)
                ->delete();
        }
    }

    /**
     * Generate pending events for the notification.
     */
    private function generatePendingEvents(
        Notification $notification,
        int $limit = self::MAX_PENDING_EVENTS,
        ?Carbon $startFrom = null,
    ): void {
        $timezone = $notification->user?->timezone ?? 'UTC';
        $now = $startFrom ?? now($timezone);
        $start = $notification->starts_at
            ? $notification->starts_at->copy()->setTimezone($timezone)->startOfDay()
            : now($timezone)->startOfDay();

        $base = $now->gt($start) ? $now : $start;
        $times = $this->getEffectiveTimes($notification);
        $events = [];

        $completedScheduledAts = $notification->events()
            ->whereIn('status', [EventStatus::Done, EventStatus::Cancelled, EventStatus::Postponed])
            ->pluck('scheduled_at')
            ->map(fn ($dt) => $dt->utc()->toDateTimeString())
            ->flip()
            ->all();

        // Try to find valid time slots on the current day first
        if ($this->isValidScheduleDay($notification, $base)) {
            foreach ($times as $time) {
                $candidate = $this->applyTime($base->copy(), $time);

                if ($candidate->gt($now) && $this->checkEndsAt($notification, $candidate, $timezone)) {
                    $utc = $candidate->copy()->utc();

                    if (! isset($completedScheduledAts[$utc->toDateTimeString()])) {
                        $events[] = $utc;
                    }

                    if (count($events) >= $limit) {
                        break;
                    }
                }
            }
        }

        // Generate future dates
        $currentDate = $base->copy();

        while (count($events) < $limit) {
            $nextDate = $this->getNextOccurrenceDate($notification, $currentDate, $timezone);

            if (! $nextDate) {
                break;
            }

            foreach ($times as $time) {
                $candidate = $this->applyTime($nextDate->copy(), $time);

                if (! $this->checkEndsAt($notification, $candidate, $timezone)) {
                    break 2;
                }

                $utc = $candidate->copy()->utc();

                if (! isset($completedScheduledAts[$utc->toDateTimeString()])) {
                    $events[] = $utc;
                }

                if (count($events) >= $limit) {
                    break 2;
                }
            }

            $currentDate = $nextDate;
        }

        foreach ($events as $scheduledAt) {
            NotificationEvent::query()->create([
                'notification_id' => $notification->id,
                'user_id' => $notification->user_id,
                'scheduled_at' => $scheduledAt,
                'status' => EventStatus::Pending,
            ]);
        }
    }

    private function isValidScheduleDay(Notification $notification, Carbon $date): bool
    {
        return match ($notification->schedule_type) {
            ScheduleType::EveryDay => true,
            ScheduleType::WeekDays => in_array($date->isoWeekday(), $notification->week_days ?? []),
            ScheduleType::EveryNDays => true,
            ScheduleType::Cyclical => true,
            ScheduleType::AsNeeded => false,
        };
    }

    private function getNextOccurrenceDate(Notification $notification, Carbon $base, string $timezone): ?Carbon
    {
        return match ($notification->schedule_type) {
            ScheduleType::EveryDay => $base->copy()->addDay()->startOfDay(),
            ScheduleType::WeekDays => $this->nextWeekDay($notification, $base->copy()->addDay()->startOfDay()),
            ScheduleType::EveryNDays => $base->copy()->addDays($notification->every_n_days ?? 1)->startOfDay(),
            ScheduleType::Cyclical => $this->nextCyclicalDate($notification, $base),
            default => null,
        };
    }

    private function nextWeekDay(Notification $notification, Carbon $from): ?Carbon
    {
        $days = $notification->week_days ?? [];

        if (empty($days)) {
            return null;
        }

        for ($i = 0; $i < 8; $i++) {
            $candidate = $from->copy()->addDays($i)->startOfDay();

            if (in_array($candidate->isoWeekday(), $days)) {
                return $candidate;
            }
        }

        return null;
    }

    private function nextCyclicalDate(Notification $notification, Carbon $base): Carbon
    {
        $value = $notification->cyclical_value ?? 1;

        return match ($notification->cyclical_unit ?? 'weeks') {
            'days' => $base->copy()->addDays($value)->startOfDay(),
            'weeks' => $base->copy()->addWeeks($value)->startOfDay(),
            'months' => $base->copy()->addMonths($value)->startOfDay(),
            'years' => $base->copy()->addYears($value)->startOfDay(),
            default => $base->copy()->addWeeks($value)->startOfDay(),
        };
    }

    private function checkEndsAt(Notification $notification, Carbon $date, string $timezone): bool
    {
        if ($notification->ends_at === null) {
            return true;
        }

        return $date->lte($notification->ends_at->copy()->endOfDay());
    }

    /**
     * @return array<int, string>
     */
    private function getEffectiveTimes(Notification $notification): array
    {
        $times = $notification->times ?? ['09:00'];
        sort($times);

        return $times;
    }

    private function applyTime(Carbon $date, string $time): Carbon
    {
        [$h, $m] = explode(':', $time);

        return $date->setTime((int) $h, (int) $m, 0);
    }
}
