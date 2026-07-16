<?php

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Jobs\SendPushNotificationJob;
use App\Models\Notification;
use App\Models\NotificationEvent;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:send-reminder-notifications')]
#[Description('Dispatch reminder push notifications for still-pending events based on per-notification reminder interval')]
class SendReminderNotifications extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Pre-filter: only load events where the shortest possible interval may have elapsed,
        // eliminating recently-notified events without per-row PHP checks.
        // The exact per-notification interval is then verified in PHP below.
        $cutoff = now()->subMinutes(min(array_keys(Notification::REMINDER_INTERVALS)));

        $candidates = NotificationEvent::query()
            ->with('notification', 'user.pushSubscriptions')
            ->where('status', EventStatus::Pending)
            ->whereNotNull('notified_at')
            ->whereHas('notification', fn ($q) => $q->whereNotNull('reminder_interval'))
            ->where(fn ($q) => $q
                ->where(fn ($q) => $q->whereNull('reminded_at')->where('notified_at', '<=', $cutoff))
                ->orWhere('reminded_at', '<=', $cutoff)
            )
            ->cursor();

        $now = now();

        foreach ($candidates as $event) {
            $intervalMinutes = $event->notification?->reminder_interval;

            if ($intervalMinutes === null) {
                continue;
            }

            $lastSentAt = $event->reminded_at ?? $event->notified_at;

            if ($lastSentAt->diffInMinutes($now) < $intervalMinutes) {
                continue;
            }

            DB::transaction(function () use ($event): void {
                if ($event->notification === null || $event->notification->trashed()) {
                    return;
                }

                $event->update(['reminded_at' => now()]);

                $title = $event->notification->name;
                $body = $event->notification->description ?? '';
                $url = route('events.show', $event);

                foreach ($event->user->pushSubscriptions as $subscription) {
                    dispatch(new SendPushNotificationJob($subscription, $title, $body, ['url' => $url]))->afterCommit();
                }
            });
        }

        return self::SUCCESS;
    }
}
