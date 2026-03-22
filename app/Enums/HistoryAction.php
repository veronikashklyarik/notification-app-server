<?php

namespace App\Enums;

enum HistoryAction: string
{
    case Done = 'Done';
    case Cancelled = 'Cancelled';
    case Postponed = 'Postponed';

    public function label(): string
    {
        return $this->value;
    }

    public function badgeColor(): string
    {
        return match($this) {
            self::Done => 'green',
            self::Cancelled => 'gray',
            self::Postponed => 'yellow',
        };
    }
}
