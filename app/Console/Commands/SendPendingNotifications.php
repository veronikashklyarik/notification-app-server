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
            ->get();

        foreach ($events as $event) {
            DB::transaction(function () use ($event): void {
                $event->update(['status' => EventStatus::Done]);

                if ($event->notification === null || $event->notification->trashed()) {
                    // Orphaned event (parent notification deleted) — mark Done and skip delivery
                    return;
                }

                $title = $event->notification->name;
                $body = $event->notification->description ?? '';

                foreach ($event->user->pushSubscriptions as $subscription) {
                    dispatch(new SendPushNotificationJob($subscription, $title, $body))->afterCommit();
                }
            });
        }

        return self::SUCCESS;
    }
}
