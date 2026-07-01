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
            self::EveryDay => __('Every Day'),
            self::WeekDays => __('Specific Days'),
            self::EveryNDays => __('Every N Days'),
            self::Cyclical => __('Cyclical'),
            self::AsNeeded => __('As Needed'),
        };
    }
}
