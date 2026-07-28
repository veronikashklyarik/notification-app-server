<?php

namespace App\Enums;

enum EventStatus: string
{
    case Pending = 'pending';
    case Done = 'done';
    case Cancelled = 'cancelled';
    case Postponed = 'postponed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Done => __('Done'),
            self::Cancelled => __('Cancelled'),
            self::Postponed => __('Postponed'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Pending => 'blue',
            self::Done => 'green',
            self::Cancelled => 'gray',
            self::Postponed => 'yellow',
        };
    }
}
