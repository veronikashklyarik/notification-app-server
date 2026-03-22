<?php

namespace App\Enums;

enum ScheduleType: string
{
    case EveryDay = 'every_day';
    case WeekDays = 'week_days';
    case EveryNDays = 'every_n_days';
    case Cyclical = 'cyclical';
    case AsNeeded = 'as_needed';

    public function label(): string
    {
        return match ($this) {
            self::EveryDay => 'Every day',
            self::WeekDays => 'Specific days',
            self::EveryNDays => 'Every few days',
            self::Cyclical => 'Cyclical',
            self::AsNeeded => 'As needed',
        };
    }
}
