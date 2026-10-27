<?php

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Jobs\SendPushNotificationJob;
use App\Models\NotificationEvent;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:send-pending-notifications')]
#[Description('Dispatch web push notifications for all pending due events')]
class SendPendingNotifications extends Command
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
            ->where('status', EventStatus::Pending)
            ->where('scheduled_at', '<=', now())
            ->whereNull('notified_at')
            ->get();

        foreach ($events as $event) {
            DB::transaction(function () use ($event): void {
                // Mark as notified so cron doesn't re-send. Status stays Pending
                // until the user explicitly marks it Done / Cancelled / Postponed.
                $event->update(['notified_at' => now()]);

                if ($event->notification === null || $event->notification->trashed() || ! $event->notification->is_active) {
                    // Orphaned or paused — skip delivery; marking it notified above keeps a resume from firing stale pushes
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
