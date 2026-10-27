<?php

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Jobs\SendPushNotificationJob;
use App\Models\NotificationEvent;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:send-postponed-notifications')]
#[Description('Re-fires web push notifications for snoozed events whose postponed_until time has arrived')]
class SendPostponedNotifications extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $events = NotificationEvent::query()
            ->with('notification', 'user.pushSubscriptions')
            // A deleted (soft-deleted) account has no user here; its events must never reach the push loop
            ->whereHas('user')
            ->where('status', EventStatus::Postponed)
            ->whereNotNull('postponed_until')
            ->where('postponed_until', '<=', now())
            ->get();

        foreach ($events as $event) {
            DB::transaction(function () use ($event): void {
                // The snooze has elapsed — the event goes back to Pending so it
                // re-enters every normal pending-event flow (Today, Catch up,
                // History, the reminder-interval nag cron), exactly like a
                // freshly-due event that was never postponed.
                $event->update([
                    'status' => EventStatus::Pending,
                    'notified_at' => now(),
                    'postponed_until' => null,
                ]);

                // A paused reminder still gets its snooze resolved (the event counts as missed), just without a push
                if ($event->notification === null || $event->notification->trashed() || ! $event->notification->is_active) {
                    return;
                }

                $title = $event->notification->name;
                $body = $event->notification->description ?? '';
                $url = route('home', ['event' => $event->id]);

                foreach ($event->user->pushSubscriptions as $subscription) {
                    dispatch(new SendPushNotificationJob($subscription, $title, $body, ['url' => $url, 'tag' => 'event-'.$event->id]))->afterCommit();
                }
            });
        }

        return self::SUCCESS;
    }
}
