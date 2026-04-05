<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Services\NotificationEventService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:maintain-notification-events')]
#[Description('Top up pending events and prune old history for all active notifications')]
class MaintainNotificationEvents extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(NotificationEventService $service): int
    {
        $notifications = Notification::query()
            ->where('is_active', true)
            ->with('user')
            ->get();

        $this->info("Processing {$notifications->count()} active notifications...");

        foreach ($notifications as $notification) {
            $service->topUpEvents($notification);
            $service->pruneHistory($notification);
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
