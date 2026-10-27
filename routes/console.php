<?php

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('model:prune', [
    '--model' => [Notification::class, User::class],
])->monthly();

Schedule::command('app:maintain-notification-events')->daily();
// The overlap lock expires after 10 minutes instead of the default 24 hours: a run killed mid-way
// (OOM, reboot) never releases it, and a day-long stale lock silently stops that kind of push.
Schedule::command('app:send-pending-notifications')->everyMinute()->withoutOverlapping(10);
Schedule::command('app:send-reminder-notifications')->everyMinute()->withoutOverlapping(10);
Schedule::command('app:send-postponed-notifications')->everyMinute()->withoutOverlapping(10);
