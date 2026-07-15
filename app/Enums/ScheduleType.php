<?php

namespace App\Enums;

enum ScheduleType: string
{
    case EveryDay = 'every_day';
    case WeekDays = 'week_days';
    case EveryNDays = 'every_n_days';
    case Cyclical = 'cyclical';
    case SpecificDates = 'specific_dates';
    case AsNeeded = 'as_needed';

    public function label(): string
    {
        return match ($this) {
            self::EveryDay => __('Every Day'),
            self::WeekDays => __('Specific Days of the Week'),
            self::EveryNDays => __('Every Few Days'),
            self::Cyclical => __('Custom'),
            self::SpecificDates => __('On Specific Dates'),
            self::AsNeeded => __('As Needed'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::EveryDay => __('Every day at the same time'),
            self::WeekDays => __('On Mondays, on Weekdays'),
            self::EveryNDays => __('Every other day, every 3 days'),
            self::Cyclical => __('Every 2 weeks on Mon & Wed, or on the 1st Friday of each month'),
            self::SpecificDates => __('Pick exact dates, e.g. for irregular schedules'),
            self::AsNeeded => __('No automatic reminders — manually log each time'),
        };
    }
}
