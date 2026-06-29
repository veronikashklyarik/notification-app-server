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
            ->where('status', EventStatus::Pending)
            ->where('scheduled_at', '<=', now())
            ->whereNull('notified_at')
            ->get();

        foreach ($events as $event) {
            DB::transaction(function () use ($event): void {
                // Mark as notified so cron doesn't re-send. Status stays Pending
                // until the user explicitly marks it Done / Cancelled / Postponed.
                $event->update(['notified_at' => now()]);

                if ($event->notification === null || $event->notification->trashed()) {
                    // Orphaned event (parent notification deleted) — skip delivery
                    return;
                }

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
