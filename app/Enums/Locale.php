<?php

namespace App\Enums;

enum Locale: string
{
    case En = 'en';
    case Ru = 'ru';
    case Pl = 'pl';

    public function label(): string
    {
        return match ($this) {
            self::En => 'English',
            self::Ru => 'Русский',
            self::Pl => 'Polski',
        };
    }
}
