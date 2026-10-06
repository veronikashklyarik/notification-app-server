<?php

namespace App\Enums;

enum WeekDayPreset: string
{
    case Weekdays = 'weekdays';
    case Weekends = 'weekends';
    case EveryDay = 'every_day';

    public function label(): string
    {
        return match ($this) {
            self::Weekdays => __('Weekdays'),
            self::Weekends => __('Weekends'),
            self::EveryDay => __('Every day'),
        };
    }

    /**
     * @return array<int, int>
     */
    public function days(): array
    {
        return match ($this) {
            self::Weekdays => [1, 2, 3, 4, 5],
            self::Weekends => [6, 7],
            self::EveryDay => [1, 2, 3, 4, 5, 6, 7],
        };
    }
}
