<?php

use App\Models\Notification;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('model:prune', [
    '--model' => [Notification::class],
])->monthly();

Schedule::command('app:maintain-notification-events')->daily();
Schedule::command('app:send-pending-notifications')->everyMinute()->withoutOverlapping();
Schedule::command('app:send-reminder-notifications')->everyMinute()->withoutOverlapping();
