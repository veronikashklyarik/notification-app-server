<?php

namespace App\Helpers;

use Carbon\Carbon;

class TimeHelper
{
    public static function toUserTz(?Carbon $date, string $format = 'M j, Y \a\t H:i'): string
    {
        if (! $date) {
            return '';
        }

        $tz = config('app.user_timezone', 'UTC');

        return $date->copy()->setTimezone($tz)->translatedFormat($format);
    }

    public static function toUserDate(?Carbon $date): ?Carbon
    {
        if (! $date) {
            return null;
        }

        $tz = config('app.user_timezone', 'UTC');

        return $date->copy()->setTimezone($tz);
    }
}
